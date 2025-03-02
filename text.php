<?php

function remove_unsupported_cars($str) {

    $str = preg_replace('/[[:^print:]]/', '', $str); // should be aA
    return $str;
}