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
$app = new Picovico();
$app->authenticate('PICOVICO_APP_ID', 'PICOVICO_APP_SECRET');
// use login method to login directly with username and password (not recommended though)
// $app->login('PICOVICO_USERNAME', 'PICOVICO_PASSWORD');
$project_id = $app->begin('Hello World');
$app->set_style('vanilla');
$app->add_text('Hello World', 'Let\'s Picovico');
$app->add_image('http://s3-us-west-2.amazonaws.com/pv-styles/christmas/pv_christmas_winter_themes.png');
$app->add_image('http://s3.amazonaws.com/pvcdn2/video/8501d6865c2d484abb2e8a858cffca80/8501d6865c2d484abb2e8a858cffca80-360.jpg', 'Image captions are optional');
$app->add_image('http://www.picovico.com/blog/wp-content/uploads/2014/12/Yearbook-Screenshot.jpg');
$app->add_music('http://s3.amazonaws.com/picovico-1/assets/music/Latin/Latinish.mp3');
$app->add_credits('Music', 'Frank Nora');
$app->set_quality(Picovico::Q_360P);
$app->create();
```

Use the project_id saved earlier to check status of your video
```php
$app->get($project_id);
```


