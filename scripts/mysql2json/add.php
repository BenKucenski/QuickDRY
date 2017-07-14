<?php
if(sizeof($cols) == 0) {
	Halt($cols);
}

$props = '';
$primary = '';
foreach($cols as $col)
{

}

$sql = "
		SELECT
				table_name,
				column_name,
				referenced_table_name,
				referenced_column_name
		FROM
				info_schema.key_column_usage
		WHERE
				referenced_table_schema = '" . $database . "'
				AND table_name = '" . $table_name . "'
		  		AND referenced_table_name IS NOT NULL
		ORDER BY column_name
	";

$res = MySQL_A::query($sql);
$refs = array();

foreach($res['data'] as $fk)
{
	$refs[$fk['column_name']] = SQL_Base::TableToClass($database, $fk['referenced_table_name']);
}

$colors = '';
$colors_set = '';
$hidden = '';

$form = '
<table class="dialog_form">
';
$has_practice_id = false;
foreach($cols as $col) {
	if(strcasecmp($col['Key'],'pri') == 0)
	{
		$hidden .= '<input type="hidden" name="' . $col['Field'] . '" id="' . $c_name . '_' . $col['Field'] . '" />' . "\r\n";
		if($primary == '') {
			$primary = $col['Field'];
		}
	}
if(strcasecmp($col['Key'],'pri') != 0)
{
	if(substr($col['Field'],strlen($col['Field']) - 6,6) === '_by_id')
		continue;
	if(substr($col['Field'],strlen($col['Field']) - 3,3) === '_at')
		continue;
	if(substr($col['Field'],strlen($col['Field']) - 5,5) === '_file')
		continue;

	if(isset($refs[$col['Field']]))
	{
		if($refs[$col['Field']] === 'ColorClass')
		{
			$colors .= '
	$(\'#dlg_care_type_' . $col['Field'] . '_selected\').html(\'Select One...\');
	$(\'#dlg_care_type_' . $col['Field'] . '_selected\').css({\'background-color\' : \'#ffffff\'});
				';

			$color_var = str_replace('_id','',$col['Field']);
			$colors_set .= '
	if(data.serialized.' . $color_var . ') {
		$(\'#dlg_care_type_' . $col['Field'] . '_selected\').html(\'\');
		$(\'#dlg_care_type_' . $col['Field'] . '_selected\').css({\'background-color\' : \'#\' + data.serialized.' . $color_var . '});
	}
				';
		}

		$type = $refs[$col['Field']];
		$form .= '<tr><td class="name">' . FieldToDisplay($col['Field']) . '</td><td class="field"><?php echo ' . $refs[$col['Field']] . '::Select(null, [\'name\'=>\'' . $col['Field'] . '\',\'id\'=>\'' . $c_name . '_' . $col['Field'] . '\']); ?></td></tr>' . "\r\n";

	}
	else
		switch($col['Type'])
		{
			case 'text':
				$form .= '<tr><td class="name">' . FieldToDisplay($col['Field']) . '</td><td class="field"><textarea class="form-control" name="' . $col['Field'] . '" id="' . $c_name . '_' . $col['Field'] . '"></textarea></td></tr>' . "\r\n";
				break;

			case 'tinyint(1)':
				$elem = $c_name . '_' . $col['Field'];

				$form .= '<tr><td class="name">' . FieldToDisplay($col['Field']) . '</td><td class="field">
					<input type="checkbox" id="' . $elem . '" onclick="$(\'#' . $elem . '_hidden\').val(this.checked ? 1 : 0);" />
					<input type="hidden" name="' . $col['Field'] . '" id="' . $elem . '_hidden" value="0" />
					</td></tr>' . "\r\n";
				break;

			case 'datetime':
			case 'timestamp':
				$form .= '<tr><td class="name">' . FieldToDisplay($col['Field']) . '</td><td class="field"><input class="form-control time-picker" type="text" name="' . $col['Field'] . '" id="' . $c_name . '_' . $col['Field'] . '" /></td></tr>' . "\r\n";
				break;

			case 'date':
				$form .= '<tr><td class="name">' . FieldToDisplay($col['Field']) . '</td><td class="field"><input class="form-control date-picker" type="text" name="' . $col['Field'] . '" id="' . $c_name . '_' . $col['Field'] . '" /></td></tr>' . "\r\n";
				break;

			case 'varchar(6)':
				$form .= '<tr><td class="name">' . FieldToDisplay($col['Field']) . '</td><td class="field"><input class="form-control color" type="text" name="' . $col['Field'] . '" id="' . $c_name . '_' . $col['Field'] . '" /></td></tr>' . "\r\n";
				break;

			default:
				$form .= '<tr><td class="name">' . FieldToDisplay($col['Field']) . '</td><td class="field"><input class="form-control" type="text" name="' . $col['Field'] . '" id="' . $c_name . '_' . $col['Field'] . '" /></td></tr>' . "\r\n";
		}
}
}
$form .= '</table>';

$add = '<script src="/pages/json/_' . $c_name . '/controls/add.js"></script>

<div class="modal fade" id="' . $c_name . '_dialog" style="display: none;" tabindex="-1" role="dialog" aria-labelledby="joinModalLabel">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h3 class="modal-title" id="' . $c_name . '_dialog_title"></h3>
            </div>
            <div class="modal-body">

<form id="' . $c_name . '_form">
' . $hidden . '
<input type="hidden" id="' . $c_name . '_document_number" />
' . $form . '

</form>
            </div>
            <div class="modal-footer">
                <span style="white-space: nowrap;">

                <button type="button" class="btn btn-primary" onclick="' . $c_name . '.Save();">Save</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" id="btn_' . $c_name .  '_delete" class="btn btn-danger" onclick="' . $c_name . '.Delete();">Delete</button>
                </span>
            </div>
        </div>
    </div>
</div>
';

$fp = fopen('json/_' . $c_name . '/controls/add.php','w');
fwrite($fp,$add);
fclose($fp);

///////////////////////////////////////////////
///////////////////////////////////////////////
///////////////////////////////////////////////
///////////////////////////////////////////////
///////////////////////////////////////////////
///////////////////////////////////////////////

$js = '


var ' . $c_name .  ' = {
    title: "' . $c_name .  '",
    save_action: "Saving ' . $c_name .  '",
    delete_action: "Deleting ' . $c_name .  '",
    _form: "' . $c_name .  '_form",
    _class: "' . $c_name .  '",
    _dialog: "' . $c_name .  '_dialog",
    _active: false,
    _save_callback: function (data) {
        ReloadPage();
    },
    _delete_callback: function (data) {
        ReloadPage();
    },
    _Load: function (data) {
        ShowModal(this._dialog, this.title);

    },
    Load: function (' . $primary . ', save_callback, delete_callback) {
        ClearForm(this._form);

        $("#" + this._class + "_' . $primary . '").val(' . $primary . ');
        if (' . $primary . ') {
            Post("/json/_" + ' . $c_name .  '._class + "/get.json", {uuid: ' . $primary . '}, function (data) {
                LoadForm(data, ' . $c_name .  '._class);
                if (!data.can_delete) {
                    $("#btn_" + ' . $c_name .  '._class + "_delete").hide();
                } else {
                    $("#btn_" + ' . $c_name .  '._class + "_delete").show();
                }
            });
        } else {
            $("#btn_" + this._class + "_delete").hide();
        }

        this._active = true;
        this._Load();

        if (save_callback) {
            this._save_callback = save_callback;
        }
        if (delete_callback) {
            this._delete_callback = delete_callback;
        }
    },
    Save: function () {
        if (!this._active) {
            return;
        }
        WaitDialog("Please Wait...", this.save_action);
        this._active = false;
        SaveObject(this._class, {serialized: $("#" + this._form).serialize()}, this._save_callback, this._dialog);
    },
    Delete: function (id) {
        if(id) {
            ConfirmDeleteObject(this._class, this.title, {uuid: id}, "", this._delete_callback, this._dialog);
            return false;
        }
        if (!this._active) {
            return;
        }
        this._active = false;

        ConfirmDeleteObject(this._class, this.title, {uuid: $("#" + this._class + "_id").val()}, "", this._delete_callback, this._dialog);
    }
};
';

$fp = fopen('json/_' . $c_name . '/controls/add.js','w');
fwrite($fp,$js);
fclose($fp);

