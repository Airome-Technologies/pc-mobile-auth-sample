<?php

/**
 * Script to start authentication process with PC
 * 
 * This script
 *   - recieves PC User ID
 *   - creates a PC transaction (see https://repo.payconfirm.org/server/doc/v5/rest-api/#create-transaction)
 *   - remembers transaction ID for this user in temporary file to process it 
 *     after the transaction will be confirmed by a user with mobile phone
 * 
 * Notes:
 *   - Real back-end Application should use own user identifiers for a users
 *   - PC User ID should be stored with Application's User ID after Personalization process
 *   - to handle PC Transaction it's better to use database, sessions and all of this stuff
 *   - to store transaction IDs in temporary file - it's just for sample purposes
 * 
 * After this script called, Mobile Device with PC SDK and personalized with specified PC User ID
 * should confirm (digitally sign) created transaction
 * 
 * It will lead to callback from PC Server to pc_callback_reciever.php and changing status to
 * 'confirmed' or 'declined'
 * 
 * If status will be 'confirmed', then a user can be authorized (authentication successfull)
 * 
 * Authorization process (grant to a user rights to acceess) is handled
 * by finish_authentication.php script
 * 
 * 
 * Input JSON sample:
 *    {"pc_user_id":"sample-e7315c7d-7176-4431-a3f7-5343f2f86146"}
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

// Params to create PC Transaction, see https://repo.payconfirm.org/server/doc/v5/rest-api/#create-transaction
$create_transaction_params = array (
    'transaction_data' => array (
		// to authenticate a user we can use some random transaction content
        'text' => bin2hex(random_bytes(32))
    ),
    'callback_url' => $callback_receiver_url
);

// encode params to JSON
$data_string = json_encode($create_transaction_params);

// build request url
$create_transaction_url = $pc_url ."/" .$system_id ."/users/" .$user_id ."/transactions";

// make a request
if (!pc_request(
    $create_transaction_url,
    $data_string,
    'transaction_created',
    $transaction_info,
    $error_description,
    $error_code
)) {
    // if error - die
    header("HTTP/1.1 500 Internal request failed", true, 500);
    die("Call to PC failed. Error code: " .$error_code .", error description: " .$error_description);
}

// get transaction id
$transaction_id = $transaction_info['transaction_id'];

// store the transaction id in tmp-file (you should use database instead if this)
store_transaction_info($user_id, $transaction_id, 'created');

// Format and return the result
$result = array();
$result['transaction_id'] = $transaction_id;

header("Content-Type: application/json");
print(json_encode($result));

?>