// Fix for multiple modals hiding scrollbars
$(document).on('hidden.bs.modal', '.modal', function () {
    $('.modal:visible').length && $(document.body).addClass('modal-open');
});


function chkOnlyEmailIsValid(sEmail) {
    var filter = /^([\w-\.]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([\w-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/;
    return filter.test(sEmail);
}

if (!String.prototype.startsWith) {
    String.prototype.startsWith = function (searchString, position) {
        position = position || 0;
        return this.substr(position, searchString.length) === searchString;
    };
}

function scrollToElement(elem) {
    $('html, body').animate({
        scrollTop: $("#" + elem).offset().top
    }, 0);
}

function money2num(strMoney) {
    if (!strMoney) {
        return "";
    }

    //var newnum = strMoney.replace("$","").replace(",","");
    var newnum = strMoney.replace("$", "").replace(/[',']/gi, '');
    if (!isNaN(newnum))
        return (newnum);
    else
        return "";
}


function num2money(n_value, prefix, elementId, dec, retval) {
    dec = (dec) ? dec : false;
    var oNval = n_value.toString();

    if (typeof(n_value) === "string") {
        n_value = money2num(n_value);
    }


    if (n_value === "") {
        if (elementId != null) {
            var elem = $("#" + elementId);
            if (elem.type === "text") {
                if (retval) {
                    elem.val("");
                }
                else {
                    elem.val(prefix + "0");
                }
            }
            else
                elem.html(prefix + "0");
        }
        return;
    }
    var pre = (!prefix) ? "$" : prefix;

    if (isNaN(Number(n_value)))
        return 'ERROR';

    var b_negative = Boolean(n_value < 0);
    n_value = Math.abs(n_value);

    // ROUND TO 1/100 PRECISION, ADD ENDING ZEROES IF NEEDED
    var roundPt = null;
    if (dec && dec > 2) {
        dec = parseInt(oNval.substr(oNval.indexOf('.')).length - 1);
        var divd = parseInt(eval('1e' + dec));
        roundPt = (Math.round(n_value * divd) % divd > 9) ? (Math.round(n_value * divd) % divd) : ('0' + Math.round(n_value * divd) % divd);
    } else {
        roundPt = (Math.round(n_value * 1e2) % 1e2 > 9) ? (Math.round(n_value * 1e2) % 1e2) : ('0' + Math.round(n_value * 1e2) % 1e2);
    }
    var s_result = String(roundPt + '00').substring(0, dec);
    // SEPARATE ALL ORDERS
    var b_first = true;
    var s_subresult;
    while (n_value >= 1) {
        s_subresult = (n_value >= 1e3 ? '00' : '') + Math.floor(n_value % 1e3);
        s_result = s_subresult.slice(-3) + (b_first ? '.' : ',') + s_result;
        b_first = false;
        n_value = n_value / 1e3;
    }

    // ADD AT LEAST ONE INTEGER DIGIT
    if (b_first)
        s_result = '0.' + s_result;

    // APPLY FORMATTING AND RETURN
    if (!dec) {
        s_result = s_result.substring(0, s_result.indexOf("."));
    }
    if (elementId != null) {
        if ($("#" + elementId).type == "text")
            $("#" + elementId).val(b_negative ? '-' + pre + s_result + '' : pre + s_result);
        else
            $("#" + elementId).html(b_negative ? '-' + pre + s_result + '' : pre + s_result);
    }
    return b_negative
        ? '-' + pre + s_result + ''
        : pre + s_result;
}

function createDialog(id, title, optionObj, closeFunCall) {
    //Intialize Dialog
    var dlg = $("#" + id).dialog({
        modal: true,
        draggable: false,
        resizable: false,
        autoOpen: false,
        title: title,
        close: function (event, ui) {
            if (typeof(closeFunCall) != "undefined") {
                //Create the function call from function name and parameter.
                var funcCall = closeFunCall + "();";
                //Call the function
                eval(funcCall);
            }
        }
    });
    //Change default settings according to options after init
    if (typeof(optionObj) != "undefined" && typeof(optionObj) == "object") {
        $.each(optionObj, function (key, value) {
            $("#" + id).dialog("option", key, value);
        });
    }
    return dlg;
}


var QueryString = function () {
    // This function is anonymous, is executed immediately and
    // the return value is assigned to QueryString!
    var query_string = {};
    var query = window.location.search.substring(1);
    var vars = query.split("&");
    for (var i = 0; i < vars.length; i++) {
        var pair = vars[i].split("=");
        // If first entry with this name
        if (typeof query_string[pair[0]] === "undefined") {
            query_string[pair[0]] = pair[1];
            // If second entry with this name
        } else if (typeof query_string[pair[0]] === "string") {
            query_string[pair[0]] = [query_string[pair[0]], pair[1]];
            // If third or later entry with this name
        } else {
            query_string[pair[0]].push(pair[1]);
        }
    }
    query_string['base_url'] = window.location.href.split('?')[0];
    return query_string;
}();

function _SignOut(data) {
    Post('/api/v2/json.signout', data, function (data) {
        window.location = data.redirect;
    });
}

function SignOut(return_to) {
    ConfirmDialog('Sign Out', 'Are you sure you want to sign out?', 'Sign Out', _SignOut, {return_to: return_to});
}

function CheckForNewMessages() {
    LoadHTML('/inbox/get_summary.json', {}, 'inbox .notifications', null, true);
    setTimeout('CheckForNewMessages();', 30000);
}

function MarkMessageSeen(message_id) {
    Post('/inbox/mark_seen.json', {
        message_id: message_id
    }, 'UpdateNotifications');
}

function _ToggleRecentMessages(data) {
    if (!data.count)
        window.location = '/inbox';
}

function ToggleRecentMessages() {
    if ($('#inbox_list').html() == '')
        LoadHTML('/inbox/get_recent.json', {}, 'inbox_list', '_ToggleRecentMessages', true);
    else
        $('#inbox_list').html('');
}

function trim(stringToTrim) {
    return stringToTrim.replace(/^\s+|\s+$/g, "");
}

function ltrim(stringToTrim) {
    return stringToTrim.replace(/^\s+/, "");
}

function rtrim(stringToTrim) {
    return stringToTrim.replace(/\s+$/, "");
}

//Return a helper with preserved width of cells
var fixHelper = function (e, ui) {
    ui.children().each(function () {
        $(this).width($(this).width());
    });
    return ui;
};

function SetCookie(name, value, expires) {
    if (expires * 1.0 == 0)
        expires = 365;

    $.cookie(name, value, {
        expires: expires,
        path: '/',
        domain: DOMAIN
    });
}

function ClearCookie(name) {
    $.cookie(name, null, {
        expires: -1,
        path: '/',
        domain: DOMAIN
    });
}

function GetCookie(name) {
    return $.cookie(name);
}


function ClearForm(form_id, clear_hidden) {
    $('#' + form_id).each(function () {
        this.reset();
    });
    $('#' + form_id + ' input[type=checkbox]').each(function () {
        this.checked = false;
    });

    // reset doesn't clear out hidden fields, so this has to be done separately
    if (typeof(clear_hidden) == "undefined" || clear_hidden)
        $('#' + form_id + ' input[type=hidden]').each(function () {
            $(this).val('');
        });
}

var _last_tab = 0;

function ShowSideTab(id) {
    if (_last_tab != id) {
        $('#side_tab_' + _last_tab).removeClass('active');
        $('#side_content_' + _last_tab).hide();
    }
    $('#side_tab_' + id).addClass('active');
    $('#side_content_' + id).show();

    _last_tab = id;

    SetBack();
}


var mouse_x;
var mouse_y;

$(document).ready(function () {
    InitDatePickers();

    $(document).mousemove(function (e) {
        mouse_x = e.pageX;
        mouse_y = e.pageY;
    });

    $(".window_height").each(function () {
        var e = $(this);
        e.css('height', $(window).height() - e.offset().top - 10);
    });

    $(window).resize(function () {
        $(".window_height").each(function () {
            var e = $(this);
            e.css('height', $(window).height() - e.offset().top - 10);
        });
    });

    // http://manos.malihu.gr/jquery-custom-content-scroller
    if ($('#scrolling_list').length > 0) {
        $("#scrolling_list").mCustomScrollbar({});
        $("#scrolling_list").mCustomScrollbar("scrollTo", '#default_scroll_location');
    }

    $(".dropdown dt a").click(function () {
        $(".dropdown dd ul").toggle();
    });

    $(".dropdown dd ul li a").click(function () {
        $(".dropdown dd ul").hide();
    });

    $(document).bind('click', function (e) {
        var $clicked = $(e.target);
        if (!$clicked.parents().hasClass("dropdown"))
            $(".dropdown dd ul").hide();
    });

    // don't use  data-toggle="tab"
    var hash = window.location.hash;
    hash && $('ul.nav a[href="' + hash + '"]').tab('show');

    $('.nav-tabs a').click(function (e) {
        $(this).tab('show');
        var scrollmem = $('body').scrollTop() || $('html').scrollTop();
        window.location.hash = this.hash;
        $('html,body').scrollTop(scrollmem);
    });

    $('.nav-pills a').click(function (e) {
        $(this).tab('show');
        var scrollmem = $('body').scrollTop() || $('html').scrollTop();
        window.location.hash = this.hash;
        $('html,body').scrollTop(scrollmem);
    });

    //setTimeout('CheckTimeout();',5000);

});

function _CheckTimeout(data) {
    switch (data.timeout_code) {
        case 1:
            CloseDialogIfOpen('notice_dialog');
            NoticeDialog('Warning', 'Your session is about to expire.');

            break;
        case 2:
            window.location = '/logout';
            break;
    }
    setTimeout('CheckTimeout();', 5000);
}

function CheckTimeout() {
    Post('/check_timeout.json', {}, '_CheckTimeout');
}

function twoDigits(d) {
    if (0 <= d && d < 10) return "0" + d.toString();
    if (-10 < d && d < 0) return "-0" + (-1 * d).toString();
    return d.toString();
}

/**
 *
 * @returns {string}
 * @constructor
 */
function Timestamp() {
    var d = new Date();
    return twoDigits(1 + d.getMonth()) + "/" + twoDigits(d.getDate()) + "/" + d.getFullYear() + " " + twoDigits(d.getHours()) + ":" + twoDigits(d.getMinutes()) + ":00";
}

// http://stackoverflow.com/questions/19305821/multiple-modals-overlay
$(document).on('show.bs.modal', '.modal', function () {
    var zIndex = 1040 + (10 * $('.modal:visible').length);
    $(this).css('z-index', zIndex);
    setTimeout(function () {
        $('.modal-backdrop').not('.modal-stack').css('z-index', zIndex - 1).addClass('modal-stack');
    }, 0);
});

function AutoComplete(elem_id, form_id, source_url, select_function) {
    $('#' + elem_id).autocomplete({
        source: source_url,
        minLength: 1,
        html: true,
        select: select_function
    });
    if (form_id) {
        $('#' + elem_id).autocomplete("option", "appendTo", "#" + form_id);
    }
}


function LookupObject(url, input_elem_id, output_elem_id, form_id) {
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
}


function InitDatePickers() {
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

    $('.motrigger').mouseover(function (event) {
        createTooltip(event, $('.motooltip', this).html(), this);
    }).mouseout(function () {
        // create a hidefunction on the callback if you want
        $('.modisptooltip').remove();
    });

    $(".report").floatHeader({fadeOut: 0, fadeIn: 0});


    $('.action_menu').dropit();
}

function CloseDialogIfOpen(dialog_id) {
    if (DialogIsOpen(dialog_id)) {
        if ($("#" + dialog_id).hasClass("ui-dialog-content")) {
            $('#' + dialog_id).dialog('close');
        } else {
            $('#' + dialog_id).modal('hide');
        }
    }
}

/**
 *
 * @param dialog_id
 * @returns {*}
 * @constructor
 */
function DialogIsOpen(dialog_id) {

    var elem = $("#" + dialog_id);
    if (elem.hasClass('in')) {
        return true;
    }

    return elem.hasClass("ui-dialog-content") && elem.dialog("isOpen") === true;
}

function Post(url, vars, callback, error_callback, dialog) {
    $.ajax({
        method: "POST",
        url: url,
        data: vars,
        dataType: "json",
        async: true,
        success: function (data) {
            CloseDialogIfOpen('wait_dialog');
            if (data.error) {
                if (typeof(error_callback) === "function") {
                    error_callback(data);
                } else {
                    NoticeDialog('Error', data.error);
                }
            } else {
                if (data.success && $.n) {
                    $.n.success(data.success);
                }

                if (dialog) {
                    CloseDialogIfOpen(dialog);
                }

                if (typeof(callback) == "function") {
                    callback(data);
                }
            }
        }
    });
}

//http://stackoverflow.com/questions/280634/endswith-in-javascript
function endsWith(str, suffix) {
    return str.indexOf(suffix, str.length - suffix.length) !== -1;
}

/**
 *
 * @param type
 * @param vars
 * @param callback
 * @param dialog
 * @returns {boolean}
 * @constructor
 */
function SaveObject(type, vars, callback, dialog) {
    var is_safe = true;

    eval('if(typeof(_' + dialog + '_active) != "undefined") { if(_' + dialog + '_active == false) is_safe = false; else _' + dialog + '_active = false; }');

    if (!is_safe)
        return false;

    if (!DialogIsOpen('wait_dialog')) {
        WaitDialog('Please Wait', 'Saving...');
    }

    var url = endsWith(type, '.json') ? type : '/json/_' + type + '/save.json';
    $.post(url, vars, function (data) {
        CloseDialogIfOpen('wait_dialog');
        if (data.error) {
            NoticeDialog('Error', data.error);
        }
        else {
            if (data.success && $.n) {
                $.n.success(data.success);
            }
            if (dialog)
                CloseDialogIfOpen(dialog);
            if (typeof(callback) == "function")
                callback(data);
        }
        eval('if(typeof(_' + dialog + '_active) != "undefined") _' + dialog + '_active = true;');
    }, "json");
}

function GetObject(type, vars, callback) {
    var url = endsWith(type, '.json') ? type : '/json/_' + type + '/get.json';
    $.post(url, vars, function (data) {
        CloseDialogIfOpen('wait_dialog');
        if (data.error)
            NoticeDialog('Error', data.error);
        else {
            if (typeof(callback) == "function")
                callback(data);
        }
    }, "json");
}

function SelectObject(type, vars, elem_id, callback) {
    var url = endsWith(type, '.json') ? type : '/json/_' + type + '/select.json';
    $.post(url, vars, function (data) {
        CloseDialogIfOpen('wait_dialog');
        if (data.error)
            NoticeDialog('Error', data.error);
        else {
            if (typeof ($('#' + elem_id)) == 'object')
                $('#' + elem_id).replaceWith(data.html);
            if (typeof(callback) == "function")
                callback(data);
        }
    }, "json");
}

function ConfirmDeleteObject(object_type, object_name, vars, document_number, callback, dialog) {
    var msg = 'You are about to delete ' + object_name;
    if (document_number)
        msg += ' ' + document_number;
    msg += '. Are you sure?';

    ConfirmDialogControl.Load('Delete ' + object_name, msg,
        'Delete', ConfirmDeleteObjectCallback, {
            object_type: object_type,
            vars: vars,
            callback: callback,
            dialog: dialog
        });
}

function ConfirmDeleteObjectCallback(data) {
    DeleteObject(data.object_type, data.vars, data.callback, true, data.dialog);
}

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
function DeleteObject(type, vars, callback, confirmed, dialog) {
    if (confirmed !== true && !confirm('Are you sure?')) {
        return false;
    }


    WaitDialog('Please Wait', 'Deleting...');

    var url = endsWith(type, '.json') ? type : '/json/_' + type + '/delete.json';
    Post(url, vars, callback, null, dialog);
}

function LoadHTML(url, vars, elem_id, callback, dontinit) {
    $.post(url, vars, function (data) {
        if (data.error !== undefined)
            NoticeDialog('Error', data.error);
        else {
            $('#' + elem_id).html(data.html);
            if (!dontinit)
                InitDatePickers();
            if (typeof(callback) === "function")
                callback(data);
        }
        data = null;
        CloseDialogIfOpen('wait_dialog');
    }, "json");
}

function ReplaceHTML(url, vars, elem_id, callback) {
    $.post(url, vars, function (data) {
        if (data.error !== undefined)
            NoticeDialog('Error', data.error);
        else {
            $('#' + elem_id).replaceWith(data.html);
            InitDatePickers();
            if (typeof(callback) === "function")
                callback(data);
        }
    }, "json");
}

function AppendHTML(url, vars, elem_id, callback) {
    $.post(url, vars, function (data) {
        if (data.error != undefined)
            NoticeDialog('Error', data.error);
        else {
            $('#' + elem_id).append(data.html);
            if (typeof(callback) == "function")
                callback(data);
            InitDatePickers();
        }
    }, "json");
}

function AddTableRow(url, vars, elem_id, callback) {
    $.post(url, vars, function (data) {
        if (data.error != undefined)
            NoticeDialog('Error', data.error);
        else {
            $('#' + elem_id + ' > tbody:last').append(data.html);
            if (typeof(callback) == "function")
                callback(data);
            InitDatePickers();
        }
    }, "json");
}

function LoadForm(data, elem_id) {
    for (var i in data.serialized)
        if (typeof ($('#' + elem_id + '_' + i)) == 'object') {
            if ($('#' + elem_id + '_' + i).prop("type") == 'checkbox') {
                if (data.serialized[i] == 1)
                    $('#' + elem_id + '_' + i).prop('checked', true);
                else
                    $('#' + elem_id + '_' + i).prop('checked', false);

                if (typeof ($('#' + elem_id + '_' + i + '_cur')) == 'object') {
                    if (data.serialized[i] == 1)
                        $('#' + elem_id + '_' + i + '_cur').html('Yes');
                    else
                        $('#' + elem_id + '_' + i + '_cur').html('No');
                }
            }
            else {
                if ($('#' + elem_id + '_' + i).hasClass('date-picker')) {
                    if (data.serialized[i])
                        if (data.serialized[i].length > 7) {
                            var t = data.serialized[i];
                            t = t.split(' ');
                            t = t[0];
                            t = t.split('-');
                            if (t[1] == undefined)
                                $('#' + elem_id + '_' + i).val(t);
                            else
                                $('#' + elem_id + '_' + i).val(t[1] + '/' + t[2] + '/' + t[0]);
                            if (typeof ($('#' + elem_id + '_' + i + '_cur')) == 'object') {
                                $('#' + elem_id + '_' + i + '_cur').html(t[1] + '/' + t[2] + '/' + t[0]);
                            }
                        }
                }
                else {
                    $('#' + elem_id + '_' + i).val(data.serialized[i]);
                    if (typeof ($('#' + elem_id + '_' + i + '_cur')) == 'object') {
                        $('#' + elem_id + '_' + i + '_cur').html(data.serialized[i]);
                    }
                }
            }
        }
    InitDatePickers();
}

function DelayedReloadPage(seconds) {
    setTimeout('ReloadPage();', seconds * 1000);
}

function ReloadPage(title, text) {
    if (typeof (text) == "undefined")
        title = "Reloading Page";
    if (typeof (title) == "undefined")
        text = "Please Wait";

    WaitDialog(title, text);
    setTimeout(function () {
        window.location.reload(true);
    }, 1000);
}

function RedirectPage(url, title, text) {
    if (typeof (text) == "undefined")
        title = "Reloading Page";
    if (typeof (title) == "undefined")
        text = "Please Wait";

    WaitDialog(title, text);
    window.location = url;
}

function ShowModal(elem_id, title) {
    eval('if(typeof(_' + elem_id + '_active) != "undefined") _' + elem_id + '_active = true;');

    $('#' + elem_id + '_title').html(title);

    $('#' + elem_id).modal('show');
    //$('#' + elem_id).disableSelection();
}

function CheckAll(elem, elem_class) {
    $('.' + elem_class).prop('checked', elem.checked);
}

function NewTab(url) {
    window.open(url, '_blank');
}

jQuery.fn.exists = function () {
    return this.length > 0;
};

function createPlaceHolder() {
    if (!Modernizr.input.placeholder) {
        $('[placeholder]').focus(function () {
            var i = $(this);
            if (i.val() == i.attr('placeholder')) {
                i.val('').removeClass('placeholder');
            }
        }).blur(function () {
            var i = $(this);
            if (i.val() == '' || i.val() == i.attr('placeholder')) {
                i.addClass('placeholder').val(i.attr('placeholder'));
            }
        }).blur().parents('form').submit(function () {
            $(this).find('[placeholder]').each(function () {
                var i = $(this);
                if (i.val() == i.attr('placeholder'))
                    i.val('');
            })
        });
    }
}

function is_mobile() {
    var check = false;
    (function (a) {
        if (/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i.test(a) || /1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i.test(a.substr(0, 4))) check = true
    })(navigator.userAgent || navigator.vendor || window.opera);
    return check;
}

/**
 * Function to clear all the elements of form.
 * @param {String} id of the <form> tag
 **/
function clearForm(id) {
    $("#" + id).find(':input').each(function () {
        switch (this.type) {
            case 'password':
            case 'select-multiple':
            case 'select-one':
            case 'text':
            case 'textarea':
                $(this).val('');
                break;
            case 'checkbox':
            case 'radio':
                $(this).prop('checked', false);
        }
    });
}

/**
 * @fileoverview This javascript file contains the common functions.
 */
var now = new Date();
var timeZone = now.getTimezoneOffset();

var urlParams = {};
(function () {
    var e,
        a = /\+/g,  // Regex for replacing addition symbol with a space
        r = /([^&=]+)=?([^&]*)/g,
        d = function (s) {
            return decodeURIComponent(s.replace(a, " "));
        },
        q = window.location.search.substring(1);

    while (e = r.exec(q))
        urlParams[d(e[1])] = d(e[2]);
})();


/**
 * Function to track page url for Google Analytics
 **/
function trackPageUrlForGA(pageUrl) {
    /* commented as it was 8z request.
    if(typeof _gaq != 'undefined' )
        _gaq.push(['_trackPageview', '/goal/'+pageUrl]);
    */
}

function getUrlParams() {
    var params = {};
    window.location.search.replace(/[?&]+([^=&]+)=([^&]*)/gi, function (str, key, value) {
        params[key] = value;
    });

    return params;
}


function formatNumber(number) {
    var results = '';
    var numbers = number.replace(/\D/g, '');
    var char = {0: '(', 3: ') ', 6: '-'};
    this.value = '';
    for (var i = 0; i < numbers.length; i++) {
        results += (char[i] || '') + numbers[i];
    }

    return results;
}

function InitTinyMCE(elem_id) {
    tinymce.init({
        selector: '#' + elem_id,
        height: 500,
        theme: 'modern',
        plugins: [
            'advlist autolink lists link image charmap print preview hr anchor pagebreak',
            'searchreplace wordcount visualblocks visualchars code fullscreen',
            'insertdatetime media nonbreaking save table contextmenu directionality',
            'emoticons template paste textcolor colorpicker textpattern imagetools'
        ],
        toolbar1: 'insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image',
        toolbar2: 'print preview media | forecolor backcolor emoticons',
        image_advtab: true,
        templates: [],
        content_css: [
            '//fast.fonts.net/cssapi/e6dc9b99-64fe-4292-ad98-6974f93cd2a2.css',
            '//www.tinymce.com/css/codepen.min.css'
        ]
    });
}

// https://stackoverflow.com/questions/5619202/converting-string-to-date-in-js
function stringToDate(_date, _format, _delimiter) {
    //stringToDate("17/9/2014","dd/MM/yyyy","/");
    //stringToDate("9/17/2014","mm/dd/yyyy","/");
    //stringToDate("9-17-2014","mm-dd-yyyy","-");

    var formatLowerCase = _format.toLowerCase();
    var formatItems = formatLowerCase.split(_delimiter);
    var dateItems = _date.split(_delimiter);
    var monthIndex = formatItems.indexOf("mm");
    var dayIndex = formatItems.indexOf("dd");
    var yearIndex = formatItems.indexOf("yyyy");
    var month = parseInt(dateItems[monthIndex]);
    month -= 1;
    return new Date(dateItems[yearIndex], month, dateItems[dayIndex]);
}

/**
 *
 * @param date
 * @returns {string}
 * @constructor
 */
function DateToSQLDate(date) {
    return date.toLocaleString('en-US', {year: 'numeric', month: 'numeric', day: 'numeric'});
}