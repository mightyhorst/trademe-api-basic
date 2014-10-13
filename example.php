<?php
/*
Copyright 2014 Robert Monnig - robertm@monnigdesign.co.nz

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
*/

 // Initializing
ini_set('display_errors', 1);
session_start();

//Get our Trade Me API class
require_once("trademeApiRequest.php");

// Config

// Database Config
$db_user = '[DB USER]';
$db_password = '[DB PASSWORD]';
$db_name = '[DB NAME]';

// OAuth config
$oauth_consumer_key = '[CONSUMER_KEY]';
$oauth_consumer_secret = '[CONSUMER_SECRET]';

// API config
$url_part = 'tmsandbox'; // tmsandbox = sandbox, trademe = live
$callback_url = 'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] . $_SERVER['PHP_SELF'];
$permissions = array( // MyTradeMeRead, MyTradeMeWrite, BiddingAndBuying
  'MyTradeMeRead',
  'MyTradeMeWrite'
);
$photos_dir = $_SERVER['DOCUMENT_ROOT'] . '/trademe_photos/';

// Init DB
$GLOBALS['mysqli'] = new mysqli('localhost', $db_user, $db_password, $db_name);

$oauth_settings = array(
  'oauth_version' => '1.0',
  'oauth_signature_method' => 'HMAC-SHA1',
  'oauth_consumer_key' => $oauth_consumer_key,
  'oauth_consumer_secret' => $oauth_consumer_secret
);

// Get our long-life oauth tokens out from storage
$sql = 'SELECT oauth_token, oauth_token_secret FROM trademe_tokens';
$result = $GLOBALS['mysqli']->query($sql);

if (!$result) {
  die('Database error.');
}

// If we don't have any  long-life tokens, get some!
if ($result->num_rows == 0) {
  $tm = new trademeApiRequest($url_part, $callback_url, $oauth_settings);
  $res = $tm->get_access_tokens($permissions);

  if (!isset($res[0]['oauth_token']) || !isset($res[0]['oauth_token_secret'])) {
    echo $tm->build_response_error($res[0]);
    exit();
  }

  $tokens = $res[0];

  $sql = "INSERT INTO trademe_tokens (oauth_token, oauth_token_secret) VALUES ('" . $tokens['oauth_token'] . "', '" . $tokens['oauth_token_secret'] . "')";
  $GLOBALS['mysqli']->query($sql);
} else {
  $tokens = $result->fetch_assoc();
}

// Add our long life tokens to our oauth settings
$oauth_settings = array_merge($oauth_settings, $tokens);

// List a product
// Upload product photos
$product_photos = array(
  $photos_dir . 'photo1.jpg',
  $photos_dir . 'photo2.jpg',
  $photos_dir . 'photo3.jpg'
);

$tm = new trademeApiRequest($url_part, $callback_url, $oauth_settings);
$photo_ids = $tm->upload_photos($product_photos);

// Create listing
$test_product = array(
  'product_category_id' => '0187-0442-2109-4295-', // Get category from Trade Me
  'product_title' => "Test Product",
  'product_price' => 5.00,
  'product_description' => "Lorem ipsum dolor sit amet, consectetur adipiscing elit.\r\n\r\nQuisque rhoncus quam sit amet tincidunt imperdiet.\r\n\r\nEtiam vitae bibendum velit, non venenatis est",
  'photo_ids' => $photo_ids
);

$tm = new trademeApiRequest; // The parameters haven't changed so we don't have to enter them again
$res = $tm->list_product($test_product);

if (isset($tm->last_error)) {
  echo $tm->build_response_error($res[0]);
  exit();
}

echo $res[0]['Description'];

// Optionally store resulting listing id for future API calls
//$listing_id = $res[0]['ListingId'];
?>