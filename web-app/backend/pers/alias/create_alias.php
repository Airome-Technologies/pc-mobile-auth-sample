<?php

/**
 * Script to create a new "Alias" to get PC User in JSON format
 * 
 * Personalization by Alias consists of following steps:
 *  - create alias and store them in tmp-file (in real application you should use database)
 *    done by this script
 *  - privide the Alias value to a user
 *  - a user types this alias into the mobile app
 *  - mobile app makes a request to this sample to script /pers/alias/get_pc_user.php
 *    and provides the Alias
 *  - get_pc_user.php calls PC Server to create a PC User and export them in JSON-format
 *    to encrypt a keys this script uses "activation_code" value
 *    see https://repo.payconfirm.org/server/doc/v5/rest-api/#json-export-key
 *  - mobile app imports a PC Users and asks from a user activation_code value
 *  - done
 */

include('../../config.php');

// create random Alias
$alias_value = random_str(8, "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ");

// create activation_code for this Alias
// 
// !!! WARNING - we create Activation Code here for DEMO PURPOSES ONLY
//     You should create Activation Code at the moment when you requests key JSON from PC Server
//     After you have created Activation Code you should send it to a user with another channel
//     It can be email, SMS, push or something else
//
//     We can not send SMS or something here in demo, that's why we create activation code here

$activation_code = random_str(10, "0123456789abcdefghijklmnopqrstuvwxyz");

// store Alias and activation code to tmp-file
//   In real Application you should
//     - use database
//     - DO NOT STORE ACTIVATION CODE, see comment above
$alias = array($alias_value => $activation_code);
store_alias($alias);

// Format and return the result
$result = array();
$result['alias'] = $alias_value;

// !!! WARNING - we create Activation Code here for DEMO PURPOSES ONLY
//     You should create Activation Code at the moment when you requests key JSON from PC Server
//     After you have created Activation Code you should send it to a user with another channel
//     It can be email, SMS, push or something else
//
//     We can not send SMS or something here in demo, that's why we create activation code here
$result['activation_code'] = $activation_code;

header("Content-Type: application/json");
print(json_encode($result));

?>