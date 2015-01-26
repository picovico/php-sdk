<?php

/**
 * Provides access to the Picovico Application Platform
 * 
 * @author acpmasquerade <acpmasquerade@picovico.com>
 */
class PicovicoBase {
    
    /**
     * The access_token
     *
     * @var string
     */
    protected $request = null;

    /**
     * Initialize a Picovico Application.
     *
     * @param array $config The application configuration
     */
    public function __construct() {
        $this->request = new PicovicoRequest();
    }

	public static function generated_device_id(){
		if(defined("PICOVICO_DEVICE_ID")){
			return PICOVICO_DEVICE_ID;
		}
		return "PICOVICO_PHP_SDK_2.1";
	}

    /**
     * Analyzes the supplied result to see if it was thrown
     * because the access token is no longer valid.  If that is
     * the case, then the persistent store is cleared.
     *
     * @param $result array A record storing the error message returned
     *                      by a failed API call.
     */
    public static function throw_api_exception($result) {
        $e = new PicovicoException($result);
        throw $e;
    }

    /**
     * Prints to the error log if you aren't in command line mode.
     *
     * @param string $msg Log message
     */
    protected static function error_log($msg) {
        // disable error log if we are running in a CLI environment
        if (php_sapi_name() != 'cli') {
            error_log($msg);
        }
    }

    /**
     * Dumps a readable output for a variable
     * 
     * @param <type> $var variable to debug
     */
    public static function debug($var){
        if (php_sapi_name() != 'cli') {
            echo "<pre>";
        }
        print_r($var);
        
        die();
    }

    public function is_logged_in(){
        return $this->request->is_logged_in();
    }

    /**
     * Upload if local image file, import if a remote file
     */
    protected function upload_image($file_path, $source = NULL){
        if(PicovicoUtils::is_local_file($file_path)){
            return $this->request->put(PicovicoUrl::upload_photo, $file_path);
        }else{
            return $this->request->post(PicovicoUrl::upload_photo, array("url"=>$file_path, "source"=>$source, "thumbnail_url"=>$file_path));
        }
    }

    /**
     * Upload if local music file, import if a remote file
     */
    protected function upload_music($file_path, $source = NULL){
    if(PicovicoUtils::is_local_file($file_path)){
            return $this->request->put(PicovicoUrl::upload_music, $file_path, array("X-Music-Artist"=>"Unknown", "X-Music-Title"=>"Unknown - ".date('r')));
        }else{
            return $this->request->post(PicovicoUrl::upload_music, array("url"=>$file_path, "preview_url"=>$file_path));
        }
    }

    /**
     * Appends a slide onto the video project.
     */
    protected static function append_vdd_slide(&$vdd, $slide){
        if($vdd){
            if(!is_array($vdd["assets"])){
                $vdd["assets"] = array();
            }
            $last_slide = NULL;
            $current_slides_count = count($vdd["assets"]);
            $last_end_time = 0;
            if($vdd["assets"]){
                $last_slide = $vdd["assets"][count($vdd["assets"]) - 1];
                if(is_array($last_slide)){
                    $last_end_time = $last_slide["end_time"]; 
                }else{
                    $last_end_time = $last_slide->end_time;
                }
            }
            $slide->start_time = $last_end_time;
            $slide->end_time = $last_end_time + Picovico::STANDARD_SLIDE_DURATION;
            $vdd["assets"][] = $slide;
        }
    }

    /**
     * Prepares the slide data for image slides and appends to the vdd
     */
    protected static function append_image_slide(&$vdd, $image_id, $caption = NULL){
        $template = new stdClass();
        $template->name = "image";
        $template->data = new stdClass();
        $template->data->text = $caption; 
        $template->asset_id = $image_id; 
        self::append_vdd_slide($vdd, $template);
    }

    /**
     * Prepares the slide data for text slides and appends to the vdd
     */
    protected static function append_text_slide(&$vdd, $title = NULL, $text = NULL){
        $template = new stdClass();
        $template->name = "text";
        $template->data = new stdClass();
        $template->data->text = $text;
        $template->data->title = $title; 
        self::append_vdd_slide($vdd, $template);
    }

    /**
     * Saves music for the current video project.
     * Saved separately because only one music is supported.
     */
    protected function set_music(&$vdd, $music_id){
        $template = new stdClass();
        $template->name = "music";
        $template->asset_id = $music_id;
        $template->_comment_ = "Will be replaced later";
        $vdd["_music"] = $template;
    }

    /**
     * If music is set and not appended to the VDD slide, appends the music as vdd slide
     */
    protected static function append_music(&$vdd){
        if(isset($vdd["_music"])){
            self::append_vdd_slide($vdd, $vdd["_music"]);
            unset($vdd["_music"]);
        }
    }
}
