
<div class="menu-container">
<?php /* @var $CurrentUser UserClass */ if(isset($CurrentUser) && is_object($CurrentUser) && $CurrentUser->id) { ?>

<div style="float: right;">

<dl class="dropdown" style="display: inline-block;">
    <dt><a href="#" style="text-decoration: none;"><span><?php echo $CurrentUser->email; ?></span></a></dt>
    <dd>
        <ul>
            <li><a href="/my_account">My Account</a></li>
            <?php if($CurrentUser->Is(ROLE_ID_ADMINISTRATOR)) { ?>
            <li><a href="/admin">Admin</a></li>
            <?php } ?>
            <li><a href="/logout">Logout</a></li>
        </ul>
    </dd>
</dl>
</div>

<?php } ?>

<?php
echo $_MENU_HTML;
?>

</div>
<div style="clear:both;"></div>
<div style="height:24px;"></div>
