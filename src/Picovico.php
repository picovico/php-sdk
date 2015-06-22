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
 * Error Reporting switches
 * PS: Please change the error reporting level as required.
 */
if(defined("PICOVICO_DEBUG") AND PICOVICO_DEBUG === TRUE){
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
}else{
	error_reporting(0);
	session_start();
}

if (!function_exists('curl_init')) {
    throw new Exception('Picovico needs the CURL PHP extension.');
}

if (!function_exists('json_decode')) {
    throw new Exception('Picovico needs the JSON PHP extension.');
}

require_once __DIR__."/lib/exception.php";
require_once __DIR__."/lib/base.php";
require_once __DIR__."/lib/request.php";
require_once __DIR__."/lib/urls.php";
require_once __DIR__."/lib/utils.php";

/**
 * Picovico Class for end API developers
 * Handles all necessary steps related to the video definition and creation process. 
 */
class Picovico extends PicovicoBase{

    const API_VERSION = '2.1';
    const VERSION = '2.0.13';
    const API_SERVER = 'api.picovico.com';
    const API_SCHEME = 'https';

    /** Available Video rendering states */
    const VIDEO_INITIAL = "initial";
    const VIDEO_PUBLISHED = "published";
    const VIDEO_PROCESSING = "processing";

    /** Rendering Quality Levels */
    const Q_360P = 360; // ld
    const Q_480P = 480; // sd
    const Q_720P = 720; // md
    const Q_1080P = 1080; // hd

    const STANDARD_SLIDE_DURATION = 5;

    // Video Data for the final video request
    private $vdd = NULL;
    private $video_id = NULL;
    private $app_id = NULL; // provided during developer signup. 
    private $app_secret = NULL; // provided during developer signup. 
    private $device_id = NULL; // more a developer specific variable, which can be used to identify different deployment instances

	function __construct($app_id = NULL, $app_secret = NULL, $device_id = NULL){
		parent::__construct();
		$this->vdd = array();
		$this->video_id = NULL;
		$this->app_id = $app_id;
		$this->app_secret = $app_secret;
		if(!$device_id){
			$this->device_id = $this->generated_device_id();
		}
	}

	/**
	 * Login using Picovico username and password
	 * If logged in successfully, login tokens are set.
	 * @param $username
	 * @param $password
	 */
	function login($username, $password){

		$params = array('app_id'=>$this->app_id, 'username'=>$username,'password'=>$password, 'device_id'=>$this->device_id);

		$response = $this->request->make_request(PicovicoUrl::login, $params, NULL, PicovicoRequest::POST, PicovicoRequest::ANONYMOUS);

		if(isset($response['access_key']) AND isset($response['access_token'])){
			$this->set_login_tokens($response['access_key'], $response['access_token']);
		}

		return $response;
	}

	/**
 	 * Starting from 2.1 version of the API, all apps should authenticate themselves before they can make requests to the system.
	 * This function authenticates app with app_id and app_secret. The owner owning the application will be logged in then.
	 */
	function authenticate(){

		$params = array('app_id'=>$this->app_id,'app_secret'=>$this->app_secret, 'device_id'=>$this->device_id);

		$response = $this->request->make_request(PicovicoUrl::app_authenticate, $params, NULL, PicovicoRequest::POST, PicovicoRequest::ANONYMOUS);

		if(isset($response['access_key']) AND isset($response['access_token'])){
			$this->set_login_tokens($response['access_key'], $response['access_token']);
		}

		return $response;
	}

	/**
 	 * Account Profile /me
	 */
	function profile(){
		return $this->request->make_request(PicovicoUrl::me, array(), NULL, PicovicoRequest::GET);
	}

	/**
	 * If any saved access_key and access_token available, continue with those available tokens
	 * @param $access_key
	 * @param $access_token
	 */
	function set_login_tokens($access_key, $access_token){
		$this->request->set_tokens($access_key, $access_token);
	}

	/**
	 * Open any existing project which has not yet been rendered
	 * @param $video_id
	 */
	function open($video_id = NULL){
		$this->video_id = NULL;
		$this->vdd = array();
		if($video_id != NULL){
			$picovico_video = $this->get($video_id);
			if($picovico_video['status'] === Picovico::VIDEO_INITIAL){
				$this->video_id = $video_id;
				$this->vdd =  $picovico_video;
				
				// required due to the type incompatibility.
				$quality_cleanups = array();
				foreach($this->vdd["quality"] as $some_quality){
					$quality_cleanups[] = intval($some_quality);
				}
				$this->vdd["quality"] = max($quality_cleanups);
			}else{
				return FALSE;
			}
		}
		return $this->vdd;
	}

	/**
	 * Begin with an empty project.
	 * @param $name
	 * @param $quality - defaults to 360p
	 */
	function begin($name, $quality = Picovico::Q_360P){
		$this->video_id = NULL;
		$this->vdd = array();
		$params = array('name'=>$name, 'quality'=>$quality);
		$response = $this->request->post(PicovicoUrl::begin_project, $params);
		if($response['id']){
			$this->video_id = $response['id'];
			$this->vdd = $response;
			// truncate assets if defined already, open existing project to retain
			$this->vdd["assets"] = array();
		}
		return $this->video_id;
	}

	/**
	 * Upload local image file or any remote image to the logged in account.
	 */
	function upload_image($image_path, $source = NULL){
		return parent::upload_image($image_path, $source);
	}

