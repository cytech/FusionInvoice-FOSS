@include('time_tracking._task_list_refresh_js')
@include('time_tracking._project_edit_totals_refresh')
@include('layouts._daterangetimepicker')

<script type="text/javascript">
    $(function () {
        $('#modal-show-timers').modal();

        $('#date_time_range').on('apply.daterangepicker', function(ev, picker) {
            $.post('{{ route('timeTracking.timers.store') }}', {
                time_tracking_task_id: {{ $task->id }},
                start_at: $('#from_date_time').val(),
                end_at: $('#to_date_time').val()
            }).done(function (response) {
                refreshTaskList();
                refreshTimerList();
                refreshTotals();
            }).fail(function (response) {
                showErrors($.parseJSON(response.responseText).errors, '#modal-status-placeholder');
            });
        });

        $('#task-timer-list').on('click', '.btn-delete-timer', function() {
            if (!confirm('{!! trans('fi.delete_record_warning') !!}')) return false;
            $.post('{{ route('timeTracking.timers.delete') }}', {
                id: $(this).data('timer-id')
            }).done(function () {
                refreshTaskList();
                refreshTimerList();
                refreshTotals();
            })
        });

        function refreshTimerList() {
            $('#task-timer-list').load('{{ route('timeTracking.timers.refreshList') }}', {
                time_tracking_task_id: {{ $task->id }}
            });
        }
    })
</script>

<div class="modal fade" id="modal-show-timers">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title">{{ trans('TimeTracking::lang.timers') }}</h4>
            </div>
            <div class="modal-body">

                <div id="modal-status-placeholder"></div>

                <div class="row">
                    <div class="col-md-6">
                        <label>{{ trans('TimeTracking::lang.add_timer') }}</label>
                        <div class="input-group">
                            {!! Form::text('date_time_range', null, ['id' => 'date_time_range', 'class' => 'form-control', 'readonly' => 'readonly']) !!}
                            <span class="input-group-addon open-daterangetimepicker">
                                <i class="fa fa-calendar"></i>
                            </span>
                        </div>
                        {!! Form::hidden('from_date_time', null, ['id' => 'from_date_time']) !!}
                        {!! Form::hidden('to_date_time', null, ['id' => 'to_date_time']) !!}
                    </div>
                </div>

                <div id="task-timer-list">
                    @include('time_tracking._timer_list')
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('TimeTracking::lang.close') }}</button>
            </div>
        </div>
    </div>
</div>