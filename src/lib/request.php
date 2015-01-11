<?php

/** Request to Picovico **/

class PicovicoRequest{

	const ANONYMOUS = FALSE;
	const AUTHORIZED = TRUE;

	const GET = "get";
	const POST = "post";
	const PUT = "put";
	const DELETE = "delete";

    /**
     * Default options for curl.
     */
    public static $CURL_OPTIONS = array(
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_SSL_VERIFYPEER => FALSE
    );

    private $access_token;
    private $access_key;

    function __construct(){
    	self::$CURL_OPTIONS[CURLOPT_USERAGENT] = 'Picovico-php-' . Picovico::VERSION;
    	$this->access_key = NULL;
    	$this->access_token = NULL;
    }

    public function set_tokens($access_key, $access_token){
    	$this->access_token = $access_token; 
    	$this->access_key = $access_key;    	
    }

    public function is_logged_in(){
    	if($this->access_key !== NULL AND $this->access_token !== NULL){
    		return true;
    	}else{
    		return false;
    	}
    }

    public function get($url, $params = array(), $headers = array()){
    	return $this->make_request($url, $params, $headers, PicovicoRequest::GET);
    }

    public function post($url, $params = array(), $headers = array()){
    	return $this->make_request($url, $params, $headers, PicovicoRequest::POST);
    }

    public function put($url, $filename, $headers = array()){
    	return $this->make_request($url, array("file" => $filename), $headers,PicovicoRequest::PUT);
    }

    public function delete($url, $params  = array(), $headers = array()){
 		return $this->make_request($url, $params, $headers, PicovicoRequest::DELETE);
    }

    public function make_request($url, $params = array(), $headers = array(), $method = PicovicoRequest::GET, $include_access_headers = PicovicoRequest::AUTHORIZED) {

    	if ($include_access_headers == PicovicoRequest::AUTHORIZED AND $this->is_logged_in() === FALSE){
    		PicovicoBase::throw_api_exception("Not Requested - reason : Login required");
    		return NULL;
    	}

    	$url = "http://".Picovico::API_SERVER."/v".Picovico::API_VERSION."/".$url;

        $ch = curl_init();
        
        $options = self::$CURL_OPTIONS;

        if(!$params){
            $params = array();
        }

        $curl_headers = array();

        // access token headers
        if($include_access_headers === PicovicoRequest::AUTHORIZED){
	        $curl_headers[] = "X-Access-Key: {$this->access_key}";
	        $curl_headers[] = "X-Access-Token: {$this->access_token}";
	    }

	    if($headers){
	    	foreach($headers as  $h_key=>$h_val){
	    		$curl_headers[] = "{$h_key}: {$h_val}";
	    	}
	    }

        // serialize if array or objects
        foreach($params as $key=>$val){
            if(is_array($val) || is_object($val)){
                if(is_array($params)){
                    $params["{$key}"] = json_encode($val);
                }elseif(is_object($params)){
                    $params->{$key} = json_encode($val);
                }
            }
        }

        // for put request, if file is supplied, then specifiy the file for PUT
        // and remove the file parameter
        $file_pointer = NULL;
        if($method === PicovicoRequest::PUT and isset($params["file"])){
            $file_pointer = fopen($params["file"], "r");
            $filesize = filesize($params["file"]);
            $options[CURLOPT_PUT] = TRUE;
            $options[CURLOPT_INFILE] = $file_pointer;
            $options[CURLOPT_INFILESIZE] = $filesize;
            unset($params["file"]);
        }

        $curl_request_params_string = http_build_query($params, null, '&');
        $curl_request_url = $url;
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
        }else{
            $e = new PicovicoException(array(
                        "type"=>"CurlException",
                        'message' => "Invalid Request method : {$method}",
                        'code' => "0",
                    ));
            curl_close($ch);
            throw $e;
        }

        $options[CURLOPT_CUSTOMREQUEST] = strtoupper($method);
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
}
