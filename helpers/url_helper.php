<?php
function base_url($path = '') {
    $base = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]/project-allocation-system/";
    return $base . ltrim($path, '/');
}

function admin_url($path = '') {
    return base_url('admin/' . ltrim($path, '/'));
}

function supervisor_url($path = '') {
    return base_url('supervisor/' . ltrim($path, '/'));
}

function student_url($path = '') {
    return base_url('student/' . ltrim($path, '/'));
}