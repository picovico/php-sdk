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

$pv_config = array();
$pv_config["access_token"] = "2|iOcYxmxvhS6aqBaKQvUCtX9kF3VWU5ju2D1xZnStPJU";

// Get Available Themes
$pv = new Picovico_Theme($pv_config);
//$themes = $pv->get_available_themes();
//Picovico::debug($themes);

//$theme = $pv->get_theme("coolvibes");
//Picovico::debug($theme);

// Create Video

