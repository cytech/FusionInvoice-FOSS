<script type="text/javascript">

    $(function () {

        attachPdf = 0;

        $('#modal-mail-workorder').modal({backdrop: 'static'}).on('shown.bs.modal', function () {
            $('#to').chosen();
            $('#cc').chosen();
            $('#bcc').chosen();
        });

        $('#btn-submit-mail-workorder').click(function () {

            const $btn = $(this).button('loading');

            if ($('#attach_pdf').prop('checked') == true) {
                attachPdf = 1;
            }

            $.post('{{ route('workorderMail.store') }}', {
                workorder_id: {{ $workorderId }},
                to: $('#to').val(),
                cc: $('#cc').val(),
                bcc: $('#bcc').val(),
                subject: $('#subject').val(),
                body: $('#body').val(),
                attach_pdf: attachPdf
            }).done(function (response) {
                $('#modal-status-placeholder').html('<div class="alert alert-success">' + '@lang('bt.sent')' + '</div>');
                setTimeout("window.location='" + decodeURIComponent('{{ $redirectTo }}') + "'", 1000);
            }).fail(function (response) {
                $btn.button('reset');
                showErrors($.parseJSON(response.responseText).errors, '#modal-status-placeholder');
            });
        });
    });

</script>
