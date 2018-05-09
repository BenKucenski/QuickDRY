<html>

<head>
    <meta http-equiv="X-UA-Compatible" content="IE=10" />
    <title><?php echo isset($_META_TITLE) ? $_META_TITLE : META_TITLE; ?></title>

    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css"/>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css">
    <link rel="stylesheet" id="css-revolution-css" href="/css/bootstrap-datepicker3.min.css" type="text/css" media="">

    <?php if(file_exists('pages'.CURRENT_PAGE. '/' . CURRENT_PAGE_NAME . '.css')) { ?>
        <link rel="stylesheet" href="/pages<?php echo CURRENT_PAGE. '/' . CURRENT_PAGE_NAME . '.css'; ?>">
    <?php } ?>

    <script src="https://code.jquery.com/jquery-1.11.3.min.js"></script>
    <script src="https://code.jquery.com/ui/1.11.4/jquery-ui.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.3.0/js/bootstrap-datepicker.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.18.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.47/js/bootstrap-datetimepicker.min.js"></script>
    <script src="https://www.google.com/jsapi"></script>
    <script type="text/javascript" src="/QuickDRY/js/GoogleCharts.js"></script>

    <script type='text/javascript' src='/QuickDRY/js/jquery.cookie.js'></script>
    <script type='text/javascript' src='/QuickDRY/js/jquery.stickyheader.js'></script>
    <script type='text/javascript' src='/QuickDRY/js/helpers.js'></script>
    <script type='text/javascript' src='/QuickDRY/js/jquery.mousewheel.min.js'></script>
    <script type='text/javascript' src='/QuickDRY/js/jquery.scrollbar-min.js'></script>
    <script type='text/javascript' src='/QuickDRY/js/jquery-tooltip.min.js'></script>
    <script type='text/javascript' src='/QuickDRY/js/jquery.notifications-1.1.min.js'></script>
    <script type='text/javascript' src='/QuickDRY/js/dropit.js'></script>

    <?php if(file_exists('pages'.CURRENT_PAGE. '/' . CURRENT_PAGE_NAME . '.js')) { ?>
        <script type="text/javascript" src="/pages<?php echo CURRENT_PAGE. '/' . CURRENT_PAGE_NAME . '.js'; ?>"></script>
    <?php } ?>

    <script src="/QuickDRY/js/tmpl.min.js"></script>
    <script src="/QuickDRY/js/load-image.min.js"></script>
    <script src="/QuickDRY/js/canvas-to-blob.min.js"></script>
    <script src="/QuickDRY/js/jquery.iframe-transport.js"></script>
    <script src="/QuickDRY/js/jquery.fileupload.js"></script>
    <script src="/QuickDRY/js/jquery.fileupload-fp.js"></script>
    <script src="/QuickDRY/js/jquery.fileupload-ui.js"></script>
    <script src="/QuickDRY/js/locale.js"></script>
    <script src="/QuickDRY/js/jscolor.js"></script>
</head>
<body onload="if (typeof InitGoogleCharts === 'function') { InitGoogleCharts(); }">


<div class="container-fluid">
    <div class="row">
        <div id="custom-bootstrap-menu" class="navbar navbar-default " role="navigation">
            <div class="collapse navbar-collapse navbar-menubuilder">
                <ul class="nav navbar-nav navbar-left">
                    <?php echo $Web->Navigation->RenderBootstrap(); ?>
                </ul>
                <?php if(isset($Web->CurrentUser) && is_object($Web->CurrentUser) && $Web->CurrentUser->login) { ?>

                    <ul class="nav navbar-nav navbar-right">
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><?php echo $Web->CurrentUser->login; ?><span class="caret"></span></a>
                            <ul class="dropdown-menu">
                                <li><a href="/logout">Logout</a></li>
                            </ul>
                        </li>
                    </ul>


                <?php } ?>
            </div>
        </div>
    </div>

    <div class="row">
        <?php echo $Web->HTML; ?>
    </div>

    <div class="row" style="margin: 15px;">
        <div style="text-align: center; font-size: 16pt;">
            <?php if(defined('META_LOGO') && META_LOGO) { ?><img src="/images/logo.png"/><br/><?php } ?>
            Copyright <?php echo date('Y'); ?> <?php echo META_TITLE; ?>
        </div>
    </div>

</div>

<input type="hidden" id="current_tab" value="<?php echo $Web->Cookie->current_tab * 1.0; ?>" />
<?php require_once 'QuickDry/controls/ctl_notice.php'; ?>
<?php require_once 'QuickDry/controls/ctl_wait.php'; ?>
<?php require_once 'QuickDry/controls/ctl_confirm.php'; ?>


<script>
    DOMAIN = "<?php echo COOKIE_DOMAIN; ?>";
    $(document).ready(function(){
        <?php if($Web->Session->notice) { ?>
        NoticeDialog('Notice', "<?php echo $Web->Session->notice; ?>");
        <?php $Web->Session->notice = ''; } ?>
        <?php if($Web->Session->error) { ?>
        NoticeDialog('Error', "<?php echo $Web->Session->error; ?>");
        <?php $Web->Session->error = ''; } ?>

        ShowTab($('#current_tab').val());
    });
</script>
</body>
</html>

