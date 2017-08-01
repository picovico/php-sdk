<?php

// error_reporting(0);

class Picovico{
    # api
    const api_version = "v2.7";
    const api_endpoint = "https://api2.picovico.com";
    # authentication
    const app_id = "default_app_id";
    const app_secret = "default_app_secret";
    const device_id = "com.picovico.dev";
    # session vars
    private $session_app_id;
    private $session_app_secret;
    private $session_device_id;
    private $access_key;
    private $access_token;
    private $is_authenticated = false;
    # header vars
    private $auth_headers;
    private $anon_headers;
    private $default_headers;
    # helpers
    private function _api_call($method, $url, $args = [], $headers = []){
        $ch = curl_init();        
        $curl_headers = $headers;
        $curl_options = [];
        $curl_request_url = implode("/", [self::api_endpoint, self::api_version, $url]);

        $method = strtoupper($method); # uppercase method. just in case
        $content_type = isset($curl_headers['Content-Type']) ? $curl_headers['Content-Type'] : null;
        if($content_type === "application/json"){
            $payload = json_encode($args);
            $curl_headers['Content-Length'] = strlen($payload);
            $curl_options[CURLOPT_CUSTOMREQUEST] = $method;
            $curl_options[CURLOPT_POSTFIELDS] = $payload;
        }else{
            $curl_request_params_string = http_build_query($args, null, '&');
            if($method === "POST"){
                $curl_options[CURLOPT_POSTFIELDS] = $curl_request_params_string;
            }elseif(in_array($method, array("GET", "PUT", "DELETE"))){
                if($curl_request_params_string){
                    $curl_request_url_parts = parse_url($curl_request_url);
                    if(isset($curl_request_url_parts["query"])){
                        $curl_request_url .= "&" . $curl_request_params_string;
                    }else{
                        $curl_request_url .= "?" . $curl_request_params_string;
                    }
                }
            }
        }
        $curl_options[CURLOPT_URL] = $curl_request_url;
        if(in_array($method, array("PUT", "DELETE"))){
        	$curl_headers["X-HTTP-Method-Override"] = $method;
        }
        $_curl_headers = [];
        foreach($curl_headers ? $curl_headers : $this->default_headers as $k=>$v){
            $_curl_headers[] = "{$k}: {$v}";
        }
        $curl_options[CURLOPT_HTTPHEADER] = $_curl_headers;
        $curl_options[CURLOPT_RETURNTRANSFER] = true;
        curl_setopt_array($ch, $curl_options);
        
        $response = curl_exec($ch);
        if ($response === FALSE) {
            return [null, null, curl_errno($ch)];
        }
        $resp_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return [$resp_code >= 400 ? false : true, $resp_code, json_decode($response, true)];
    }
    public function authenticated_api($method, $url, $args = [], $headers = []){
        return $this->_api_call($method, $url, $args, array_merge($this->auth_headers, $headers));
    }

    public function anonymous_api($method, $url, $args = [], $headers = []){
        return $this->_api_call($method, $url, $args, array_merge($this->anon_headers, $headers));
    }
    # constructor
    function __construct($app_id, $app_secret, $device_id){
        $this->session_app_id = $app_id ? $app_id : self::app_id;
        $this->session_app_secret = $app_secret ? $app_secret : self::app_secret;
        $this->session_device_id = $device_id ? $device_id : self::device_id;

        $this->default_headers = [
            'X-PV-Meta-App' => $this->session_app_id
        ];
        $this->anon_headers = $this->default_headers;
        $this->auth_headers = $this->default_headers;
    }
    public function authenticate(){
        $_args = [
            'app_id' => $this->session_app_id,
            'app_secret' => $this->session_app_secret,
            'device_id' => $this->session_device_id
        ];
        list($status, $code, $data) = $this->anonymous_api("POST", "login/app", $_args);
        $success = false;
        if($status AND $code === 200){
            $this->access_key = $data['data'][0]['access_key'];
            $this->access_token = $data['data'][0]['access_token'];
            $success = true;
        }
        $this->auth_headers['X-Access-Key'] = $this->access_key;
        $this->auth_headers['X-Access-Token'] = $this->access_token;
        return $success;
    }
    public function text_slide($title="", $body= ""){
        return [
            "name" => "text", 
            "data" => [
                "title" => $title,
                "text" => $body
            ]
        ];
    }
    public function image_slide($image, $caption= ""){
        return [
            "name" => "image", 
            "url" => $image,
            "data" => [
                "caption" => $caption
            ]
        ];
    }
}

# initialize and authenticate
$app_id = getenv("PV_2017_APP_ID");
$app_secret = getenv("PV_2017_APP_SECRET");
$device_id = getenv("PV_2017_DEVICE_ID");
$pv = new Picovico($app_id, $app_secret, $device_id);
$pv->authenticate();

# build the video JSON
$payload = [
    "style" => "vanilla_frameless",
    "quality" => 480,
    "name" => "Sample Video",
    "aspect_ratio" => "16:9",
    "assets" => [
        "music" => [
            "asset_id" => "aud_6j44J9zjbSQe54ZTTSqUj2"
            # "url" => ".... some url ..."
        ],
        "frames" => [
            // $pv->text_slide("aasdfasd", "basdfasd"),
            // $pv->text_slide("casdfa", "dasdfasd"),
            // $pv->text_slide("asdfasde", "fasdfasd"),
            // $pv->text_slide("asdfasde", "fasdfasd"),
            $pv->image_slide("https://images.unsplash.com/photo-1481326086332-e77dd61a4ea1"),
            $pv->image_slide("https://images.unsplash.com/photo-1481326086332-e77dd61a4ea1"),
            $pv->image_slide("https://images.unsplash.com/photo-1481326086332-e77dd61a4ea1"),
            $pv->image_slide("https://images.unsplash.com/photo-1481326086332-e77dd61a4ea1")            
        ]
    ]
];

$response = $pv->authenticated_api("POST", "me/videos", $payload, ["Content-Type"=>"application/json"]);
print_r($response);