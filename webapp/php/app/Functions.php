<?php

use \Psr\Http\Message\ResponseInterface as Response;

function escape_html($h)
{
    return htmlspecialchars($h, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function redirect(Response $response, $location, $status)
{
    return $response->withStatus($status)->withHeader('Location', $location);
}

function image_url($post)
{
    $ext = '';
    if ($post['mime'] === 'image/jpeg') {
        $ext = '.jpg';
    } else if ($post['mime'] === 'image/png') {
        $ext = '.png';
    } else if ($post['mime'] === 'image/gif') {
        $ext = '.gif';
    }
    return "/image/{$post['id']}{$ext}";
}

function validate_user($account_name, $password)
{
    if (!(preg_match('/\A[0-9a-zA-Z_]{3,}\z/', $account_name) && preg_match('/\A[0-9a-zA-Z_]{6,}\z/', $password))) {
        return false;
    }
    return true;
}

function digest($src)
{
    return hash('sha512', $src);
}

function calculate_salt($account_name)
{
    return digest($account_name);
}

function calculate_passhash($account_name, $password)
{
    $salt = calculate_salt($account_name);
    return digest("{$password}:{$salt}");
}