	/**
	 * Upload local music file or any remote music to the logged in account. 
	 */
	function upload_music($music_path, $source = NULL){
		return parent::upload_music($music_path, $source);
	}

	/**
	 * Upload and append any image. Remote contents aren't downloaded locally.
	 * @param $image_path
	 * @param $caption
	 */
	function add_image($image_path, $caption = "", $source = "hosted"){
		$image_response = $this->upload_image($image_path, $source);
		if(isset($image_response["id"])){
			$this->add_library_image($image_response["id"], $caption);
		}
		return $image_response;
	}

	/**
	 * Append any image previously uploaded
	 */
	function add_library_image($image_id, $caption = ""){
		if($image_id){
			PicovicoBase::append_image_slide($this->vdd, $image_id, $caption);
			return true;
		}
		return False;
	}

	/**
	* List of uploaded / purchased musics
	*/
	function get_musics(){
		return $this->request->get(PicovicoUrl::get_musics);
	}

	/**
	* List of Available Musics in the Picovico library
	*/
	function get_library_musics(){
		return $this->request->get(PicovicoUrl::get_library_musics);
	}

	/**
	 * Append text slide to the project
	 */
	function add_text($title = "", $text = ""){
		if($title OR $text){
			PicovicoBase::append_text_slide($this->vdd, $title, $text);	
			return True;
		}
		return False;
	}

	/** 
	 * Define the backgroudn music
	 */
	function add_music($music_path){
		$music_response = $this->upload_music($music_path);
		if(isset($music_response["id"])){
			$this->add_library_music($music_response["id"]);
		}
		return $music_response;
	}

	/* 
	 * Define any previously uploaded music, or any music available from library. 
	 */
	function add_library_music($music_id){
		if($music_id){
			PicovicoBase::set_music($this->vdd, $music_id);
			return False;
		}
		return True;
	}

	/**
	* Delete music from your library
	*/
	function delete_music($music_id){
		if($music_id){
			$url = sprintf(PicovicoUrl::delete_music, $music_id);
			return $this->request->delete($url);
		}else{
			return False;
		}
	}

	/**
	 * Fetches styles available for the logged in account
	 */
	function get_styles(){
		$url = sprintf(PicovicoUrl::get_styles);
		return $this->request->make_request($url, NULL, NULL, PicovicoRequest::GET, PicovicoRequest::AUTHORIZED);
	}

	/**
	 * Defines style for the current video project
	 */
	function set_style($style_machine_name){
		if($style_machine_name){
			$this->vdd["style"] = $style_machine_name;
			return True;
		}
		return False;
	}

	/*
	 * Defines rendering quality for the current video project
	 */
	function set_quality($quality){
		if($quality){
			$this->vdd["quality"] = intval($quality);
			return True;
		}
		return False;
	}

	/**
	 * Append credit slides
	 */
	function add_credits($title = null, $text = null){
		if($title or $text){
			if(!isset($this->vdd["credit"])){
				$this->vdd["credit"] = array();
			}
			$this->vdd["credit"][] = array($title, $text);
			return TRUE;
		}
		return False;
	}

	/**
	 * Clear all credit slides
	 */
	function remove_credits(){
		$this->vdd["credit"] = array();
		return True;
	}

	/**
	 * Callback URL
	 */
	function set_callback_url($url){
		$this->vdd["callback_url"] = $url;
		return TRUE;
	}

	/**
	 * Fetch any existing video. Use open() for editing.
	 * @param $video_id - alphanumeric Identifier of the project
	 */
	function get($video_id){
		$url = sprintf(PicovicoUrl::single_video, $video_id);
		return $this->request->get($url);
	}

	/**
	 * Save the current progress with the project
	 */
	function save(){
		if(!$this->video_id){
			return NULL;
		}
		// fix music first
		parent::append_music($this->vdd);
		$url = sprintf(PicovicoUrl::save_video, $this->video_id);
		return $this->request->post($url, $this->vdd);
	}

	/**
	 * Make a preview request for the project. 
	 * Will generate 144p video is preview is available for the style.
	 * rendering state of the video will not be changed.
	 */
	function preview(){
		$response = $this->save();
		$url = sprintf(PicovicoUrl::preview_video, $this->video_id);
		return $this->request->post($url);
	}

	/**
	 * Send the actual rendering request to rendering engine
	 */
	function create(){
		$response = $this->save();
		$url = sprintf(PicovicoUrl::create_video, $this->video_id);
		return $this->request->post($url);
	}

	/** Duplicates any video and saves it to the new draft or overwrites if any exists */
	function duplicate($video_id){
		$url = sprintf(PicovicoUrl::duplicate_video, $video_id);
		return $this->request->post($url);
	}

	/** Gets a list of last 15 videos */
	function get_videos(){
		$url = PicovicoUrl::get_videos;
		return $this->request->get($url);
	}

	/* Resets the current local progress */
	function reset(){
		PicovicoBase::reset_music($this->vdd);
		PicovicoBase::reset_slides($this->vdd);
		$this->remove_credits();
		$this->vdd["style"] = NULL;
		$this->vdd["quality"] = NULL;
		return $this->vdd;
	}

	/** Returns the current draft saved */
	function draft(){
		$url = PicovicoUrl::get_draft;
		return $this->request->get($url);
	}

	/**
	* Creates a readable dump of the current project
	*/
	function dump(){
		if($this->vdd){
			return $this->vdd;
		}else{
			return false;
		}
	}
}

