<?php

/**
 * Script to get PC User in JSON format by created Alias
 * 
 * Personalization by Alias consists of following steps:
 *  - create alias and store them in tmp-file (in real application you should use database)
 *    done by this create_alias.php
 *  - privide the Alias value to a user
 *  - a user types this alias into the mobile app
 *  - mobile app makes a request to this sample to script /pers/alias/get_pc_user.php (this script)
 *    and provides the Alias
 *  - get_pc_user.php calls PC Server to create a PC User and export them in JSON-format
 *    to encrypt a keys this script uses "activation_code" value
 *    see https://repo.payconfirm.org/server/doc/v5/rest-api/#json-export-key
 *  - mobile app imports a PC Users and asks from a user activation_code value
 *  - done
 * 
 * Input JSON sample:
 *    {"alias":"F33P27ON"}
 */

include('../../config.php');

// read input JSON
$php_input = file_get_contents('php://input');
$request = (array) json_decode($php_input, true);

// check if alias is specified in the request
if (!isset($request['alias'])) {
    header("HTTP/1.0 400 Bad Request", true, 400);
    header("Content-Type: application/json");
    die(json_encode(array('error'=>'alias value not specified')));
}

$alias_value = $request['alias'];

// check if alias value was created earlier (stored in tmp-file) and get activation_code
$alias = get_alias($alias_value);
if (null == $alias) {
    header("HTTP/1.0 400 Bad Request", true, 400);
    header("Content-Type: application/json");
    die(json_encode(array('error'=>'alias not found')));
}

// Minimal create user params
$create_user_params = array(
    'id_prefix' => 'sample-',
    'key_encryption_password' => $alias[$alias_value],  // activation code
    'return_key_method' => 'KEY_JSON'
);

// !!! WARNING - we use stored Activation Code for DEMO PURPOSES ONLY
//     You should create Activation Code at the moment when you requests key JSON from PC Server
//     It means - here
//     After you have created Activation Code you should send it to a user with another channel
//     It can be email, SMS, push or something else
//
//     We can not send SMS or something here in demo, that's why we use stored activation code

// encode params to JSON
$data_string = json_encode($create_user_params);

// build request url
$register_user_url = $pc_url ."/" .$system_id ."/users";

// make a request
if (!pc_request(
    $register_user_url,
    $data_string,
    'user_created',
    $user_info,
    $error_description,
    $error_code
)) {
    // if error - die
    header("HTTP/1.1 500 Internal request failed", true, 500);
    die("Call to PC failed. Error code: " .$error_code .", error description: " .$error_description);
}

// Format and return the result
$result = array();
$result['user_id'] = $user_info['user_id'];
$result['key_json'] = $user_info['key_json'];
$result['activation_code'] = $alias[$alias_value];

header("Content-Type: application/json");
print(json_encode($result));

?>