<?php

	error_reporting(0);
	define("PICOVICO_DEBUG", false);

	include __DIR__."/../src/Picovico.php";
	include __DIR__."/includes/functions.php";
	include __DIR__."/includes/actions.php";

	/** @TODO
		* Create a CLIENT Class and refactor accordingly
		* Move all the variables inside the class scope.
		* Get rid of global variables wherever possible.
		**/

	// $argv used before identifying actions.
	// client_arguments used afterwise. 
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

	// Remove all prepends and use the remaining arguments. 
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

	// authenticate and save the history
	if(is_auth_action($action)){
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
	}elseif(is_valid_action($action)){
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

