<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserActivityEvent
{
    use Dispatchable, SerializesModels;

    public $activityType;
    public $activityData;

    public function __construct($activityType, $activityData = null)
    {
        $this->activityType = $activityType;
        $this->activityData = $activityData;
    }
}