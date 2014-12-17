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

    funciton __construct(){
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

    	$url = "http://".Picovico::API_SERVER."/".Picovico::API_VERSION."/".$url;

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

        $curl_request_params_string = http_build_query($params, null, '&');

        if($method === PicovicoRequest::POST){
            $options[CURLOPT_POSTFIELDS] = $curl_request_params_string;
            $options[CURLOPT_URL] = $url;
        }elseif($method === PicovicoRequest::GET){
            $curl_request_url_parts = parse_url($url."?");
            if(isset($curl_request_url_parts["query"])){
                $curl_request_url = $url . "&" . $curl_request_params_string;
            }else{
                $curl_request_url = $url . "?" . $curl_request_params_string;
            }
            $options[CURLOPT_URL] = $curl_request_url;
        }

        if($method === PicovicoRequest::PUT OR $method === PicovicoRequest::DELETE){
        	$curl_headers[] = "X-HTTP-Method-Override: ".strtoupper($method);
        }

        $file_pointer = NULL;

        // for put request
        if($method === PicovicoRequest::PUT and isset($params["file"])){
        	$file_pointer = fopen($params["file"], "r");
        	$filesize = filesize($params["file"]);
        	$options[CURLOPT_INFILE] = $file_pointer;
        	$options[CURLOPT_INFILESIZE] = $filesize;
        }

		if($curl_headers){
	    	$options[CURLOPT_HTTPHEADER] = $curl_headers;
	    }

        curl_setopt_array($ch, $options);
        
        $result = curl_exec($ch);

        if ($result === FALSE) {
            $e = new PicovicoException(array(
                        "type"=>"CurlException",
                        'message' => curl_error($ch),
                        'code' => curl_errno($ch),
                    ));
            curl_close($ch);
            throw $e;
        }
        curl_close($ch);

        if($file_pointer != NULL){
        	fclose($file_pointere);
        }

        $curl_response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if($curl_response_code !== 200){
        	$e = new PicovicoException(array(
                        "type"=>"ApiHttpException",
                        'message' => $response,
                        'code' => $curl_response_code,
                    ));
            curl_close($ch);
            throw $e;
        }

        // Because every response is a valid JSON response
        return json_decode($result, TRUE);
    }
}