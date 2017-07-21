<?php foreach ($Session->api_log as $log) {
    /* @var $log APIRequest */ ?>
    <table style="width: 100%; border-collapse: collapse; margin-bottom: 1.0em;">
        <tr>
            <td colspan="2" style="padding: 3px; border: solid 1px #000; background-color: #ddd;"><?php echo $log->path; ?></td>
        </tr>
        <tr>
            <td colspan="2" style="padding: 3px;"><b>Response Header</b></td>
        </tr>
        <tr>
            <td colspan="2" style="padding: 3px;">
                <pre><?php echo $log->_http_headers; ?></pre>
            </td>
        </tr>
        <?php if (is_array($log->data)) { ?>
            <tr>
                <td colspan="2" style="padding: 3px;"><b>Parameters</b></td>
            </tr>
            <?php foreach ($log->data as $name => $value) {
                if ($name === 'password' || $name === 'upass') {
                    $value = '********';
                } ?>
                <tr>
                    <td style="border: solid 1px #ccc; padding: 3px;"><?php echo $name; ?></td>
                    <td style="border: solid 1px #ccc; padding: 3px;"><?php echo !is_array($value) ? $value : implode(', ', $value); ?></td>
                </tr>
            <?php } ?>
            <?php $props = $log->GetProps();
            if (sizeof($props)) {
                if (!is_array($props)) {
                    $props = [$props];
                } ?>
                <tr>
                    <td colspan="2" style="padding: 3px;"><b>Return Values</b></td>
                </tr>
                <?php foreach ($props as $name => $value) {
                    if ($name === 'password' || $name === 'upass') {
                        $value = '********';
                    } ?>
                    <tr>
                        <td style="border: solid 1px #ccc; padding: 3px;"><?php echo $name; ?></td>
                        <td style="border: solid 1px #ccc; padding: 3px;"><?php echo !is_object($value) ? (is_array($value) ? 'Array of Objects (' . sizeof(
                                    $value
                                ) . ')' : $value) : 'Single Object'; ?></td>
                    </tr>
                    <?php if (is_object($value)) {
                        $ps = get_object_vars($value); ?>
                        <tr>
                            <td style="border: solid 1px #ccc; padding: 3px; vertical-align: top; ">Object</td>
                            <td style="border: solid 1px #ccc;">
                                <table style="width: 100%; border-collapse: collapse; margin-bottom: 1.0em;">
                                    <?php foreach ($ps as $col => $val) {
                                        if ($col === 'password' || $col === 'upass') {
                                            $val = '********';
                                        } ?>
                                        <tr>
                                            <td style="vertical-align: top; border: solid 1px #ccc; padding: 3px;"><?php echo $col; ?></td>
                                            <td style="border: solid 1px #ccc; padding: 3px;"><?php Debug::Show($val, false, false); ?></td>
                                        </tr>
                                    <?php } ?>
                                </table>
                            </td>
                        </tr>
                    <?php } ?>
                    <?php if (is_array($value) && sizeof($value)) {
                        $s = $value[0];
                        $ps = is_object($s) ? get_object_vars($s) : [$s]; ?>
                        <tr>
                            <td style="border: solid 1px #ccc; padding: 3px; vertical-align: top; ">Object<br/>Sample</td>
                            <td style="border: solid 1px #ccc;">
                                <table style="width: 100%; border-collapse: collapse; margin-bottom: 1.0em;">
                                    <?php foreach ($ps as $col => $val) {
                                        if ($col === 'password') {
                                            $val = '********';
                                        } ?>
                                        <tr>
                                            <td style="vertical-align: top; border: solid 1px #ccc; padding: 3px;"><?php echo $col; ?></td>
                                            <td style="border: solid 1px #ccc; padding: 3px;"><?php echo is_object($val) || is_array($val) ? '<pre>' . print_r($val, true)
                                                    . '</pre>' : $val; ?></td>
                                        </tr>
                                    <?php } ?>
                                </table>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } ?>
            <?php } ?>
        <?php } ?>
    </table>
<?php } ?>

<?php $Session->api_log = array(); ?>