<?php

if (! defined('BASE_FILEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Shorthand function to link to internal url
 *
 * @param  string $internal_url internal url
 *
 * @return string      index independent internal url
 */
function href($internal_url)
{
    $Url = new Cuttlefish\Url($internal_url);

    // relative links for portability.
    return $Url->url_relative;
}

/**
 * List pages in templated format
 *
 * @return string html of list of pages
 */
function pages()
{
    $output     = '';
    $pages_path = sprintf("/%s/%s", Configuration::CONTENT_FOLDER, 'pages');

    $Files = new Cuttlefish\Files(array('url' => $pages_path), Configuration::CONTENT_EXT);
    foreach ($Files->files() as $path) {
        $filename = pathinfo($path, PATHINFO_FILENAME);
        $title    = ucwords(str_replace("-", " ", $filename));
        $output   .= sprintf("<li><a href='%s'>%s</a></li>", href("/pages/$filename"), $title);
    }

    return $output;
}

/**
 * Returns theme directory
 *
 * @return string link to theme directory
 */
function theme_dir()
{
    $theme_folder = Configuration::THEMES_FOLDER . DIRECTORY_SEPARATOR . Configuration::THEME . DIRECTORY_SEPARATOR;

    return BASE_PATH . str_replace("\\", "/", $theme_folder);
}

/**
 * Is the user logged in
 *
 * @return boolean logged in status
 */
function isLoggedIn()
{
    global $App;

    return $App->Security->isLoggedIn();
}
