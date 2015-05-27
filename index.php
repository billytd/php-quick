<?php
/**
 * PHP Quick - Test php code snippets quickly
 * Tested with PHP 5.5
 * @author Billy Flaherty bflaherty4@gmail.com
 */

require_once 'quick.php';

$quick = new Quick();

if ($quick->isValidPost()) {
    $quick->processRequest();
}

$result = $quick->getProcessResult();

?>
<!DOCTYPE html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>PHP Quick!</title>
    <link rel="stylesheet" type="text/css" href="./default.css">
    <script type="text/javascript" src="http://code.jquery.com/jquery-1.8.2.min.js"></script>
    <script type="text/javascript" src="./quick.js"></script>
    <script type="text/javascript">
    $(document).ready(function(){
        $.phpQuick({
            process_url: '<?php echo $_SERVER['REQUEST_URI']; ?>',
            valid_post: <?php echo $quick->isValidPost() ? 'true' : 'false'; ?>,
            page_request_result: <?php echo json_encode($result); ?>
        });
    });
    </script>
</head>
<body>
    <h1>PHP Quick <span>PHP <?php echo phpversion(); ?> <a href="?info=1">view phpinfo()</a></span></h1>
    <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" id="codeForm">
        <div id="InputContainer" class="fieldsetWrapper<?php echo $quick->isValidPost() ? ' left' : ''; ?>">
            <fieldset>
                <legend>In</legend>
                <textarea name="code"><?php echo $quick->getPost('code'); ?></textarea>
                <label>Display Errors: <input type="checkbox" name="errors" id="enableErrors" value="1"<?php echo $quick->getPost('errors') ? ' checked="checked"' : ''; ?>></label>
                <label>Parse as:
                    <select name="type">
                        <option value="php"<?php echo ($quick->getPost('type') == 'php' ? ' selected="selected"' : ''); ?>>PHP</option>
                        <option value="json"<?php echo ($quick->getPost('type') == 'json' ? ' selected="selected"' : ''); ?>>JSON</option>
                    </select>
                </label>
                <br>
                <input type="submit" value="Parse">
            </fieldset>
        </div>

        <div id="OutputContainer" class="fieldsetWrapper right hide">
            <fieldset class="right">
                <legend>Out</legend>
                <pre id="output"><?php echo $result['body']; ?></pre>
                <p id="ExecutionTime">Execution time: <strong><?php echo $result['elapsed_time_formatted']; ?></strong></p>
                <p id="outputError" class="hide"><?php echo $result['error']; ?></p>
            </fieldset>
        </div>
        <div class="clear"></div>
    </form>

    <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
        <fieldset>
            <legend>Current Time</legend>
            <label id="TheCurrentTime">
                <p><em><?php echo date('r'); ?></em> :: <input type="text" value="<?php echo time(); ?>" readonly="readonly" class="selectable"></p>
            </div>
        </fieldset>

        <fieldset id="TimeStampConverter">
            <legend>Format Timestamp</legend>
            <label>
                <p><input type="text" name="ts" placeholder="Unix Timestamp" value="<?php echo isset($_POST['ts']) ? $_POST['ts'] : ''; ?>">
                    :: <em class="note">(Enter a unix timestamp to see <a href="https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Date/toUTCString#Description">formatted date</a>.)</em>
                <input type="text" class="selectable medium hide" readonly="readonly"></p>
            </label>
        </fieldset>
    </form>
</body>