<?php
// wordpress load
if (php_sapi_name() !== 'cli') {
    die('Meant to be run from command line');
}

function find_wordpress_base_path()
{
    $dir = dirname(__FILE__);
    do {
        //it is possible to check for other files here
        if (file_exists($dir . '/wp-config.php')) {
            return $dir;
        }
    } while ($dir = realpath("$dir/.."));
    return null;
}

define('BASE_PATH', find_wordpress_base_path() . '/');
define('WP_USE_THEMES', false);
global $wp, $wp_query, $wp_the_query, $wp_rewrite, $wp_did_header;
require BASE_PATH . 'wp-load.php';
// wordpress load

define('OPENPIX_ENV', 'development');

function getOpenPixApiUrl()
{
    if (OPENPIX_ENV === 'development') {
        return 'http://localhost:5001';
    }

    if (OPENPIX_ENV === 'staging') {
        return 'https://api.openpix.dev';
    }

    // production
    return 'https://api.openpix.com.br';
}

$correlationID = '8d6df0db26ed49f9a6953c89aaabdea4';

$url = getOpenPixApiUrl() . '/api/v1/charge/' . $correlationID;

echo '\n\n';
echo $url;

$response = wp_safe_remote_get($url);
$data = json_decode($response);

echo $data;
