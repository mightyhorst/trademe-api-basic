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

class oauthRequest {

  protected static $oauth_settings = array(
    'oauth_version' => '1.0',
    'signature_method' => 'HMAC-SHA1',
    'oauth_consumer_key' => null,
    'oauth_consumer_secret' => null,
    'oauth_token' => null,
    'oauth_token_secret' => null
  );
  // oauth fields for request
  protected static $oauth_fields = array();
  protected $exclude_fields = array();

  public function __construct($oauth_settings = null) {
    if (isset($oauth_settings)) {
      self::$oauth_settings = array_merge(self::$oauth_settings, $oauth_settings);

      $tmp = array(
        'oauth_version',
        'oauth_consumer_key',
        'oauth_signature_method',
        'oauth_token'
      );

      foreach ($tmp as $val) {
        if (isset(self::$oauth_settings[$val])) {
          self::$oauth_fields[$val] = self::$oauth_settings[$val];
        }
      }
    }
  }

  protected function build_request($url, $method, $oauth_fields = array(), $exclude_fields = array()) {
    self::$oauth_fields = array_merge(self::$oauth_fields, $oauth_fields);

    self::$oauth_fields['oauth_timestamp'] = time();
    self::$oauth_fields['oauth_nonce'] = uniqid();

    self::$oauth_fields['oauth_signature'] = self::build_signature($url, $method);

    $this->exclude_fields = $exclude_fields;
  }

  protected function build_signature($url, $method) {
    if (self::$oauth_settings['oauth_signature_method'] == 'HMAC-SHA1') {
      $signature_base = self::build_signature_base($url, $method);
      
      // debug
      //var_dump($signature_base);

      $signature = base64_encode(
        hash_hmac(
          'sha1',
          $signature_base,
          self::$oauth_settings['oauth_consumer_secret'] . '&' . self::$oauth_settings['oauth_token_secret'],
          true
        )
      );
    } else {
      $signature = self::$oauth_settings['oauth_consumer_secret'] . '&' . self::$oauth_settings['oauth_token_secret'];
    }

    return $signature;
  }

  protected function build_signature_base($url, $method) {
    if (isset(self::$oauth_fields['oauth_signature'])) {
      unset(self::$oauth_fields['oauth_signature']);
    }
    
    $str = $method .'&' . rawurlencode($url) . '&';

    ksort(self::$oauth_fields);

    $tmp = '';
    foreach (self::$oauth_fields as $key => $val) {
      $prefix = ($tmp == '')? '' : '&';
      $tmp .= $prefix . $key . '=' . rawurlencode($val);
    }

    $str .= rawurlencode($tmp);

    return $str;
  }

  protected function do_request($url, $method, $headers = array(), $post_fields = '') {
    $headers[] = self::build_header();

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    if ($method == 'POST') {
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    }

    $res = curl_exec($ch);

    return array($res, curl_getinfo($ch));
  }

  protected function build_header() {
    $str = '';
    foreach (self::$oauth_fields as $key => $val) {
      if (in_array($key, $this->exclude_fields)) {
        continue;
      }
      $str .= ($str == '')? '' : ', ';
      $str .= $key . '="' . rawurlencode($val) . '"';
    }

    $str = 'Authorization: OAuth ' . $str;

    // debug
    //var_dump($str);

    return $str;
  }
}
?>