<?php

class Quick {
    private $max_ajax_php_eval_time = 1; // number of seconds to allow php to run when processing php submitted with ajax
    private $is_valid_post          = false;
    private $process_result         = ['success' => false, 'elapsed_time_formatted' => '', 'body' => '', 'error' => ''];
    private $ts_execution_start;

    public function __construct()
    {
        // is valid post
        $this->is_valid_post = ($this->getPost('type') && $this->getPost('code'));

        // if AJAX request, begin processing, send response and exit
        if (isset($_GET['ajax']) && $this->isValidPost()) {
            $this->processAjaxRequestSendOutputAndExit();
        }

        // show phpinfo and exit
        if (isset($_GET['info'])) {
            echo phpinfo();
            exit;
        }
    }

    private function processAjaxRequestSendOutputAndExit()
    {
        $this->processRequest();

        echo json_encode($this->getProcessResult());

        exit;
    }

    public function processRequest()
    {
        $this->initExecutionTimer();

        if ($this->getPost('type') == 'php') {
            $this->processPhp($this->getPost('code'), $this->max_ajax_php_eval_time);
        } else if ($this->getPost('type') == 'json') {
            $this->processJson($this->getPost('code'));
        }

        $this->process_result['elapsed_time_formatted'] = $this->fetchFormattedExecutionTime();
    }

    public function getProcessResult()
    {
        return $this->process_result;
    }

    public function processPhp($code, $time_limit = false)
    {
        if ($time_limit) {
            set_time_limit($time_limit);
        }

        ob_start();

        $old_err_vals = [
                'error_reporting' => error_reporting(E_ALL | E_STRICT),
                'html_errors'     => ini_set('html_errors', false),
                'display_errors'  => ini_set('display_errors', true),
                'log_errors'      => ini_set('log_errors', false)
            ];

        try {
            $success = (eval($code)) === null;
        } catch (Exception $e) {
            $response['error'] = $e->getMessage();
            $response['body']  = '';
        }

        error_reporting($old_err_vals['error_reporting']);
        ini_set('html_errors', $old_err_vals['html_errors']);
        ini_set('display_errors', $old_err_vals['display_errors']);
        ini_set('log_errors', $old_err_vals['log_errors']);

        $this->process_result['body'] = ob_get_clean();

        if (preg_match("/\n(Notice|Warning|Fatal error|Parse error)\:.*\n/i", $this->process_result['body'], $match)) {
            $error_string = $match[0];
        } else if (!$success){
            $error_string = $this->process_result['body'];
        } else {
            $this->process_result['success'] = true;
        }

        if (!$this->process_result['success']) {
            $this->process_result['error'] = preg_replace("/in \/.*\([0-9]+\) \: eval\(\)'d/i", 'in your', trim($error_string));
        }
    }

    public function processJson($json)
    {
        $json = preg_replace("/\\\\(\'|\"|\`)/", "$1", $json);
        $this->process_result['success'] = (bool)json_decode($json);

        if (!$this->process_result['success']) {
            $this->process_result['error'] = $this->fetchJsonErrorStringForErrorCode(json_last_error());
        } else {
            $this->process_result['body'] = $this->fetchPrettyJson($json);
        }
    }

    private function initExecutionTimer()
    {
        $this->ts_execution_start = microtime(true);
    }

    private function fetchFormattedExecutionTime()
    {
        $dec_places = 5;
        $number     = 0;

        if ($this->ts_execution_start) {
            $number = microtime(true) - $this->ts_execution_start;
        }

        return number_format($number, $dec_places) . ' seconds';
    }

    /**
     * Indents a flat JSON string to make it more human-readable.
     *
     * @param string $json The original JSON string to process.
     * @return string Indented version of the original JSON string.
     */
    private function fetchPrettyJson($json)
    {

        // CHECK JSON VALID
        if (!json_decode($json)) {
            return $this->fetchJsonErrorStringForErrorCode(json_last_error());
        }

        $pretty      = '';
        $level       = 0;
        $strLen      = strlen($json);
        $indentStr   = '    ';
        $newLine     = "\n";
        $prevChar    = '';
        $outOfQuotes = true;
        $chars       = str_split($json);

        foreach($chars as $char){

            // Are we inside a quoted string?
            if ($char == '"' && $prevChar != '\\') {
                $outOfQuotes = !$outOfQuotes;

            // If this character is the end of an element, output a new line and indent the next line.
            } else if (($char == '}' || $char == ']') && $outOfQuotes) {
                $pretty .= $newLine;
                $level--;
                for($j = 0; $j < $level; $j++) {
                    $pretty .= $indentStr;
                }
            } else if (($char == "\n" || $char == "\r" || $char == ' ') && $outOfQuotes){
                $char = '';
            }

            // Add the character to the result string.
            $pretty .= $char;

            // If the last character was the beginning of an element, output a new line and indent the next line.
            if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {

                $pretty .= $newLine;

                if ($char == '{' || $char == '[') {
                    $level++;
                }

                for ($j = 0; $j < $level; $j++) {
                    $pretty .= $indentStr;
                }
            }

            $prevChar = $char;
        }

        return $pretty;
    }

    private function fetchJsonErrorStringForErrorCode($error_code){
        switch ($error_code) {
            case JSON_ERROR_NONE:
                return '';
            case JSON_ERROR_DEPTH:
                $err = 'Maximum stack depth exceeded';
                break;
            case JSON_ERROR_STATE_MISMATCH:
                $err = 'Underflow or the modes mismatch';
                break;
            case JSON_ERROR_CTRL_CHAR:
                $err = 'Unexpected control character found';
                break;
            case JSON_ERROR_SYNTAX:
                $err = 'Syntax error, malformed JSON';
                break;
            case JSON_ERROR_UTF8:
                $err = 'Malformed UTF-8 characters, possibly incorrectly encoded';
            default:
                $err = 'Unknown error';
        }

        return 'JSON Error: ' . $err;
    }

    public function isValidPost()
    {
        return $this->is_valid_post;
    }

    public function getPost($key = null)
    {
        $post = [ // set defaults
                'requestHeaders' => [],
                'code'           => '',
                'type'           => 'php',
                'errors'         => null
            ];

        if (isset($_POST)) {
            $post = array_merge($post, $_POST);
        }

        if ($key) {
            return isset($post[$key]) ? $post[$key] : null;
        }

        return $post;
    }
}
