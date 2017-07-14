<?php
$list = InboxClass::GetSeverityCounts($CurrentUser->id);
$ss = SeverityClass::GetAll('sort_order','desc');

?>
<div id="inbox">
<div style="float: right; font-weight: bold; padding-left: 10px; color: #fff;"></div>
<div class="notifications" onclick="ToggleRecentMessages();">
<span style="font-weight: bold; color: #fff; text-decoration: none;">Inbox</span>
<?php foreach($ss as /* @var $s SeverityClass */ $s) if(isset($list[$s->id])) { ?>
<div class="notify" title="<?php echo $s->name; ?>" style="background-color: #<?php echo $s->color->color; ?>"><?php echo $list[$s->id]; ?></div>
<?php } ?>
</div>
<div id="inbox_list"></div>
</div>

<script>
$(document).ready(function() {
	setTimeout('CheckForNewMessages();',30000);
});
</script>