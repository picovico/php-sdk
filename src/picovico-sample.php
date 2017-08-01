<?php
$PICOVICO_APP_ID = "";
$PICOVICO_APP_SECRET = "";
$PICOVICO_DEVICE_ID = "";

$PICOVICO_API = "https://api2.picovico.com/v2.7/";

class Picovico{
    # api
    const api_version = "2.7";
    const api_endpoint = "https://api2.picovico.com/";
    const api_url = self::api_endpoint + self::api_version + "/";
    # authentication
    const app_id = "your-app-id";
    const app_secret = "your-app-secret";
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
        $curl_options =[];
        $curl_request_params_string = http_build_query($args, null, '&');
        $curl_request_url = self::api_url + $url;

        $method = strtoupper($method); # uppercase method. just in case
        if($method === "POST"){

        }
        if($method === PicovicoRequest::POST){
            $options[CURLOPT_POSTFIELDS] = $curl_request_params_string;
        }elseif($method === PicovicoRequest::GET OR $method === PicovicoRequest::PUT OR $method === PicovicoRequest::DELETE){
            if($curl_request_params_string){
                $curl_request_url_parts = parse_url($url."?");
                if(isset($curl_request_url_parts["query"])){
                    $curl_request_url = $url . "&" . $curl_request_params_string;
                }else{
                    $curl_request_url = $url . "?" . $curl_request_params_string;
                }
            }
        }
        $options[CURLOPT_URL] = $curl_request_url;
        if($method === PicovicoRequest::PUT OR $method === PicovicoRequest::DELETE){
        	$curl_headers[] = "X-HTTP-Method-Override: ".strtoupper($method);
        }
		if($curl_headers){
	    	$options[CURLOPT_HTTPHEADER] = $curl_headers;
	    }
        curl_setopt_array($ch, $options);
        
        $response = curl_exec($ch);
        if ($response === FALSE) {
            $e = new PicovicoException(array(
                        "type"=>"CurlException",
                        'message' => curl_error($ch),
                        'code' => curl_errno($ch),
                    ));
            curl_close($ch);
            throw $e;
        }
        if($file_pointer != NULL){
        	fclose($file_pointer);
        }
        $curl_response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if($curl_response_code >=400){
			$message = json_decode($response, TRUE);
			$message["_url"] = $options[CURLOPT_URL];
        	$e = new PicovicoException(array(
                        "type"=>"ApiHttpException",
                        'message' => $message,
                        'code' => $curl_response_code,
                    ));
            curl_close($ch);
            throw $e;
        }
		curl_close($ch);
        // Because every response is a valid JSON response
        return json_decode($response, TRUE);
    }
    private function _api_authenticated_call($method, $url, $args = [], $headers = []){
        return $this->_api_call($method, $url, $args, array_merge($this->anon_headers, $headers));
    }

    private function _api_anon_call($method, $url, $args = [], $headers = []){
        return $this->_api_call($method, $url, $args, array_merge($this->auth_headers, $headers));
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

        $this->_authenticate();
    }
    private function _authenticate(){
        $_args = [
            'app_id' => $_app_id,
            'app_secret' => $_app_secret,
            'device_id' => $_device_id
        ];
        $resp = $this->_api_anon_call("POST", "login/app", $_args);
    }
}

# Authenticate
