<html>

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=10" />
    <meta charset="UTF-8">

    <title><?php echo isset($_META_TITLE) ? $_META_TITLE : META_TITLE; ?></title>

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css"/>

    <link rel="stylesheet" type="text/css" href="/css/jquery.notifications.css" />
    <link rel="stylesheet" type="text/css" href="/css/jquery.fileupload-ui.css" />
    <link rel="stylesheet" type="text/css" href="/css/le-frog/jquery-ui-1.10.3.custom.css" />

    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css" />
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.11/css/dataTables.bootstrap.min.css" />

    <link rel="stylesheet" type="text/css" href="/css/user.css" />

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.min.js"></script>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.3.0/css/datepicker.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.3.0/css/datepicker3.min.css" />


    <?php if(file_exists('pages'.CURRENT_PAGE. '/' . CURRENT_PAGE_NAME . '.css')) { ?>
        <link rel="stylesheet" type="text/css" href="/pages<?php echo CURRENT_PAGE. '/' . CURRENT_PAGE_NAME . '.css'; ?>" />
    <?php } ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.3.0/js/bootstrap-datepicker.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.18.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.47/js/bootstrap-datetimepicker.min.js"></script>

    <script type='text/javascript' src='/js/jquery.cookie.js'></script>
    <script type='text/javascript' src='/js/jquery.stickyheader.js'></script>
    <script type='text/javascript' src='/QuickDRY/js/helpers.js'></script>
    <script type='text/javascript' src='/js/jquery.mousewheel.min.js'></script>
    <script type='text/javascript' src='/js/jquery.scrollbar-min.js'></script>
    <script type='text/javascript' src='/js/jquery-tooltip.min.js'></script>
    <script type='text/javascript' src='/js/jquery.notifications-1.1.min.js'></script>
    <script type='text/javascript' src='/js/dropit.js'></script>
    <script type="text/javascript" language="javascript" src="https://cdn.datatables.net/1.10.11/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" language="javascript" src="https://cdn.datatables.net/1.10.11/js/dataTables.bootstrap.min.js"></script>
    <script src="https://www.google.com/jsapi"></script>
    <script type="text/javascript" src="/QuickDRY/js/GoogleCharts.js"></script>

    <?php if(file_exists('pages'.CURRENT_PAGE. '/' . CURRENT_PAGE_NAME . '.js')) { ?>
        <script type="text/javascript" src="/pages<?php echo CURRENT_PAGE. '/' . CURRENT_PAGE_NAME . '.js'; ?>"></script>
    <?php } ?>

    <script src="/js/tmpl.min.js"></script>
    <script src="/js/load-image.min.js"></script>
    <script src="/js/canvas-to-blob.min.js"></script>
    <script src="/js/jquery.iframe-transport.js"></script>
    <script src="/js/jquery.fileupload.js"></script>
    <script src="/js/jquery.fileupload-fp.js"></script>
    <script src="/js/jquery.fileupload-ui.js"></script>
    <script src="/js/locale.js"></script>
    <script src="/js/jscolor.js"></script>
    <script type='text/javascript' src='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/2.6.1/fullcalendar.min.js'></script>
</head>
<body onload="if (typeof InitGoogleCharts === 'function') { InitGoogleCharts(); }">

<div class="container-fluid">

    <?php echo $_PAGE_HTML; ?>

    <div class="row">
        <div class="col-xs-12" style="text-align: center;">
            <?php if(defined('META_LOGO') && META_LOGO) { ?><img src="/images/logo.png"/><br/><?php } ?>
            Copyright <?php echo date('Y'); ?> <?php echo META_TITLE; ?>
        </div>
    </div>

</div>
</body>
</html>