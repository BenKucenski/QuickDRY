$(document).ready(function() {
		$('#notice_dialog').hide();
});

function _notice_dialog(title)
{
	ShowModal('notice_dialog',title);
}

function NoticeDialog(title, text)
{
	_notice_dialog(title);
	$('#ctl_notice_text').html(text);
}
