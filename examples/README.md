# Picovico API 2.0
## Example using PHP-SDK 2.0

The official PHP SDK supports following API features
- Authenticate
- Begin a new video project
- Edit a draft video project
- Append Image Slides, Upload local image or specify image at any remote location
- Append Text Slides
- Define Music, Upload local music or specify music at any remote location
- Define Styles, Both public and user specific styles are supported
- Append Credits
- Request to render a draft video project

## Basic walkthrough example
```php
<?php
include 'src/Picovico.php';
$app = new Picovico('PICOVICO_APP_ID', 'PICOVICO_APP_SECRET', 'PICOVICO_DEVICE_ID');
$r = $app->authenticate();
  print_r($r);
// use login method to login directly with username and password (not recommended though)
// $app->login('PICOVICO_USERNAME', 'PICOVICO_PASSWORD');
$project_id = $app->begin('Hello World'); 
  print_r($project_id);
$r = $app->set_style('vanilla');
  print_r($r);
$r = $app->add_text('Hello World', 'Let\'s Picovico');
  print_r($r);
$r = $app->add_image('http://s3-us-west-2.amazonaws.com/pv-styles/christmas/pv_christmas_winter_themes.png');
  print_r($r);
$r = $app->add_image('http://s3.amazonaws.com/pvcdn2/video/8501d6865c2d484abb2e8a858cffca80/8501d6865c2d484abb2e8a858cffca80-360.jpg', 'Image captions are optional');
  print_r($r);
$r = $app->add_text("Thank You", "Namaste!!!");
  print_r($r);
$r = $app->add_music('https://s3-us-west-2.amazonaws.com/pv-audio-library/free-music/preview/Latin/Latinish.mp3');
  print_r($r);
$r = $app->add_credits('Music', 'Frank Nora');
  print_r($r);
$r = $app->set_quality(Picovico::Q_360P);
  print_r($r);
$r = $app->create();
  print_r($r);
```

Use the project_id saved earlier to check status of your video
```php
$r = $app->get($project_id);
```

## Alternate Implementation
Because Picovico API allows to create ONLY ONE Draft per account, its good to upload assets / music before creating the project.
```php
<?php
/// ... authentication steps as above ... 
$project_assets = array();
$project_assets[] = array("text", "Hello World", "Let's Picovico");
$r = $app->upload_image('http://s3-us-west-2.amazonaws.com/pv-styles/christmas/pv_christmas_winter_themes.png', "hosted");
$project_assets[] = array("image", $r["id"], "some-caption-if-required");
$r = $app->upload_image('http://s3.amazonaws.com/pvcdn2/video/8501d6865c2d484abb2e8a858cffca80/8501d6865c2d484abb2e8a858cffca80-360.jpg', "hosted");
$project_assets[] = array("image", $r["id"], "some-caption-if-required");
$project_assets[] = array("text", "Thank You", "Namaste");
// .... begin the project now ...
$project_id = $app->begin('Hello World'); 
$r = $app->set_style('vanilla');
foreach($project_assets as $some_asset){
  if($some_asset[0] == "image"){ $app->add_library_image($some_asset[1], $some_asset[2]); }
  elseif($some_asset[0] == "text"){ $app->add_text($some_asset[1], $some_asset[2]); }
  else{ 
    // .. some invalid asset. please see if the type is either text or image 
  }
}
$r = $app->add_library_music('NhLIs');
$r = $app->add_credits('Music', 'Sunshine (Kevin MacLeod)');
$r = $app->set_quality(Picovico::Q_360P);
$r = $app->create();
```
