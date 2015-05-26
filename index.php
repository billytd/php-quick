<?php
/**
 * PHP Quick - Test php code snippets quickly
 * Tested with PHP 5.5
 * @author Billy Flaherty bflaherty4@gmail.com
 */

require_once 'quick.php';

$quick = new Quick();

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
        console.log('b');
        $.phpQuick({
            ts_server: <?php echo time(); ?>,
            process_url: '<?php echo $_SERVER['REQUEST_URI']; ?>'
        });
    });
    </script>
</head>
<body>
    <h1>PHP Quick <span>PHP <?php echo phpversion(); ?> <a href="?info=1">view phpinfo()</a></span></h1>
    <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>" id="codeForm">
        <div class="fieldsetWrapper<?php echo $quick->getPost('code') ? ' left' : ''; ?>">
            <fieldset>
                <legend>In</legend>
                <textarea name="code"><?php echo $quick->getPost('code'); ?></textarea>
                <label>Display Errors: <input type="checkbox" name="errors" id="enableErrors" value="1"<?php echo $quick->getPost('errors') ? ' checked="checked"' : ''; ?>></label>
                <label>Parse as:
                    <select name="type">
                        <option value="php"<?php echo (($quick->getPost('type') == 'php') ? ' selected="selected"' : ''); ?>>PHP</option>
                        <option value="json"<?php echo ($quick->getPost('type') == 'json' ? ' selected="selected"' : ''); ?>>JSON</option>
                    </select>
                </label>
                <br>
                <input type="submit" value="Parse">
            </fieldset>
        </div>

        <?php
        if ($quick->isValidPost()) {
            $quick->processRequest();
            $result = $quick->getProcessResult();
            ?>
            <div class="fieldsetWrapper right">
                <fieldset class="right">
                    <legend>Out</legend>
                    <pre id="output"><?php
                        echo $result['error'] ? '' : $result['body'];
                    ?></pre>
                    <p id="ExecutionTime">Execution time: <strong><?php echo $result['elapsed_time_formatted']; ?></strong></p>
                    <div id="outputError"><p><?php echo $result['error']; ?></p></div>
                </fieldset>
            </div>
            <?php
        }
        ?>

        <div class="clear"></div>
    </form>

    <form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>">
        <fieldset>
            <legend>Current Time</legend>
            <label id="TheCurrentTime">
                <p><em><?php echo date('r'); ?></em> = <input type="text" value="<?php echo time(); ?>" readonly="readonly"></p>
            </div>
        </fieldset>

        <fieldset id="TimeStampConverter">
            <legend>Format Timestamp</legend>
            <label>
                <p><input type="text" name="ts" placeholder="Unix Timestamp" value="<?php echo isset($_POST['ts']) ? $_POST['ts'] : ''; ?>">
                    = <em class="note">(Enter a unix timestamp to see <a href="https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Date/toUTCString#Description">formatted date</a>.)</em>
                <input type="text" class="formatted" readonly="readonly"></p>
            </label>
        </fieldset>
    </form>
</body>