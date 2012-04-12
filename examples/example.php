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

        $content = "<ol class='themes'>";

        foreach($themes as $t){
            $content .=
            "<li><a href='{$t->get_sample_url()}'>
                <img src='{$t->get_thumbnail()}' width='250'/>
                <br />
                <span>
                    {$t->get_name()}
                </span>
                </a>
            </li>
            ";
        }

        $content .= "</ol>
            <hr />

            <h3>Code</h3>
            <hr />
            <pre>
                \$config = array();
                \$config['access_token'] = 'some-acces-tken';
                \$pv = new Picovico_Theme(\$config);
                \$pv->get_available_themes();
                
            </pre>

            ";

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

        if($_POST["create"]){

            // generate video
            try{

                $response_token = $this->picovico_video->create_video();

                $_SESSION["video_tokens"][] = $response_token;

                $content .= "<hr />
                    Video has been submitted for processing.
                    <hr />
                    Token is : {$response_token}
                    <br />

                    Check the status of Video -

                    <a href='?video={$response_token}'>
                        {$response_token}
                    </a>
                    ";

            } catch (Picovico_Exception $e){
                $content .= "
                    Error submitting video request
                    <hr />
                    Reason: <strong>{$e->getType()}</strong>
                    ";
            }
            
        }else{

        }

        $content .= "

            <form method='POST' action='?create'>
                <center>                    
                    <input type='submit' name='create' value='Click the button to proceed creating a video'>
                </center>
            </form>

            <hr />

            <h3>Code</h3>
            <hr />
            <pre>
                // config
                \$config = array();
                \$config['access_token'] = 'some-acces-tken';

                // picovico video object
                \$pv = new Picovico_Video(\$config);

                // add some text frames
                \$pv->add_text_frame('Text title', 'text description')

                // add some image frames
                \$pv->add_image_frame('http://some-image-url/', 'some-image-caption');
                \$pv->add_image_frame('http://some-image-url/', 'some-image-caption');
                \$pv->add_image_frame('http://some-image-url/', 'some-image-caption');
                \$pv->add_image_frame('http://some-image-url/', 'some-image-caption');


                // set video title
                \$pv->set_title('Hello Picovico');


                // set video description
                \$pv->set_description('some-video-description');


                // set the music url ( mandatory )
                \$pv->set_music_url('//some-music-url-.mp3');


                // set the callback url ( mandataory ) 
                \$pv->set_callback_url('//some-callback-url');


                // set a callback email ( optional ) 
                \$pv->set_callback_email('acpmasquerade@picovico.com');


                // set a video theme ( mandatory ) 
                \$theme = Picovico_Theme::new_dummy_theme('vanilla');
                \$pv->set_theme(\$theme);

                // create video 
                \$response = \$pv->create_video();

                // \$response is the token for the video just submitted. 

            </pre>



            ";

        return $content;
        
    }

    function video(){

        $content = "";
        
        $token = $_GET["video"];
        
        if($token){

            try{
                $video = $this->picovico_video->get_video($token);

                $content .= "<hr />
                Status - <strong>". $video->get_status_message() . "</strong>"
                ."
                ";

                if($video->get_status() == Picovico_Config::VIDEO_STATUS_COMPLETE){
                    $content .= "
                        <hr />
                        Total Duration : "  .$video->get_duration() . "
                        <br />
                        <a href='".$video->get_url().">".$video->get_thumbnail()."<br />
                            Check the Video
                            </a>'
                        ";
                }
            }
            catch (Picovico_Exception $e){
                $content .= " <hr />ERROR: <strong>{$e->getType()}</strong>";
            }

            
        }


        $content .= "<hr />
            <h3>
            Other Videos
            </h3>
            <ul>";

        // check the list of videos from session
        foreach($_SESSION["video_tokens"] as $some_token){
            if($some_token == $token){
                continue;
            }

            $content .= 
            "<li> <a href='?video={$some_token}'>".$some_token."</a></li>";
            
        }

        $content .= "</ul>";

        $content .= "<hr />
            <h3>Code</h3>
            <hr />
            <pre>
            \$config = array ();
            \$config['access_token'] = 'some-access-token';
        
            \$pv = new Picovico_Video();

            \$v = \$pv->get_video('some-video-token');

            </pre>

        ";

        return $content;
    }
}

// config
require_once dirname(__FILE__)."/"."config.example.php";

// load config into the example class.
$pv_example = new Picovico_Example($PV_config);

if(isset($_GET["themes"])){
    $content = $pv_example->themes();
}elseif(isset($_GET["create"])){
    $content = $pv_example->create_video();
}elseif(isset($_GET["video"])){
    $content = $pv_example->video();
}

// Generate View
$HTML = <<<HTML
    <html>
        <head>
            <title>
                Picovico - Example [acpmasquerade@picovico.com]
            </title>
        </head>
        <body>
            <style type="text/css">
                body{
                    margin:0;padding:10px;
                }
                hr{
                    height:1px; width:100%;margin:5px 0; padding:0; background:#aaa; border:0;
                }
                .navigation {
                    border-top:1px solid #aaa;
                    border-bottom:1px solid #aaa;
                    padding:10px;
                    margin:10px 0;
                    background:#fafafa;
                }
                .navigation a{
                    border-left:1px solid #aaa;
                    border-right:1px solid #aaa;
                    padding:0 5px;
                    margin:0 5px;
                    display:inline-block;
                    color:black;
                    text-decoration:underline;
                }
                .themes li
                {
                    border:1px dashed #aaa;
                    padding:5px;
                    display:inline-block;
                    background:#fafafa;
                }

                .themes{
                    padding:0;margin:0;
                }
            </style>
            <h1>Picovico Examples</h1>
            <h5>acpmasquerade@picovico.com</h5>
            <div class="navigation">
                <a href="?themes">Themes</a> -
                <a href="?create">Create Video</a> -
                <a href="?video">My Videos</a>
            </div>
            <div class="content">
                {$content}
            </div>
        </body>
    </html>
HTML;

echo $HTML;

//$pv_example->themes();


die();