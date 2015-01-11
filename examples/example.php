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

// just to save the tokens,
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Picovico API Example

// picovico
require_once __DIR__."/example.config.php";
require_once __DIR__."/../src/"."picovico.php";

# LOGIN or set saved tokens
// check session storage
$app = new Picovico();

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
		$login = $app->login($PICOVICO_USERNAME, $PICOVICO_PASSWORD);
		$PICOVICO_ACCESS_KEY = $login["access_key"];
		$PICOVICO_ACCESS_TOKEN = $login["access_token"];
		$_SESSION["PICOVICO_SESSION"] = $login;
	
		pv_dump("Logged in as", $login["id"]);
	}catch(Exception $e){
		echo $e;
		die();
	}
}

// Begin
try{
	$project_id = $app->begin("Demo Project");
	pv_dump("Project", $project_id);

	pv_dump("Add Text");
	$app->add_text("PICOVICO", "API Demo");

	pv_dump("Uploading Image 1");
	$r = $app->add_image("https://farm6.staticflickr.com/5529/11078119266_96b048acfc_k_d.jpg", "Pick any image", "flickr");
	pv_dump("Uploading Image 2");
	sleep(1);
	$r = $app->add_image("https://farm8.staticflickr.com/7408/11078075176_b60bfe6f0a_k_d.jpg", "And a background music", "flickr");
	pv_dump("Uploading Image 3");
	$r = $app->add_image("https://farm4.staticflickr.com/3796/11078026996_f6304ada65_k_d.jpg", "Did you see the caption ?", "flickr");
	sleep(1);
	pv_dump("Uploading Image 4");
	$r = $app->add_image("https://farm6.staticflickr.com/5521/11027474124_c94b42a0e2_k_d.jpg", "", "flickr");
	sleep(1);
	pv_dump("Uploading Image 5");
	$r = $app->add_image("https://farm8.staticflickr.com/7437/11027337296_48822cc37a_h_d.jpg", "and you missed one caption.", "flickr");
	pv_dump("Uploading Image 6");
	$r = $app->add_image("https://farm3.staticflickr.com/2813/11027391384_afea13950a_k_d.jpg", "still reached to end of the video.", "flickr");

	pv_dump("Style");
	$app->set_style("vanilla");

	pv_dump("Quality");
	$app->set_quality(Picovico::Q_360P);

	pv_dump("Add Music");
	$app->add_music("https://s3-us-west-2.amazonaws.com/pv-audio-library/samples/freemusicarchive.org.the.impossebulls.02.havenots.mascot.revolution.mp3");
	$app->add_credits("Music", "The Impossebulls\nfreemusicarchive.org");

	$r = $app->create();

	// Video is being created.
	pv_dump("LOOPing 15 seconds to check video status");
	while(true){		
		sleep(15);
		$video = $app->get($project_id);
		if(isset($video["video"])){
			pv_dump("Video READY", $video["video"]);
			break;
		}
		pv_dump("Video", $video);
	}

}catch(Exception $e){
	echo $e;
	die();
}
