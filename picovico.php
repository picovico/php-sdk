<?php

/**
 * Licensed under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */
/**
  if (!function_exists('curl_init')) {
  throw new Exception('Picovico needs the CURL PHP extension.');
  }

  if (!function_exists('json_decode')) {
  throw new Exception('Picovico needs the JSON PHP extension.');
  }
 *
 */

/**
 * The Picovico configuration class
 */
class Picovico_Config{

    private static $PV_config;
    private static $PV_config_loaded;
    
    public static function _init(){
        if(isset(self::$PV_config_loaded) AND self::$PV_config_loaded == TRUE){
            // do nothing
        }else{
            // load the configuration from config file once, and cache it
            $ini_config = parse_ini_file(__DIR__."/"."picovico.ini", TRUE);

            self::$PV_config = $ini_config;
            self::$PV_config_loaded = TRUE;
        }
    }

    public static function get($var = null){
        if(($var)){
            return self::$PV_config[$var];
        }else{
            return self::$PV_config;
        }
    }

    public static function get_api_config($var = null){
        if(($var)){
            return self::$PV_config["api"][$var];
        }else{
            return self::$PV_config["api"];
        }
    }

}

// Initialize the configuration
Picovico_Config::_init();


/**
 * The Picovico way of handling Execptions
 *
 * @author acpmasquerade <acpmasquerade@picovico.com>
 */
class Picovico_Api_Exception extends Exception {

    /**
     * The result from the API server that represents the exception information.
     */
    protected $result;

    public function __construct($result) {
        parent::__construct($msg, $code);
    }

    /**
     * Return the associated result object returned by the API server.
     *
     * @return array The result from the API server
     */
    public function getResult() {
        return $this->result;
    }

    /**
     * Returns the associated type for the error. This will default to
     * 'UFO_Exception' when a type is not available.
     *
     * @return string
     */
    public function getType() {
        return 'UFO_Exception';
    }

    /**
     * @return string The string representation of the error
     */
    public function __toString() {
        return $error_string;
    }

}

/**
 * Provides access to the Picovico Application Platform
 * 
 * @author acpmasquerade <acpmasquerade@picovico.com>
 */
class Picovico {
    /**
     * Version.
     */
    const API_VERSION = '0.1alpha';
    const VERSION = '0.1alpha';

    /**
     * Default options for curl.
     */
    public static $CURL_OPTIONS = array(
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_USERAGENT => 'Picovico-php-0.1alpha',
        CURLOPT_SSL_VERIFYPEER => false,
    );
    /**
     * The access_token
     *
     * @var string
     */
    protected $access_token = null;

    /**
     * Initialize a Picovico Application.
     *
     * @param array $config The application configuration
     */
    public function __construct($config) {
        if (isset($config["access_token"])) {
            $this->set_access_token($config["access_token"]);
        }
    }

    /**
     * Sets the access token for api calls.
     *
     * @param string $access_token an access token.
     * @return Picovico
     */
    public function set_access_token($access_token) {
        $this->access_token = $access_token;
        return $this;
    }

    /**
     * Determines the access token that should be used for API calls.
     *
     * @return string The access_token
     */
    public function get_access_token() {
        return $this->access_token;
    }

    /**
     * Makes an HTTP request. The CURL way
     *
     * @param string $url The URL to make the request to
     * @param array $params The parameters to use for the GET/POST body
     * @param CurlHandler $curl_handler Initialized curl handle
     *
     * @return string The response text
     */
    protected function make_request($url, $params, $curl_handler=null) {
        if (!$curl_handler) {
            $curl_handler = curl_init();
        }

        $options = self::$CURL_OPTIONS;

        $options[CURLOPT_POSTFIELDS] = http_build_query($params, null, '&');
        
        $options[CURLOPT_URL] = $url;

        curl_setopt_array($curl_handler, $options);
        $result = curl_exec($curl_handler);

        if ($result === false) {
            $e = new Picovico_Api_Exception(array(
                        'error_code' => curl_errno($curl_handler),
                        'error' => array(
                            'message' => curl_error($curl_handler),
                            'type' => 'CurlException',
                        ),
                    ));
            curl_close($curl_handler);
            throw $e;
        }
        curl_close($curl_handler);
        return $result;
    }

    /**
     * Build the URL for api given parameters.
     *
     * @param $method String the method name.
     * @return string The URL for the given parameters
     */
    protected function get_api_url($method) {
        return "http://api.picovico.com/".Picovico_Config::get_api_config($method);
    }

    /**
     * Returns the Current URL,
     *
     * @return string The current URL
     */
    protected function get_current_url() {
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
    protected function throw_api_exception($result) {
        $e = new Picovico_Api_Exception($result);        
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
     * Destroy the current session
     */
    public function destroy_session() {
        $this->set_access_token(null);
    }
}


/**
 * Picovico Themes API wrapper
 *
 * @author acpmasquerade <acpmasquerade@gmail.com>
 */
class Picovico_Theme extends Picovico{
    function  __construct($config) {
        parent::__construct($config);
    }

    /**
     * 
     * @return <array> Array of Picovico_Theme (s)
     */
    function get_available_themes(){
        
    }

    /**
     * Fetches a Picovico theme for the machine_name
     * 
     * @param <string> $theme_machine_name - The machine name identifier for the theme
     *
     * @return Picovico_Theme
     */
    function get_theme($theme_machine_name){
        
    }
}

/**
 * Picovico Videos API wrapper
 *
 * @author acpmasquerade <acpmasquerade@gmail.com>
 */
class Picovico_Video extends Picovico{
    function  __construct($config) {
        parent::__construct($config);
    }

    /**
     * Fetches a publicly available video, created by user.
     * 
     * @param <string> $public_video_identifier - The unique identifier for any publicly available video
     *
     * @return <Picovico_Video>
     */
    function get_public_video($public_video_identifier){

    }

    /**
     * Fetches videos created by the user identified by the access_token
     * 
     * @param <string> $access_token (optional) The access_token
     *
     * @return <array> array of Picovico_Video (s)
     */
    function get_my_videos($access_token = null){
        
    }

    /**
     * Create video
     */
    function create_video(){
        
    }

    
}


