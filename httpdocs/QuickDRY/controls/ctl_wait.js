function _wait_dialog(title)
{
    ShowModal('wait_dialog', title);
}

function WaitDialog(title, text)
{
	_wait_dialog(title);
	$('#ctl_wait_text').html(text);
}
