var QuickDRY = {
    Create: function (type, vars, callback, dialog) {
        if (dialog) {
            var modalClass = dialog.replace('_dialog', '');
        }

        if (!QuickDRY.DialogIsOpen('wait_dialog')) {
            WaitDialog('Please Wait', 'Saving...');
        }

        vars.verb = 'PUT';
        $.post('/json/' + type, vars, function (data) {
            QuickDRY.CloseDialogIfOpen('wait_dialog');
            if (data.error) {
                NoticeDialog('Error', data.error);
                if (modalClass) {
                    eval(modalClass + '._active = true;');
                }
            }
            else {
                if (data.success && $.n) {
                    $.n.success(data.success);
                }
                if (dialog) {
                    QuickDRY.CloseDialogIfOpen(dialog);
                }
                if (typeof(callback) == "function") {
                    callback(data);
                }
            }
        }, "json");
    },

    Read: function (type, vars, callback) {
        vars.verb = 'GET';
        HTTP.Post('/json/' + type, vars, function (data) {
            QuickDRY.CloseDialogIfOpen('wait_dialog');
            if (data.error) {
                NoticeDialog('Error', data.error);
            } else {
                if (typeof(callback) == "function") {
                    callback(data);
                }
            }
        }, "json");
    },

    Update: function (type, vars, callback, dialog) {
        if (dialog) {
            var modalClass = dialog.replace('_dialog', '');
        }

        if (!QuickDRY.DialogIsOpen('wait_dialog')) {
            WaitDialog('Please Wait', 'Saving...');
        }

        vars.verb = 'POST';
        $.post('/json/' + type, vars, function (data) {
            QuickDRY.CloseDialogIfOpen('wait_dialog');
            if (data.error) {
                NoticeDialog('Error', data.error);
                if (modalClass) {
                    eval(modalClass + '._active = true;');
                }
            }
            else {
                if (data.success && $.n) {
                    $.n.success(data.success);
                }
                if (dialog) {
                    QuickDRY.CloseDialogIfOpen(dialog);
                }
                if (typeof(callback) == "function") {
                    callback(data);
                }
            }
        }, "json");
    },

    ConfirmDelete: function (object_type, object_name, vars, document_number, callback, dialog) {
        var msg = 'You are about to delete ' + object_name;
        if (document_number) {
            msg += ' ' + document_number;
        }
        msg += '. Are you sure?';

        ConfirmDialogControl.Load('Delete ' + object_name, msg,
            'Delete', QuickDRY.ConfirmDeleteCallback, {
                object_type: object_type,
                vars: vars,
                callback: callback,
                dialog: dialog
            });
    },

    ConfirmDeleteCallback: function (data) {
        QuickDRY.Delete(data.object_type, data.vars, data.callback, true, data.dialog);
    },

    /**
     *
     * @param type
     * @param vars
     * @param callback
     * @param confirmed
     * @param dialog
     * @returns {boolean}
     * @constructor
     */
    Delete: function (type, vars, callback, confirmed, dialog) {
        if (confirmed !== true && !confirm('Are you sure?')) {
            return false;
        }

        WaitDialog('Please Wait', 'Deleting...', function () {
            vars.verb = 'DELETE';
            HTTP.Post('/json/' + type, vars, callback, null, dialog);
        });
    },

    LoadForm: function (data, elem_id) {
        for (var i in data.data) {
            var elem = $('#' + elem_id + '_' + i);
            var elem_cur = $('#' + elem_id + '_' + i + '_cur');
            if (typeof (elem) == 'object') {
                if (elem.prop("type") == 'checkbox') {
                    if (data.data[i] == 1) {
                        elem.prop('checked', true);
                    } else {
                        elem.prop('checked', false);
                    }

                    if (typeof (elem_cur) == 'object') {
                        if (data.data[i] == 1) {
                            elem_cur.html('Yes');
                        } else {
                            elem_cur.html('No');
                        }
                    }
                } else {
                    if (elem.hasClass('date-picker')) {
                        if (data.data[i])
                            if (data.data[i].length > 7) {
                                var t = data.data[i];
                                t = t.split(' ');
                                t = t[0];
                                t = t.split('-');
                                if (t[1] == undefined)
                                    elem.val(t);
                                else
                                    elem.val(t[1] + '/' + t[2] + '/' + t[0]);
                                if (typeof ($('#' + elem_id + '_' + i + '_cur')) == 'object') {
                                    elem_cur.html(t[1] + '/' + t[2] + '/' + t[0]);
                                }
                            }
                    } else {
                        elem.val(data.data[i]);
                        if (typeof (elem_cur) == 'object') {
                            elem_cur.html(data.data[i]);
                        }
                    }
                }
            }
        }
        QuickDRY.InitDatePickers();
    },

    InitDatePickers: function () {
        $('.date-picker').prop('autocomplete','off');

        $('.date-picker').datepicker({
            format: 'mm/dd/yyyy',
            startDate: '01/01/1900',
            endDate: '12/31/2100',
            todayHighlight: true,
            autoclose: true
        }).on('focus', function (e) {
            var t = $(this).datepicker.isOpen;
            if (!t) {
                $(this).datepicker('update', $(this).val());
            }
            $(this).datepicker.isOpen = true;

        }).on('hide', function (e) {
            $(this).datepicker.isOpen = false;
        });

        $(".time-picker").datetimepicker({});

        $('.timeonly-picker').datetimepicker({
            format: 'LT'
        });
    },
    ClearForm: function (form_id, clear_hidden) {
        $('#' + form_id).each(function () {
            this.reset();
        });
        $('#' + form_id + ' input[type=checkbox]').each(function () {
            this.checked = false;
        });

        // reset doesn't clear out hidden fields, so this has to be done separately
        if (typeof(clear_hidden) === "undefined" || clear_hidden)
            $('#' + form_id + ' input[type=hidden]').each(function () {
                $(this).val('');
            });
    },
    CloseDialogIfOpen: function (dialog_id) {
        if (QuickDRY.DialogIsOpen(dialog_id)) {
            if ($("#" + dialog_id).hasClass("ui-dialog-content")) {
                $('#' + dialog_id).dialog('close');
            } else {
                $('#' + dialog_id).modal('hide');
            }
        }
    },
    /**
     *
     * @param dialog_id
     * @returns {*}
     * @constructor
     */
    DialogIsOpen: function (dialog_id) {

        var elem = $("#" + dialog_id);
        if (elem.hasClass('in')) {
            return true;
        }

        return elem.hasClass("ui-dialog-content") && elem.dialog("isOpen") === true;
    },
    ShowModal: function (elem_id, title) {
        $('#' + elem_id + '_title').html(title);

        $('#' + elem_id).modal('show');
        //$('#' + elem_id).disableSelection();
    },
    ReloadPage: function (title, text) {
        if (typeof (text) == "undefined")
            title = "Reloading Page";
        if (typeof (title) == "undefined")
            text = "Please Wait";

        WaitDialog(title, text);
        setTimeout(function () {
            window.location.reload(true);
        }, 1000);
    },
    AutoComplete: function (elem_id, form_id, source_url, select_function) {
        $('#' + elem_id).autocomplete({
            source: source_url,
            minLength: 1,
            html: true,
            select: select_function
        });
        if (form_id) {
            $('#' + elem_id).autocomplete("option", "appendTo", "#" + form_id);
        }
    },


    LookupObject: function (url, input_elem_id, output_elem_id, form_id) {
        $('#' + input_elem_id).autocomplete({
            source: function (request, response) {
                $.ajax({
                    url: url,
                    dataType: 'json',
                    data: {term: request.term},
                    success: function (data) {
                        response(data);
                    }
                });
            },
            select: function (event, ui) {
                $('#' + output_elem_id).val(ui.item.id);
                $(this).val(ui.item.display);
                return false;
            }
        }).data("ui-autocomplete")._renderItem = function (ul, item) {
            return $("<li>")
                .data("ui-autocomplete-item", item)
                .append("" + item.display + "")
                .appendTo(ul);
        };
        if (form_id) {
            $('#' + input_elem_id).autocomplete("option", "appendTo", "#" + form_id);
        }
    },
    LoadHTML: function (url, vars, elem_id, callback, dontinit) {
        $.post(url, vars, function (data) {
            if (data.error !== undefined)
                NoticeDialog('Error', data.error);
            else {
                $('#' + elem_id).html(data.html);
                if (!dontinit)
                    QuickDRY.InitDatePickers();
                if (typeof(callback) === "function")
                    callback(data);
            }
            data = null;
            QuickDRY.CloseDialogIfOpen('wait_dialog');
        }, "json");
    },

    ReplaceHTML: function (url, vars, elem_id, callback) {
        $.post(url, vars, function (data) {
            if (data.error !== undefined)
                NoticeDialog('Error', data.error);
            else {
                $('#' + elem_id).replaceWith(data.html);
                QuickDRY.InitDatePickers();
                if (typeof(callback) === "function")
                    callback(data);
            }
        }, "json");
    },

    AppendHTML: function (url, vars, elem_id, callback) {
        $.post(url, vars, function (data) {
            if (data.error != undefined)
                NoticeDialog('Error', data.error);
            else {
                $('#' + elem_id).append(data.html);
                if (typeof(callback) == "function")
                    callback(data);
                QuickDRY.InitDatePickers();
            }
        }, "json");
    },

    AddTableRow: function (url, vars, elem_id, callback) {
        $.post(url, vars, function (data) {
            if (data.error != undefined)
                NoticeDialog('Error', data.error);
            else {
                $('#' + elem_id + ' > tbody:last').append(data.html);
                if (typeof(callback) == "function")
                    callback(data);
                QuickDRY.InitDatePickers();
            }
        }, "json");
    }
};

// Fix for multiple modals hiding scrollbars
$(document).on('hidden.bs.modal', '.modal', function () {
    $('.modal:visible').length && $(document.body).addClass('modal-open');
});

// http://stackoverflow.com/questions/19305821/multiple-modals-overlay
$(document).on('show.bs.modal', '.modal', function () {
    var zIndex = 1040 + (10 * $('.modal:visible').length);
    $(this).css('z-index', zIndex);
    setTimeout(function () {
        $('.modal-backdrop').not('.modal-stack').css('z-index', zIndex - 1).addClass('modal-stack');
    }, 0);
});

$(document).ready(function () {
    QuickDRY.InitDatePickers();
});