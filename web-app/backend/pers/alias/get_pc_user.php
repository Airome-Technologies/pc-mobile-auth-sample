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

// check if alias value was created earlier (stored in tmp-file)
$alias = get_alias($alias_value)[$alias_value];
if (null == $alias) {
    header("HTTP/1.0 400 Bad Request", true, 400);
    header("Content-Type: application/json");
    die(json_encode(array('error'=>'alias not found')));
}

// check if the alias already has been used and it has corresponded PC User ID
if (null != $alias['pc_user_id']) {
    // if pc_user_id has been set, then we call PC User Update
    $pc_request_url = $pc_url ."/" .$system_id ."/users/" .$alias['pc_user_id'] ."/key";    // update PC User endpoint

    // Minimal update user params
    $pc_request_params = array(
        'key_encryption_password' => $alias['activation_code'],  // activation code
        'return_key_method' => 'KEY_JSON'
    );

    $pc_expected_answer = 'key_updated';   // expected answer from PC Server

} else {
    // if pc_user_id has been not set, then we create PC User

    $pc_request_url = $pc_url ."/" .$system_id ."/users";   // create PC User endpoind

    // Minimal create user params
    $pc_request_params = array(
        'id_prefix' => 'sample-',
        'key_encryption_password' => $alias['activation_code'],  // activation code
        'return_key_method' => 'KEY_JSON'
    );

    $pc_expected_answer = 'user_created';   // expected answer from PC Server
}

// !!! WARNING - we use stored Activation Code for DEMO PURPOSES ONLY
//     You should create Activation Code at the moment when you requests key JSON from PC Server
//     It means - here
//     After you have created Activation Code you should send it to a user with another channel
//     It can be email, SMS, push or something else
//
//     We can not send SMS or something here in demo, that's why we use stored activation code

// encode params to JSON
$data_string = json_encode($pc_request_params);

// make a request
if (!pc_request(
    $pc_request_url,
    $data_string,
    $pc_expected_answer,
    $user_info,
    $error_description,
    $error_code
)) {
    // if error - die
    header("HTTP/1.1 500 Internal request failed", true, 500);
    die("Call to PC failed. Error code: " .$error_code .", error description: " .$error_description);
}

// store PC User ID to alias value
if ($persistent_alias) {
    $updated_alias = array($alias_value => array(
        "activation_code" => $alias['activation_code'],
        "pc_user_id" => $user_info['user_id'])
    );
    store_alias($updated_alias);
}

// Format and return the result
$result = array();
$result['user_id'] = $user_info['user_id'];
$result['key_json'] = $user_info['key_json'];
$result['activation_code'] = $alias['activation_code'];

header("Content-Type: application/json");
print(json_encode($result));

?>