<?php

/**
 * This file is part of FusionInvoiceFOSS.
 *
 *
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Addons\TimeTracking\Models;

use FI\Support\NumberFormatter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

class TimeTrackingTask extends Model
{
    protected $table = 'time_tracking_tasks';

    protected $guarded = ['id'];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($task)
        {
            $maxDisplayOrder = self::where('time_tracking_project_id', $task->time_tracking_project_id)->max('display_order');

            $task->display_order = $maxDisplayOrder + 1;
        });

        static::deleted(function ($task)
        {
            Event::fire('timeTracking.task.deleted', [$task]);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function activeTimer()
    {
        return $this->hasOne('Addons\TimeTracking\Models\TimeTrackingTimer')->where('time_tracking_timers.end_at', '0000-00-00');
    }

    public function invoice()
    {
        return $this->belongsTo('FI\Modules\Invoices\Models\Invoice');
    }

    public function project()
    {
        return $this->belongsTo('Addons\TimeTracking\Models\TimeTrackingProject', 'time_tracking_project_id');
    }

    public function timers()
    {
        return $this->hasMany('Addons\TimeTracking\Models\TimeTrackingTimer');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getFormattedHoursAttribute()
    {
        return NumberFormatter::format($this->attributes['hours']);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeGetSelect($query)
    {
        return $query->select(
            'time_tracking_tasks.*',
            DB::raw('(' . $this->getHoursSql() . ') AS hours')
        );
    }

    public function scopeBilled($query)
    {
        return $query->where('billed', 1);
    }

    public function scopeUnbilled($query)
    {
        return $query->where('billed', 0);
    }

    /*
    |--------------------------------------------------------------------------
    | SQL
    |--------------------------------------------------------------------------
    */

    private function getHoursSql()
    {
        return DB::table('time_tracking_timers')
            ->selectRaw('IFNULL(SUM(hours), 0.00)')
            ->where('time_tracking_timers.time_tracking_task_id', '=', DB::raw(DB::getTablePrefix() . 'time_tracking_tasks.id'))
            ->toSql();
    }
}