<html>

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=10"/>
    <meta charset="UTF-8">

    <title><?php echo isset($_META_TITLE) ? $_META_TITLE : META_TITLE; ?></title>

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css"/>

    <link rel="stylesheet" type="text/css" href="/css/jquery.notifications.css"/>
    <link rel="stylesheet" type="text/css" href="/css/jquery.fileupload-ui.css"/>

    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css"/>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.11/css/dataTables.bootstrap.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.4.0/fullcalendar.min.css">

    <link rel="stylesheet" type="text/css" href="/css/user.css"/>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.3.0/css/datepicker.min.css"/>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.3.0/css/datepicker3.min.css"/>

    <link rel="stylesheet" href="/QuickDRY/css/GoogleChartFix.css"/>
    <link rel="stylesheet" href="/css/masterpage/user_sidemenu.css"/>

    <?php if (file_exists('pages' . CURRENT_PAGE . '/' . CURRENT_PAGE_NAME . '.css')) { ?>
        <link rel="stylesheet" type="text/css"
              href="/pages<?php echo CURRENT_PAGE . '/' . CURRENT_PAGE_NAME . '.css'; ?>"/>
    <?php } ?>

    <script type='text/javascript' src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
    <script type='text/javascript' src="https://code.jquery.com/ui/1.11.4/jquery-ui.min.js"></script>
    <script type='text/javascript' src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.3.0/js/bootstrap-datepicker.min.js"></script>
    <script type='text/javascript' src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
    <script type='text/javascript' src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.18.1/moment.min.js"></script>
    <script type='text/javascript' src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.47/js/bootstrap-datetimepicker.min.js"></script>
    <script type='text/javascript' src="https://www.google.com/jsapi"></script>
    <script type='text/javascript'
            src='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/2.6.1/fullcalendar.min.js'></script>


    <script type='text/javascript' src='/QuickDRY/js/jquery.cookie.js'></script>
    <script type='text/javascript' src='/QuickDRY/js/MD5.js'></script>
    <script type="text/javascript" src="/QuickDRY/js/QuickDRY.Cookies.js"></script>
    <script type="text/javascript" src="/QuickDRY/js/QuickDRY.GoogleCharts.js"></script>
    <script type="text/javascript" src="/QuickDRY/js/QuickDRY.HTTP.js"></script>
    <script type="text/javascript" src="/QuickDRY/js/QuickDRY.js"></script>
    <script type="text/javascript" src="/QuickDRY/js/QuickDRY.Strings.js"></script>
    <script type="text/javascript" src="/QuickDRY/js/QuickDRY.Tabs.js"></script>


    <?php if (file_exists('pages' . CURRENT_PAGE . '/' . CURRENT_PAGE_NAME . '.js')) { ?>
        <script type="text/javascript"
                src="/pages<?php echo CURRENT_PAGE . '/' . CURRENT_PAGE_NAME . '.js'; ?>"></script>
    <?php } ?>

</head>
<body onload="if (typeof InitGoogleCharts === 'function') { GoogleCharts.InitJSAPI(InitGoogleCharts); }">

<div class="container-fluid">

            <?php echo $Web->HTML; ?>

</div>

</body>
</html>