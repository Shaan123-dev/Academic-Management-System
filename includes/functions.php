<?php

function base_url($path = '') {
    return '/AMS/' . $path;
}

function redirect($path) {
    header("Location: " . base_url($path));
    exit;
}