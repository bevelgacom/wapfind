<?php
$redis = new Redis();
$redis->connect("127.0.0.1",6379);

function generate_key() {
    $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    return substr(str_shuffle($permitted_chars), 0, 8);
}

function get_url_for_key($key) {
    global $redis;
    $url = $redis->get($key);
    if ($url) {
        return $url;
    } else {
        return false;
    }
}

function save_url($url) {
    global $redis;

    // check if it already was saved
    $key = $redis->get($url);
    if ($key) {
        return $key;
    }
    
    // generate a random key
    $key = generate_key();
    while ($redis->get($key)) {
        $key = generate_key();
    }

    $redis->set($key, $url);
    $redis->set($url, $key);
    return $key;
}

