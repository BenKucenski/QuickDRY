<script type="text/javascript" src="/QuickDRY/controls/ctl_confirm.js"></script>

<div class="modal fade" id="cc_dialog" style="display: none;" tabindex="-1" role="dialog" aria-labelledby="joinModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h3 class="modal-title" id="cc_dialog_title"></h3>
            </div>
            <div class="modal-body">

            <h3><span id="cc_text"></span></h3>

            </div>
            <div class="modal-footer">
                <span style="white-space: nowrap;">

                <button type="button" class="btn btn-primary cc_action_button" onclick="ConfirmDialogControl.Confirm();">Yes</button>
                <button type="button" class="btn btn-default cc_cancel_button" data-dismiss="modal">Cancel</button>
                </span>
            </div>
        </div>
    </div>
</div>