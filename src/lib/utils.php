<?php

	class PicovicoUtils{

		/**
		 * Checks if the argument is a local file
		 */
		public static function is_local_file($file_path){
	        $prefix = strtolower(substr($file_path, 0, 7));
	        if($prefix === "http://" || $prefix === "https:/"){
	            return false;
	        }
	        return true;
    	}

		/**
		 * Returns the Current Browsing url
		 *
		 * @return string The current URL
		 */
		public static  function get_current_url() {
		    if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1)
		            || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'
		    ) {
		        $protocol = 'https://';
		    } else {
		        $protocol = 'http://';
		    }
		    $currentUrl = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		    $parts = parse_url($currentUrl);
		    
		    // use port if non default
		    $port =
		            isset($parts['port']) &&
		            (($protocol === 'http://' && $parts['port'] !== 80) ||
		            ($protocol === 'https://' && $parts['port'] !== 443)) ? ':' . $parts['port'] : '';

		    // rebuild
		    
		    return $protocol . $parts['host'] . $port . $parts['path'] . $query;
		}
	}