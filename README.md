#Picovico - Official PHP SDK
Picovico is a online video creating platform.

##Picovico 2.0
Picovico 2.0, the latest version of Picovico, which has been re-engineered and re-systematized from the core. The latter version is improved towards providing better user experience and stability. SDK will be available for the new version very soon.

## API Version
2.1

##API Documentation
[picovico.readthedocs.org](http://picovico.readthedocs.org)

##Author
[picovico.com](http://picovico.com/)

## Getting Started
Picovico PHP-SDK is available on [Packagist](https://packagist.org/packages/picovico/php-sdk) via [composer](https://getcomposer.org)
```
{
    "require": {
        "picovico/php-sdk": "dev-master"
    }
}
```

##Functions Overview
* `login` - Login with username and password
* `authenticate` - Login with app_id and app_secret (API version 2.1 or above required)
* `set_login_tokens` - Set authentication tokens if saved earlier
* `open` - open any existing video project
* `begin` - begin with a new video project
* `upload_image` - upload local or remote image. (remote content is not downloaded locally. 250 chars max for remote content)
* `upload_music` - upload local or remote music. (remote content is not downloaded locally. 250 chars max for remote content)
* `add_image` - append image as next slide - (Uploads first, then adds to the project. No need to call upload_image if this function is used)
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
* `preview` - send a request to generate preview quality video
* `reset` - reset the project progress (resets the slides, quality, credits, style)
* `dump` - returns the local progress with active project

## Example Walkthrough
[examples/README.md](examples/README.md)

## Command-line Usage
A built-in command-line client `picovico.sh` is available under tools directory. The client is designed such that it provides a bash port to a self-client written using this SDK.
It provides all methods as above, except the `underscores` are replaced with `hyphens`.

Plus few more actions are specified.

### Usage
####Syntax:

````
$ picovico <action> <arg> <arg> ...
````

#### Bash Autocomplete
copy `tools/bash_completion.d/picovico` to `/etc/bash_completion.d/picovico`

#### Common examples
```
$ picovico login
```

```
$ picovico authenticate
```

```
$ picovico add-image
```

#### Extra actions available from command-line

Current cli session
```
$ picovico session
```

Current project from cli
```
$ picovico project
```

#### Authenticating in the command-line
The cli version requires following environment variables to set with the values obtained from your account.
 - PICOVICO_APP_ID
 - PICOVICO_APP_SECRET
 - PICOVICO_SDK

for example in `~/.bashrc`

```
export PICOVICO_APP_ID=<some-app-id>
export PICOVICO_APP_SECRET=<some-app-secret>
export PICOVICO_SDK=php
```

