var current_tab = window.location.hash.replace('#','');

function ShowTab(tab) {
    var tab = typeof tab !== "undefined" ? tab : current_tab;

    if(typeof tab === "undefined" || !tab) {
        tab = '0';
    }

    if (typeof current_tab !== "undefined" && current_tab > -1 && current_tab !== tab) {
        $('#tab_' + current_tab).addClass('tab');
        $('#tab_' + current_tab).removeClass('tab_selected');
        $('#tab_' + current_tab).removeClass('active');
        $('#tab_s_' + current_tab).hide();
    }
    $('#tab_' + tab).addClass('tab_selected');
    $('#tab_' + tab).addClass('active');
    $('#tab_' + tab).removeClass('tab');
    $('#tab_s_' + tab).show();

    current_tab = tab;
    if(current_tab) {
        window.location.hash = '#' + current_tab;
    }

    SetBack();
}

function SetBack() {
    var url = QueryString.base_url;
    var params = [];
    for (var param in QueryString) {
        if (param == 'base_url') {
            continue;
        }

        if (param == 'current_tab') {
        } else if (param == 'current_side_tab') {
        } else {
            params.push(param + '=' + QueryString[param]);
        }
    }
    params.push('current_tab=' + Cookies.Get('current_tab'));
    params.push('current_side_tab=' + Cookies.Get('current_side_tab'));

    Cookies.Set('back', url + '?' + params.join('&'));
}

var _last_tab = 0;

function ShowSideTab(id) {
    if (_last_tab !== id) {
        $('#side_tab_' + _last_tab).removeClass('active');
        $('#side_content_' + _last_tab).hide();
    }
    $('#side_tab_' + id).addClass('active');
    $('#side_content_' + id).show();

    _last_tab = id;

    SetBack();
}
