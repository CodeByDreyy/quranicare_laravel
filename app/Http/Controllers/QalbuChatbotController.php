<?php

namespace App\Http\Controllers;

use App\Models\QalbuConversation;
use App\Models\QalbuMessage;
use App\Events\UserActivityEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\UnansweredQuestion;

class QalbuChatbotController extends Controller
{
    protected string $chatbotBaseUrl;

    public function __construct()
    {
        $this->chatbotBaseUrl = config('services.chatbot.base_uri', 'http://127.0.0.1:5000');
    }

    public function chat(Request $request)
    {
        // Debug log
        Log::info('Chatbot request received', [
            'headers' => $request->headers->all(),
            'body' => $request->all(),
            'raw_body' => $request->getContent(),
        ]);

        $v = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'message' => 'required|string',
            'conversation_id' => 'nullable|integer',
        ]);
        if ($v->fails()) {
            Log::error('Validation failed', ['errors' => $v->errors()]);
            return response()->json(['errors' => $v->errors()], 422);
        }

        $userId = (int) $request->input('user_id');
        $messageText = trim($request->input('message'));
        $conversationId = $request->input('conversation_id');

        $conversation = null;
        if ($conversationId) {
            $conversation = QalbuConversation::find($conversationId);
        }
        if (!$conversation) {
            $conversation = QalbuConversation::create([
                'user_id' => $userId,
                'title' => null,
                'conversation_type' => 'general',
                'user_emotion' => null,
                'context_data' => null,
                'is_active' => 1,
            ]);
        }

        // Save user message
        $userMsg = QalbuMessage::create([
            'qalbu_conversation_id' => $conversation->id,
            'sender' => 'user',
            'message' => $messageText,
            'ai_sources' => null,
            'suggested_actions' => null,
            'ai_response_type' => null,
            'is_helpful' => null,
            'user_feedback' => null,
        ]);

        // Forward to Python service
        $payload = [
            'message' => $messageText,
            'user_emotion' => $conversation->user_emotion,
            'conversation_id' => $conversation->id,
        ];

        try {
            $resp = Http::timeout(15)->post(rtrim($this->chatbotBaseUrl, '/') . '/chat', $payload);
        } catch (\Throwable $e) {
            Log::error('Chatbot service error', [
                'error' => $e->getMessage(),
                'payload' => $payload,
                'url' => $this->chatbotBaseUrl
            ]);
            
            // Save user message even if AI fails
            QalbuMessage::create([
                'qalbu_conversation_id' => $conversation->id,
                'sender' => 'user',
                'message' => $messageText,
                'ai_sources' => null,
                'suggested_actions' => null,
                'ai_response_type' => null,
                'is_helpful' => null,
                'user_feedback' => null,
            ]);
            
            return response()->json([
                'reply' => 'Maaf, layanan chatbot sedang tidak tersedia. Tim kami sedang memperbaikinya. Silakan coba lagi dalam beberapa menit. 🔧',
                'ai_response_type' => 'fallback',
                'ai_sources' => [],
                'meta' => ['reason' => 'service_unavailable'],
                'conversation_id' => $conversation->id,
            ], 200);
        }

        if (!$resp->ok()) {
            Log::error('Python service error', [
                'status' => $resp->status(),
                'body' => $resp->body(),
                'payload' => $payload
            ]);
            
            return response()->json([
                'reply' => 'Mohon maaf, sedang ada gangguan teknis. Silakan coba kirim pesan lagi. 🙏',
                'ai_response_type' => 'fallback',
                'ai_sources' => [],
                'meta' => ['reason' => 'service_error', 'status' => $resp->status()],
                'conversation_id' => $conversation->id,
            ], 200);
        }

        $data = $resp->json();
        $reply = $data['reply'] ?? '';
        $aiResponseType = $data['ai_response_type'] ?? 'text';
        $aiSources = $data['ai_sources'] ?? [];
        $suggestedActions = $data['suggested_actions'] ?? null; // not always provided
        $metaReason = data_get($data, 'meta.reason');

        // Log unanswered questions when low similarity fallback
        if ($metaReason === 'low_similarity') {
            UnansweredQuestion::create([
                'user_id' => $userId,
                'qalbu_conversation_id' => $conversation->id,
                'question' => $messageText,
                'context' => json_encode([
                    'ai_response_type' => $aiResponseType,
                    'ai_sources' => $aiSources,
                ]),
            ]);
        }

        // persist AI message
        QalbuMessage::create([
            'qalbu_conversation_id' => $conversation->id,
            'sender' => 'ai',
            'message' => $reply,
            'ai_sources' => $aiSources ? json_encode($aiSources) : null,
            'suggested_actions' => $suggestedActions ? json_encode($suggestedActions) : null,
            'ai_response_type' => $aiResponseType,
            'is_helpful' => null,
            'user_feedback' => null,
        ]);

        // Log QalbuChat session activity
        event(new UserActivityEvent(
            $userId,
            'qalbuchat_session',
            'Sesi konseling dengan QalbuChat AI',
            [
                'conversation_id' => $conversation->id,
                'message_count' => QalbuMessage::where('qalbu_conversation_id', $conversation->id)->count(),
                'ai_response_type' => $aiResponseType,
                'conversation_type' => $conversation->conversation_type,
                'user_emotion' => $conversation->user_emotion
            ]
        ));

        // update conversation timestamp implicitly by model or here explicitly
        $conversation->touch();

        return response()->json([
            'conversation_id' => $conversation->id,
            'reply' => $reply,
            'ai_response_type' => $aiResponseType,
            'ai_sources' => $aiSources,
            'candidates' => $data['candidates'] ?? [],
            'meta' => $data['meta'] ?? [],
        ], 200);
    }

    public function feedback(Request $request)
    {
        $v = Validator::make($request->all(), [
            'message_id' => 'required|integer',
            'kb_id' => 'nullable|integer',
            'helpful' => 'nullable|boolean',
            'rating' => 'nullable|numeric',
            'comment' => 'nullable|string',
        ]);
        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }

        $message = QalbuMessage::find($request->input('message_id'));
        if (!$message) {
            return response()->json(['error' => 'message not found'], 404);
        }

        $message->is_helpful = $request->boolean('helpful');
        $message->user_feedback = $request->input('comment');
        $message->save();

        // forward to python if kb_id provided
        $kbId = $request->input('kb_id');
        if ($kbId) {
            try {
                Http::timeout(5)->post(rtrim($this->chatbotBaseUrl, '/') . '/feedback', [
                    'kb_id' => (int) $kbId,
                    'helpful' => $request->boolean('helpful'),
                    'rating' => $request->input('rating'),
                    'comment' => $request->input('comment'),
                ]);
            } catch (\Throwable $e) {
                Log::warning('Forward feedback failed', ['error' => $e->getMessage()]);
            }
        }

        return response()->json(['status' => 'ok']);
    }
}


