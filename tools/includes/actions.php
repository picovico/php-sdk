<?php
/** actions **/

$auth_actions = array("login", "authenticate", "set-login-tokens", "logout");

$stateless_actions = array("login", 
    "authenticate", 
    "profile", "set-login-tokens", "open", 
    "begin", "upload-image", 
    "get-videos",
    "duplicate", 
    "draft",
    "upload-music", "get-styles", "get");

$stateful_actions = array("save", "preview", "create", 
    "set-callback-url", "remove-credits", "add-credits", 
    "set-quality", "set-style", "add-library-music",
    "reset",
    "add-music",
    "add-text", "add-image", "add-library-image");

$supplement_actions = array("session", "project", "dump");

/** End of file **/