<?php 

/** functions.php  **/

function is_stateful_action($action){
    global $stateful_actions;
    return in_array($action, $stateful_actions);
}

function is_auth_action($action){
    global $auth_actions;
    return in_array($action, $auth_actions);
}

function is_stateless_action($action){
    global $stateless_actions;
    return in_array($action, $stateless_actions);
}

function is_valid_action($action){
    return is_stateless_action($action) OR is_stateful_action($action);
}

function is_supplement_action($action){
    global $supplement_actions;
    return in_array($action, $supplement_actions);
}

function do_auth_action(){
    global $action;
    global $action_function;
    global $client_arguments;
    global $client;

    if ($action == "logout"){
        clear_history();
        echo "true\n";
        exit(0);
    }else{
        return call_user_func_array(array($client, $action_function), $client_arguments);
    }
}

function do_supplement_action(){
    global $action;
    global $action_function;
    global $history;
    global $client;
    switch ($action) {
        case 'session':
            # code...
            $response_message = $history["session"];
            unset($response_message["object"]);
            return $response_message;
            break;
        case "project":
        case "dump":
            #
            return $client->dump();
            break;
    }
}

function do_other_action(){
    global $active_project;
    global $action;
    global $action_function;

    global $client;
    global $client_arguments;
    
    if(is_stateful_action($action)){
        if($active_project === NULL){
            echo "FATAL: Please start project first either with <begin> or <open>.\n";
            exit(1);
        }else{
            // call method with teh arguments
            return  call_user_func_array(array($client, $action_function),  $client_arguments);
        }
    }else{
        return call_user_func_array(array($client, $action_function),  $client_arguments);
    }
}

function write_history(){
    global $history;
    global $history_file;
    global $client;
    global $client_arguments;
    global $action;

    global $action_response;

    $history["action"] = $action;
    $history["args"] = $client_arguments;
    $history["response"] = $action_response;

    $history["session"]["object"] = serialize($client);
    file_put_contents($history_file, json_encode($history, JSON_PRETTY_PRINT));
}

function clear_history(){
    global $history;
    global $history_file;
    $history = array();
    file_put_contents($history_file, json_encode($history, JSON_PRETTY_PRINT));
    return True;
}

function raise_exception($e){
    echo "FATAL: API Exception";
    echo "\n";
    echo $e;
    echo "\n";
    exit(2);
}

/** End of file **/