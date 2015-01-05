#Picovico - Official PHP SDK
Picovico is a online video creating platform.

##Picovico 2.0
Picovico 2.0, the latest version of Picovico, which has been re-engineered and re-systematized from the core. The latter version is improved towards providing better user experience and stability. SDK will be available for the new version very soon.


##API Documentation
[picovico.readthedocs.org](http://picovico.readthedocs.org)

##Author
[picovico.com](http://picovico.com/)

##Functions Overview
* `login` - Login with username and password
* `set_login_tokens` - Set authentication tokens if saved earlier
* `open` - open any existing video project
* `begin` - begin with a new video project
* `upload_image` - upload local or remote image. (remote content is not downloaded locally)
* `upload_music` - upload local or remote music. (remote content is not downloaded locally)
* `add_image` - append image as next slide
* `add_library_image` - append previouosly uploaded image as next slide
* `add_text` - append text as next slide
* `add_music` - define background music
* `add_library_music` - define previously uploaded content as background music
* `get_styles` - get styles available for the account
* `set_style` - define style for the active video project
* `set_quality` - define quality for the active video project
* `add_credits` - define credit slides for the active video project (Credits appear at the end of video)
* `remove_credits` - remove all the credits defined in the active video
* `get` - request information about any previously existing video (Is available for editing after `open()` only)
* `save` - save the video progress (automatically saved before creating)
* `create` - request to render the video.

