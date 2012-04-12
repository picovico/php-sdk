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

// Picovico API Example

require_once dirname(__FILE__)."/"."picovico.php";

class Picovico_Example{

    var $config = array();

    var $picovico = null;

    function  __construct() {
        $this->config["access_token"] = "2|iOcYxmxvhS6aqBaKQvUCtX9kF3VWU5ju2D1xZnStPJU";
        $this->picovico = new Picovico($this->config);
    }
    
}

$pv_config = array();
$pv_config["access_token"] = "2|iOcYxmxvhS6aqBaKQvUCtX9kF3VWU5ju2D1xZnStPJU";

// Get Available Themes
$pv = new Picovico_Theme($pv_config);
//$themes = $pv->get_available_themes();
//Picovico::debug($themes);

$theme = $pv->get_theme("coolvibes");

// Create Video
$pv_video = new Picovico_Video($pv_config);

$pv_video->set_theme($theme);
$pv_video->set_callback_url("http://acpmasquerade.com/touch/picovico_callback.php?");
// some Music URL
//$pv_video->set_music_url("http://www.picovico.com/assets/music/classical/Laendler.mp3");
$pv_video->set_music_url("http://wp.rdandy.com/wp-content/uploads/2011/01/04-Waka-Waka-Esto-es-Africa.mp3");

// callback email
$pv_video->set_callback_email("acpmasquerade@gmail.com");

// add some random 10 text frames
$pv_video->add_text_frame(uniqid(), "@acpmasquerade");

//for($i = 0; $i < 10; $i++){
//    $pv_video->add_text_frame(uniqid(), "Text Frame");
//}

// add some image frames from the Recent Public list of Flickr
// Source: http://www.flickr.com/services/api/explore/flickr.photos.getRecent
$flickr_source = "http://api.flickr.com/services/rest/?method=flickr.photos.getRecent&api_key=93513456b90775bbf8198323d42fde83&format=json&nojsoncallback=1&per_page=25";
$flickr_stream = json_decode(file_get_contents($flickr_source), TRUE);

foreach($flickr_stream["photos"]["photo"] as $p){
    $flickr_image_url = "http://farm{$p["farm"]}.staticflickr.com/{$p["server"]}/{$p["id"]}_{$p["secret"]}.jpg";
    $flickr_image_page = "http://www.flickr.com/photos/{$p["owner"]}/{$p["id"]}";

    $pv_video->add_image_frame($flickr_image_url, "Source: {$flickr_image_page}");
}

$pv_video->shuffle_frames();
$video_token = $pv_video->create_video();

$output = <<<EOT
{$video_token}
<a href="https://api.picovico.com/video?access_token={$pv_config["access_token"]}&token={$video_token}">
Check Video Status
https://api.picovico.com/video?access_token={$pv_config["access_token"]}&token={$video_token}
</a>
EOT;

if (php_sapi_name () == "cli"){
    echo $output;
}else{
    echo nl2br($output);
}


// make a request of get_video
$pv_video_1 = $pv_video->get_video($video_token);

Picovico::debug($pv_video_1);