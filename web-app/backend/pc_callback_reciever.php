<?php

/**
 * Script to recieve callback from PC Server with information about transactions
 *
 * This script handles transaction status changing after user actions: confirm or decline
 * See https://repo.payconfirm.org/server/doc/v5/rest-api/#transactions-endpoint
 * 
 * If status will be 'confirmed', then a user can be authorized (authentication successfull)
 * 
 * Authorization process (grant to a user rights to acceess) is handled
 * by finish_authentication.php script
 * 
 */

include('config.php');

// read input JSON
$php_input = file_get_contents('php://input');
$callback_data = (array) json_decode($php_input, true);

// check if we can not parse the callback
if (!isset($callback_data['pc_callback']['type']) || !isset($callback_data['pc_callback']['version'])) {
    header("HTTP/1.0 400 Bad Request", true, 400);
    die();
}

// if there is not our callback
if ( ($callback_data['pc_callback']['type'] != 'transaction_callback') || ($callback_data['pc_callback']['version'] != 3) ) {
    header("HTTP/1.0 400 Bad Request", true, 400);
    die();
}

$transaction_result = $callback_data['pc_callback']['result'];
$transaction_callback = $callback_data['pc_callback']['transaction_callback'];

// check if there was a error
if ($transaction_result['error_code'] != 0) {
    // do nothing
    die();
}

// get new status
$status = 'declined';  // Declined by default
if (isset($transaction_callback['confirmation'])) {
    $status = 'confirmed';   // change to declined if there was declanation
}

// store the status
store_transaction_info(null, $transaction_callback['transaction_id'], $status);

?>