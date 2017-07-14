<?php
$page = '<div class="tab_nav">
    <div class="tab_top tab_selected">' . CapsToSpaces(str_replace('Class','',$c_name)) . '</div>
    <div class="tab tab_top"><a id="" onclick="' . $c_name . '.Load();">New</a></div>
</div>

<?php echo BootstrapPaginationLinks($PageModel->Count); ?>
<table class="table table-striped">
    <thead>
    <?php echo $PageModel->TableHeader; ?>
    </thead>
    <?php foreach ($PageModel->' . $page_dir . ' as $item) { ?>
        <?php echo $item->ToRow(true); ?>
    <?php } ?>
</table>
<?php echo BootstrapPaginationLinks($PageModel->Count); ?>

<?php require_once \'pages/json/_' . $c_name . '/controls/add.php\'; ?>
';

$fp = fopen('manage/' . $page_dir . '/' . $page_dir . '.php','w');
fwrite($fp,$page);
fclose($fp);

$code = '<?php

/**
 * Class ' . $page_dir . '
 *
 * @property ' . $c_name . '[] ' . $page_dir . '
 * @property string Count
 * @property string TableHeader
 */
class ' . $page_dir . ' extends BasePage
{
    public $' . $page_dir . ';
    public $Count;
    public $TableHeader;

    public function Init()
    {
        $this->MasterPage = \'user\';
        $this->IncludeMenu = true;

        $items = ' . $c_name . '::GetAllPaginated(null, null, PAGE, PER_PAGE);
        $this->TableHeader = ' . $c_name . '::GetHeader(SORT_BY, SORT_DIR, true);
        $this->' . $page_dir . ' = $items[\'items\'];
        $this->Count = $items[\'count\'];

    }
}

$PageModel = new ' . $page_dir . '($Request, $Session, $Cookie);
';
$fp = fopen('manage/' . $page_dir . '/' . $page_dir . '.code.php','w');
fwrite($fp,$code);
fclose($fp);
