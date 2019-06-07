<?php

namespace FI\Observers;

use FI\Modules\TimeTracking\Models\TimeTrackingTask;

class TimeTrackingTaskObserver
{
    /**
     * Handle the time tracking task "creating" event.
     *
     * @param  \FI\Modules\TimeTracking\Models\TimeTrackingTask  $timeTrackingTask
     * @return void
     */
    public function creating(TimeTrackingTask $timeTrackingTask): void
    {
        $maxDisplayOrder = TimeTrackingTask::where('time_tracking_project_id', $timeTrackingTask->time_tracking_project_id)->max('display_order');

        $timeTrackingTask->display_order = $maxDisplayOrder + 1;
    }
}
