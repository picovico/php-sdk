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

// Picovico API Example

// picovico
require_once dirname(__FILE__)."/../src/"."picovico.php";

class Picovico_Example{

    var $config = array();

    var $picovico_theme = null;
    var $picovico_video = null;

    function  __construct($config = array()) {
        $this->config = $config;

        $this->picovico_theme = new Picovico_Theme($this->config);
        $this->picovico_video = new Picovico_Video($this->config);

        if(!isset($_SESSION["video_tokens"])){
            $_SESSION["video_tokens"] = array();
        }

        // merge into the session, if any previous video tokens
        if($config["video_tokens"]){
            $video_tokens = $config["video_tokens"];
            $session_tokens = $_SESSION["video_tokens"];

            $all_video_tokens = array_unique(array_merge($video_tokens, $session_tokens));

            $_SESSION["video_tokens"] = $all_video_tokens;
        }
        
    }

    function themes(){
        $themes = $this->picovico_theme->get_available_themes();

        $content = "";

        foreach($themes as $t){
            $content .= "Theme : {$t->get_name()}\n
            ----------------------
            Sample URL: {$t->get_sample_url()}\n
            Thumbnail URL: {$t->get_thumbnail()}\n


            ======================

            ";
        }
        
        return $content;
    }

    function create_video(){

        $content = "";

        // add image frames
        $this->picovico_video->add_image_frame('http://farm7.static.flickr.com/6034/6227544215_fe9a9ed1ea_b.jpg', 'The predator and the prey');
        $this->picovico_video->add_image_frame('http://farm7.static.flickr.com/6169/6228061064_413bf3da13_b.jpg');
        $this->picovico_video->add_image_frame('http://farm7.static.flickr.com/6080/6115623115_ab728913f3_b.jpg', 'The eternal rays');

        // add a text frame
        $this->picovico_video->add_text_frame('Flora');

        // add more frames
        $this->picovico_video->add_image_frame('http://farm7.static.flickr.com/6014/5909306527_0ba1606f8f_b.jpg');
        $this->picovico_video->add_image_frame('http://farm5.static.flickr.com/4079/4764595958_fee8a036f5_b.jpg');

        // set a theme for the video
        $theme = Picovico_Theme::new_dummy_theme("vanilla");
        $this->picovico_video->set_theme($theme);

        // add title
        $this->picovico_video->set_title("Yet another Picovico Video");

        // add music url
        $this->picovico_video->set_music_url("http://www.picovico.com/assets/music/classical/Laendler.mp3");
        // or, any other
        //$this->picovico_video->set_music_url("http://wp.rdandy.com/wp-content/uploads/2011/01/04-Waka-Waka-Esto-es-Africa.mp3");

        // add callback url
        $this->picovico_video->set_callback_url("http://acpmasquerade.com/touch/picovico_callback.php?");

        // add callback email
        $this->picovico_video->set_callback_email("acpmasquerade@picovico.com");

        // generate video
        try{

            $response_token = $this->picovico_video->create_video();

            $_SESSION["video_tokens"][] = $response_token;

            $content .= "Video has been submitted for processing.\n
                Video Token : {$response_token}\n
                ";

        } catch (Picovico_Exception $e){
            $content .= "Error submitting video request\n
                Reason: {$e->getType()}\n
                ";
        }

        return $content;
        
    }

    function video($token = null){

        $content = "";
        
        if($token){

            try{
                $video = $this->picovico_video->get_video($token);

                $content .= "Video Status : ". $video->get_status_message() . "\n";

                if($video->get_status() == Picovico_Config::VIDEO_STATUS_COMPLETE){
                    $content .= "\nVideo is COMPLETE \n
                        Total Duration : {$video->get_duration()} \n
                        Video URL: {$video->get_url()} \n
                        Video Thumbnail: {$video->get_thumbnail()} \n";
                }
            }
            catch (Picovico_Exception $e){
                $content .= " \nERROR: {$e->getType()}\n";
            }
            
        }

        $content .= "\nOther Videos\n---------------------\n";

        // check the list of videos from session
        foreach($_SESSION["video_tokens"] as $some_token){
            if($some_token == $token){
                continue;
            }

            $content .= "{$some_token}\n";            
        }

        return $content;
    }
}

// config
require_once dirname(__FILE__)."/"."config.example.php";

// load config into the example class.
$pv_example = new Picovico_Example($PV_config);



// ----------------------------- //
// -- Run the Examples --------- //
// ----------------------------- //

// select one of the examples, from the list.

# Example 1 : Check Themes
# ($PV_config["example"] = "themes";)

# Example 2 : Create Video
# ($PV_config["example"] = "create";)

# Example 3 : Check a Video Status
# ($PV_config["example"] = "video";)
# ($PV_config["example_video_token"] = "some-video-token";)

$example = $PV_config["example"];
$example_video_token = $PV_config["example_video_token"];

switch($example){
    case "themes":
        $content = $pv_example->themes();
        break;
    case "create":
        $content = $pv_example->create_video();
        break;
    case "video":
        $content = $pv_example->video($example_video_token);
}


$content = preg_replace("/[ ]+/", " ", $content);


if (php_sapi_name() != 'cli') {
    echo "<pre>";
}

echo $content;

die();
