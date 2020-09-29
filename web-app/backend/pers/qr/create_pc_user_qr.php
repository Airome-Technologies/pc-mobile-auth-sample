<?php

/**
 *      Script to create a new PC User
 *      see https://repo.payconfirm.org/server/doc/v5/rest-api/#create-user
 */

include('../../config.php');

// Minimal create user params
$create_user_params = array(
    'id_prefix' => 'sample-',
    'return_key_method' => 'FULL_QR'
);

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
$result['user_qr'] = $user_info['key_QR'];

header("Content-Type: application/json");
print(json_encode($result));

?>