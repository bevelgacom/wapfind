<?php

function remove_unsupported_chars($str) {

    $str = preg_replace('/[[:^print:]]/', '', $str); // should be aA
    return $str;
}