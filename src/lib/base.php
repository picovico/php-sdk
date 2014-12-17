<?php

/**
 * Provides access to the Picovico Application Platform
 * 
 * @author acpmasquerade <acpmasquerade@picovico.com>
 */
class PicovicoBase {
    
    /**
     * The access_token
     *
     * @var string
     */
    protected $request = null;

    /**
     * Initialize a Picovico Application.
     *
     * @param array $config The application configuration
     */
    public function __construct($config) {
        $request = new PicovicoRequest();
    }

    /**
     * Returns the Current URL,
     *
     * @return string The current URL
     */
    public static  function get_current_url() {
        if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1)
                || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'
        ) {
            $protocol = 'https://';
        } else {
            $protocol = 'http://';
        }
        $currentUrl = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

        $parts = parse_url($currentUrl);
        
        // use port if non default
        $port =
                isset($parts['port']) &&
                (($protocol === 'http://' && $parts['port'] !== 80) ||
                ($protocol === 'https://' && $parts['port'] !== 443)) ? ':' . $parts['port'] : '';

        // rebuild
        
        return $protocol . $parts['host'] . $port . $parts['path'] . $query;
    }

    /**
     * Analyzes the supplied result to see if it was thrown
     * because the access token is no longer valid.  If that is
     * the case, then the persistent store is cleared.
     *
     * @param $result array A record storing the error message returned
     *                      by a failed API call.
     */
    public static function throw_api_exception($result) {
        $e = new PicovicoException($result);
        throw $e;
    }

    /**
     * Prints to the error log if you aren't in command line mode.
     *
     * @param string $msg Log message
     */
    protected static function error_log($msg) {
        // disable error log if we are running in a CLI environment
        if (php_sapi_name() != 'cli') {
            error_log($msg);
        }
    }

    /**
     * Dumps a readable output for a variable
     * 
     * @param <type> $var variable to debug
     */
    public static function debug($var){
        if (php_sapi_name() != 'cli') {
            echo "<pre>";
        }
        print_r($var);
        
        die();
    }

    public is_logged_in(){
        return $this->request->is_logged_in();
    }

    public is_local_file($file_path){
        $prefix = strtolower(substr($file_path, 0, 7));
        if($prefix === "http://" || $prefix === "https:/"){
            return false;
        }
        return true;
    }
}