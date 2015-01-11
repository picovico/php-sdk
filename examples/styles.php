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

// Account Login and APP authentication
include_once __DIR__."/lib/base.php";

// List Publicly available and User purchased 
try{ 
	$styles = $app->get_styles();
	$summary = array();
	foreach($styles as $s){
		$summary["{$s["machine_name"]}"] = array("name"=>$s["name"], "desc"=>$s["description"], "category"=>$s["category"]); 
	}
	pv_dump("Available Styles",$summary);

}catch(Exception $e){
	echo $e;
	die();
}
