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
error_reporting(0);
session_start();

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

class Picovico extends PicovioBase{

	const API_VERSION = '2.0';
    const VERSION = '2.0.1';
    const API_SERVER = 'uapi-f1.picovico.com';

    const VIDEO_INITIAL = "initial";
    const VIDEO_PUBLISHED = "published";
    const VIDEO_PROCESSING = "processing";

    const Q_360P = "360";
    const Q_480P = "480";
    const Q_720P = "720";
    const Q_1080P = "1080";

    // Video Data for the final video request
    private $vdd = null;
    private $video_id = NULL;

	function __construct($config){
		parent::__construct($config);
	}

	/**
	 * Login using Picovico username and password
	 * If logged in successfully, login tokens are set.
	 * @param $username
	 * @param $password
	 */
	function login($username, $password){

		$params = array('username'=>$username,'password'=>$password, 'device_id'=>$this->generated_device_id());

		$response = $this->request->make_request(PicovicoUrl::login, $params, NULL, PicovicoRequest::POST, PicovicoRequest::ANONYMOUS);

		if(isset($response['access_key']) AND isset($response['access_token'])){
			$this->set_login_tokens($response['access_key'], $response['access_token']);
		}

		return $response;
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
	 * Open any existing project, not rendered yet
	 * @param $video_id
	 */
	function open_project($video_id = NULL){
		if($video_id != NULL){
			$picovico_video = $this->get_video($video_id);
			if($picovico_video['status'] === Picovico::VIDEO_INITIAL){
				$this->video_id = $video_id;
			}else{
				$this->video_id = NULL;
				return NULL;
			}
		}else{
			// video_id is required
			return NULL;
		}
	}

	/**
	 * Start a new project. Automatically overwrites any existing project if still initial
	 * @param $name
	 * @param $quality - defaults to 360p
	 */
	function start_project($name, $quality = Picovico::Q_360P){
		$params = array('name'=>$name, 'quality'=>$quality);
		$response = $this->request->post(PicovicoUrl::create_video, $params);
		if($response['id']){
			$this->video_id = $response['id'];
			return $this->video_id;
		}
		return NULL;
	}

	function add_image($image_path, $caption = ""){
		if($this->is_local_file($image_path)){
			$this->request->put(array("file"=>$image_path))
		}
	}

	function add_library_image($image_id, $caption = ""){

	}

	function add_text($title = "", $body = ""){

	}

	function add_music($music_path){

	}

	function add_library_music($music_id){

	}

	function save_video(){

	}

	function create_video(){

	}

	function get_video($video_id){
		$url = sprintf(PicovicoUrl::get_video, $video_id);
		return $this->request->get($url, NULL, NULL, PicovicoRequest::GET, PicovicoRequest::AUTHORIZED);
	}
}