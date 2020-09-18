<?php

/**
 * Script to Authorize a user (grant to a user rights to acceess)
 * 
 * This is just a sample script
 * 
 * It returns true in case of authentication successfull, false in other case
 * See description in start_authentication.php file
 * 
 * Your real authorization script should issue something like access-token
 * or set specialized cookie
 * 
 * Input JSON sample:
 *    {"pc_user_id":"sample-e7315c7d-7176-4431-a3f7-5343f2f86146"}
 * 
 */

include('config.php');

// read input JSON
$php_input = file_get_contents('php://input');
$request = (array) json_decode($php_input, true);

// check if pc_user_id is specified in the request
if (!isset($request['pc_user_id'])) {
    header("HTTP/1.0 400 Bad Request", true, 400);
    die();
}

// Get PC User ID
$user_id = $request['pc_user_id'];

// Set result, false by default
$result = array (
    'authentication_successfull' => false
);

if ('confirmed' == get_stored_transaction_info($user_id, null)) {
    $result['authentication_successfull'] = true;
}

header("Content-Type: application/json");
print(json_encode($result));

?>