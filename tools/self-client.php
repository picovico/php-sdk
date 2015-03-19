<?php

	error_reporting(0);
	define("PICOVICO_DEBUG", false);

	include __DIR__."/../src/Picovico.php";

	$app_id = $argv[1];
	$app_secret = $argv[2];
	$device_id = $argv[3];

	$action = $argv[4];
	$action_function = str_replace("-", "_", $action);

	$action_response = "";

	$history_file = __DIR__."/.________picovico.tmp.json";

	if(file_exists("{$history_file}")){
		$history = json_decode(file_get_contents("{$history_file}"), TRUE);
	}else{
		$history = array();
	}

	if(isset($history["session"]["object"])){
		$client = unserialize($history["session"]["object"]);
	}else{
		$client = new Picovico($app_id, $app_secret, $device_id);
	}

	$client_arguments = array_splice($argv, 5);

	if(isset($history["session"]["video_id"])){
		$active_project = $history["session"]["video_id"];
	}else{
		$active_project = NULL;
	}

	if(isset($history["session"]["authenticated"])){
		$is_authenticated = $history["session"]["authenticated"];
	}else{
		$is_authenticated = FALSE;
	}

	if(!$action){
		echo "FATAL: Action is not defined";
		exit(1);
	}

	$authactions = array("login", "authenticate", "set-login-tokens", "logout");

	$stateless = array("login", 
		"authenticate", 
		"profile", "set-login-tokens", "open", 
		"begin", "upload-image", 
		"get-videos",
		"duplicate", 
		"draft",
		"upload-music", "get-styles", "get");

	$stateful = array("save", "preview", "create", 
		"set-callback-url", "remove-credits", "add-credits", 
		"set-quality", "set-style", "add-library-music",
		"reset",
		"add-music",
		"add-text", "add-image", "add-library-image");

	$supplement_actions = array("session", "project", "dump");

	function is_stateful_action($action){
		global $stateful;
		return in_array($action, $stateful);
	}

	function is_stateless_action($action){
		global $stateless;
		return in_array($action, $stateless);
	}

	function is_valid_action($action){
		return is_stateless_action($action) OR is_stateful_action($action);
	}

	function is_supplement_action($action){
		global $supplement_actions;
		return in_array($action, $supplement_actions);
	}

	function do_auth_action(){
		global $action;
		global $action_function;
		global $client_arguments;
		global $client;

		if ($action == "logout"){
			clear_history();
			echo "true\n";
			exit(0);
		}else{
			return call_user_func_array(array($client, $action_function),  $client_arguments);
		}
	}

	function do_supplement_action(){
		global $action;
		global $action_function;
		global $history;
		global $client;
		switch ($action) {
			case 'session':
				# code...
				$response_message = $history["session"];
				unset($response_message["object"]);
				return $response_message;
				break;
			case "project":
			case "dump":
				#
				return $client->dump();
				break;
		}
	}

	function do_other_action(){
		global $active_project;
		global $action;
		global $action_function;

		global $client;
		global $client_arguments;
		
		if(is_stateful_action($action)){
			if($active_project === NULL){
				echo "FATAL: Please start project first either with <begin> or <open>.\n";
				exit(1);
			}else{
				// call method with teh arguments
				return  call_user_func_array(array($client, $action_function),  $client_arguments);
			}
		}else{
			return call_user_func_array(array($client, $action_function),  $client_arguments);
		}
	}

	function write_history(){
		global $history;
		global $history_file;
		global $client;
		global $client_arguments;
		global $action;

		global $action_response;

		$history["action"] = $action;
		$history["args"] = $client_arguments;
		$history["response"] = $action_response;

		$history["session"]["object"] = serialize($client);
		file_put_contents($history_file, json_encode($history, JSON_PRETTY_PRINT));
	}

	function clear_history(){
		global $history;
		global $history_file;
		$history = array();
		file_put_contents($history_file, json_encode($history, JSON_PRETTY_PRINT));
		return True;
	}

	function raise_exception($e){
		echo "FATAL: API Exception";
		echo "\n";
		echo $e;
		echo "\n";
		exit(2);
	}

	// authenticate and save the history
	if(in_array($action, $authactions)){
		try{
			$action_response = do_auth_action();
			$history["session"]["authenticated"] = true;
			$history["session"]["access_key"] = $action_response["access_key"];
			$history["session"]["access_token"] = $action_response["access_token"];
		}catch(Exception $e){
			$history = array();
			$history["session"]["authenticated"] = false;
			write_history();
			raise_exception($e);
		}
	}elseif($is_authenticated !== TRUE){
		echo "FATAL: Autentication required prior to action calls\n";
		exit(1);
	}elseif(is_supplement_action($action)){
		$action_response = do_supplement_action();
	}
	elseif(is_valid_action($action)){
		try{
			$action_response = do_other_action();
		}catch(Exception $e){
			write_history();
			raise_exception($e);
		}
	}else{
		echo "ERROR: Invalid action\n";
		exit(3);
	}

	if($action == "begin" OR $action == "open"){
		if(is_array($action_response)){
			$active_project = $action_response["id"];
		}else{
			$active_project = $action_response;
		}
		$history["session"]["video_id"] = $active_project;
	}

	write_history();

	if($action_response === NULL){
		echo "\nNo Response or Invalid Response. Please check your arguments.";
	}else{
		echo json_encode($action_response, JSON_PRETTY_PRINT);
	}

	echo "\n";

