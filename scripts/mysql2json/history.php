<?php
$add = '<script src="/pages/json/_' . $c_name . '/controls/history.js"></script>

<div class="modal fade" id="' . $c_name . '_dialog" style="display: none;" tabindex="-1" role="dialog" aria-labelledby="joinModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h3 class="modal-title" id="' . $c_name . '_dialog_title"></h3>
            </div>
            <div class="modal-body">
                <div id="' . $c_name . '_history_div" style="height: 400px; overflow-y: auto;"></div>
            </div>
            <div class="modal-footer">
                <span style="white-space: nowrap;">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </span>
            </div>
        </div>
    </div>
</div>
';

$fp = fopen('json/_' . $c_name . '/controls/history.php','w');
fwrite($fp,$add);
fclose($fp);


$add = '
function _' . $c_name . '_history_dialog(title) {
	ShowModal(\'' . $c_name . '_history_dialog\', title);
}

function _' . $c_name . 'HistoryDialog(data) {
	if (data.error)
		NoticeDialog(\'Error\',data.error);
	else {
		$(\'#' . $c_name . '_history_div\').html(data.html);
		_' . $c_name . '_history_dialog(\'' . CapsToSpaces(str_replace('Class','',$c_name)) . ' History\');
	}
}

function ' . $c_name . 'HistoryDialog(uuid) {
	$(\'#' . $c_name . '_history_div\').html(\'\');
	if (typeof (uuid) == \'undefined\' || !uuid) {
		return;
	} else {
		Post(\'/json/_' . $c_name . '/history.json\', {
			uuid : uuid
		}, \'_' . $c_name . 'HistoryDialog\');
	}
}
';
$fp = fopen('json/_' . $c_name . '/controls/history.js','w');
fwrite($fp,$add);
fclose($fp);