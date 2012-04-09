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

define("PICOVICO_API_GET", "get");
define("PICOVICO_API_POST", "post");


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
     * @param string $method GET or POST
     *
     * @return string The response text
     */
    protected function make_request($url, $params = array(), $method = PICOVICO_API_POST) {

        $curl_handler = curl_init();
        
        $options = self::$CURL_OPTIONS;

        if(!$params){
            $params = array();
        }

        // force the access token
        $params["access_token"] = $this->get_access_token();

        $curl_request_params_string = http_build_query($params, null, '&');

        if($method == PICOVICO_API_POST){
            $options[CURLOPT_POSTFIELDS] = $curl_request_params_string;
            $options[CURLOPT_URL] = $url;
        }else{
            $curl_request_url_parts = parse_url($url."?");
            if(isset($curl_request_url_parts["query"])){
                $curl_request_url = $url . "&" . $curl_request_params_string;
            }else{
                $curl_request_url = $url . "?" . $curl_request_params_string;
            }
            $options[CURLOPT_URL] = $curl_request_url;
        }

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
     * Makes HTTP POST/GET request, and json_decodes the response
     * @param <type> $url
     * @param <type> $params
     * @param <type> $method
     */
    protected function make_json_request($url, $params = array(), $method = PICOVICO_API_POST) {
        $json_response = $this->make_request($url, $params, $method);
        return json_decode($json_response, TRUE);
    }

    /**
     * Build the URL for api given parameters.
     *
     * @param $method String the method name.
     * @return string The URL for the given parameters
     */
    protected function get_api_url($method) {
        return "https://api.picovico.com/".Picovico_Config::get_api_config($method);
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

    private $properties;
    
    function  __construct($config) {
        parent::__construct($config);
    }

    function get_name(){
        return $this->properties["name"];
    }

    function get_machine_name(){
        return $this->properties["machine_name"];
    }

    function get_description(){
        return $this->properties["description"];
    }

    function get_sample_url(){
        return $this->properties["sample_url"];
    }

    function get_thumbnail(){
        return $this->properties["thumbnail"];
    }

    private function set_properties($properties){
        $this->properties = $properties;
    }

    function get_properties(){
        return $this->properties;
    }

    /**
     *
     * @param <type> $response - JSON decoded response array from themes
     */
    function create_object_from_response($response){
        $this->set_properties($response);
        return $this;
    }

    /**
     * 
     * @return <array> Array of Picovico_Theme (s)
     */
    public function get_available_themes(){
        $url = $this->get_api_url("get_themes");
        $response = $this->make_json_request($url, array(), PICOVICO_API_GET);

        $themes = $response["themes"];

        $themes_objects_array = array();
        foreach($themes as $t){
            $themes_objects_array[] = $this->create_object_from_response($t);
        }

        return $themes_objects_array;
    }

    /**
     * Fetches a Picovico theme for the machine_name
     * 
     * @param <string> $theme_machine_name - The machine name identifier for the theme
     *
     * @return Picovico_Theme if available, otherwise returns NULL
     */
    public function get_theme($theme_machine_name){
        $url = $this->get_api_url("get_themes");
        $response = $this->make_json_request($url, array(), PICOVICO_API_GET);

        $themes = $response["themes"];

        foreach($themes as $t){
            if($t["machine_name"] == $theme_machine_name){
                return $this->create_object_from_response($t);
            }
        }

        return NULL;
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
