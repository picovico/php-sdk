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


// Picovico API 2.0

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__."/config.php";
require_once __DIR__."/../../src/"."Picovico.php";

# LOGIN or set saved tokens
// check session storage
$app = new Picovico($PICOVICO_APP_ID, $PICOVICO_APP_SECRET, PICOVICO_DEVICE_ID);

function pv_dump($title, $arg = null){
	echo "[*] {$title}";
	echo "\n";
	if($arg !== null){
		print_r($arg);
		echo "\n\n";
	}
}

if(isset($_SESSION["PICOVICO_SESSION"])){
	$PICOVICO_ACCESS_KEY = $_SESSION["PICOVICO_SESSION"]["access_key"];
	$PICOVICO_ACCESS_TOKEN = $_SESSION["PICOVICO_SESSION"]["access_token"];
	
	$app->set_login_tokens($PICOVICO_ACCESS_KEY, $PICOVICO_ACCESS_TOKEN);
}else{
	try{
		$login = $app->authenticate();
		$PICOVICO_ACCESS_KEY = $login["access_key"];
		$PICOVICO_ACCESS_TOKEN = $login["access_token"];
		$_SESSION["PICOVICO_SESSION"] = $login;
	
		pv_dump("Logged in as", $login["id"]);
	}catch(Exception $e){
		echo $e;
		die();
	}
}


pv_dump("Profile", $app->profile());
