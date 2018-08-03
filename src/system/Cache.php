<?php

namespace VanDragt\Carbon;

if ( ! defined( 'BASE_FILEPATH' ) ) {
	exit( 'No direct script access allowed' );
}

class Cache {

	protected $cwd;

	public function __construct() {
		$this->cwd = getcwd(); // set current working directory
	}

	/**
	 * Write page to disk if cache is enabled
	 */
	public function end() {
		chdir( $this->cwd ); // Not a bug (LOL): https://bugs.php.net/bug.php?id=30210
		if ( $this->has_cacheable_page_request() ) {
			$this->write_cache_to_disk( null, ob_get_contents() );
			ob_end_flush();
		}
	}

	/**
	 * Returns possibility of caching the page based on environment and configuration
	 *
	 * @return boolean whether caching is possible
	 */
	function has_cacheable_page_request() {
		$cache_enabled              = \Configuration::CACHE_ENABLED;
		$is_caching                 = ! ob_get_level() == 0;
		$has_noerrors               = is_null( error_get_last() );
		$has_cacheable_page_request = ( $cache_enabled && $is_caching && $has_noerrors );

		return (boolean) $has_cacheable_page_request;
	}

	/**
	 * Writes the collected cache to disk
	 *
	 * @param  object $url_obj url object to be written
	 * @param  string $contents contents of the cache
	 *
	 * @return string           path to the cache file
	 */
	function write_cache_to_disk( $url_obj, $contents ) {
		$url     = ( is_object( $url_obj ) ) ? $url_obj->url : $url_obj;
		$path    = $this->cache_file_from_url( $url );
		$dirname = pathinfo( $path, PATHINFO_DIRNAME );

		if ( ! is_dir( $dirname ) ) {
			mkdir( $dirname, 0777, true );
		}
		$fp = fopen( $path, 'w' );
		fwrite( $fp, $contents );
		fclose( $fp );

		return $path;
	}

	/**
	 * Returns path to cache file based on url path
	 *
	 * @param  string $path_info path to current request
	 *
	 * @return string            path to cache file
	 */
	function cache_file_from_url( $path_info = '' ) {

		if ( isset( $_SERVER['PATH_INFO'] ) && empty( $path_info ) ) {
			$path_info = substr( $_SERVER['PATH_INFO'], 1 );
		}
		$path_info = ltrim( $path_info, '/' );
		$filename  = pathinfo( $path_info, PATHINFO_DIRNAME ) . '/' . pathinfo( $path_info, PATHINFO_FILENAME );
		$filename  = ltrim( $filename, '.' );

		$ext = pathinfo( $path_info, PATHINFO_EXTENSION );
		if ( strrpos( $path_info, '.' ) === false ) {
			$filename = rtrim( $filename, '/' ) . '/index';
			$ext      = 'html';

			if ( ! strrpos( $filename, 'feed' ) === false ) {
				$ext = 'xml';
			}
		}
		$cache_file = sprintf( "%s/%s.%s", \Configuration::CACHE_FOLDER, ltrim( $filename, '/' ), $ext );
		$cache_file = str_replace( '/', DIRECTORY_SEPARATOR, $cache_file );

		return (string) $cache_file;
	}

	/**
	 * Abort caching
	 */
	function abort() {
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}
	}

	/**
	 * Start caching
	 */
	function start() {
		ob_start();
	}

	/**
	 * Retiurns whether page is already cached
	 *
	 * @return boolean page has existing cachefile
	 */
	function has_existing_cachefile() {
		$cache_file          = $this->cache_file_from_url();
		$has_cache_file      = file_exists( $cache_file );
		$has_caching_enabled = \Configuration::CACHE_ENABLED;

		return ( $has_cache_file && $has_caching_enabled );
	}

	/**
	 * Generate a static version of the complete site
	 *
	 * @return string list of output messages detailing the generated files
	 */
	function generate_site() {
		$output = '';

		if ( \Configuration::INDEX_PAGE !== '' ) {
			die( 'Currently, generating a site requires enabling Pretty Urls (see readme.md for instructions).' );
		}
		$output .= $this->clear();

		$output  .= "<br>Generating site:<br>" . PHP_EOL;
		$content = \Configuration::CONTENT_FOLDER;
		$ext     = \Configuration::CONTENT_EXT;
		$Curl    = new Curl;
		$Files   = new Files( array( 'path' => Filesystem::url_to_path( "/$content" ), $ext ) );

		$cache_urls = array();

		foreach ( $Files->files() as $index => $file_path ) {
			$File         = new File( $file_path );
			$Url          = new Url();
			$cache_urls[] = $Url->file_to_url( $File )->index();
		}

		$urls = array(
			'/',
			'/feeds/posts',
			'/archive',
		);
		foreach ( $urls as $key => $value ) {
			$Url          = new Url();
			$cache_urls[] = $Url->index( $value );
		}

		foreach ( $cache_urls as $Url ) {
			$UrlCloned  = clone $Url;
			$url_string = $UrlCloned->make_absolute()->url;
			$contents   = $Curl->url_contents( $url_string );

			if ( empty( $contents ) ) {
				die( "ERROR: no contents for {$UrlCloned->abs()->url}" );
			}

			if ( \Configuration::CACHE_ENABLED == false ) {
				$path   = $this->write_cache_to_disk( $Url, $contents );
				$output .= "Written: $path<br>" . PHP_EOL;
			}
		}
		$Curl->close();

		$output .= $this->copy_themefiles( array( 'css', 'js', 'png', 'gif', 'jpg' ) );

		return (string) $output;
	}

	/**
	 * Completely clear the site cache
	 *
	 * @return string list of output messages detailing the removed cachefiles
	 */
	function clear() {
		global $App;
		$dir    = $this->cache_folder();
		$output = sprintf( "Removing  all files in %s<br>", $dir );
		$Files  = new Files( array( 'path' => $dir ) );
		$output .= $Files->remove_all();
		$dirs   = Filesystem::subdirs( realpath( $dir . '/.' ), false );
		foreach ( $dirs as $dir ) {
			Filesystem::remove_dirs( realpath( $dir . '/.' ) );
		}
		$App->Environment->server_setup();

		return (string) $output;
	}

	function cache_folder() {
		return realpath( BASE_FILEPATH . str_replace( "/", DIRECTORY_SEPARATOR, \Configuration::CACHE_FOLDER ) );
	}

	/**
	 * Copying of the theme files to the static site output folder
	 *
	 * @param  array $file_types list of filetypes to process
	 *
	 * @return string             messages detailing the process
	 */
	function copy_themefiles( $file_types ) {
		include( 'view_functions.php' );

		$theme_dir = rtrim( theme_dir(), '/' );
		$output    = "Copying files from theme: <br><br>";

		foreach ( $file_types as $file_type ) {
			$output .= "filetype: $file_type<br>";
			$Files  = new Files( array( 'path' => Filesystem::url_to_path( "$theme_dir" ) ), $file_type );

			$destination_files = array();
			foreach ( $Files->files() as $key => $value ) {
				$output              .= "$key: $value<br>";
				$cache               = ltrim( \Configuration::CACHE_FOLDER, "./" );
				$destination_files[] = str_replace( 'public', $cache, $value );
			}
			Filesystem::copy_files( $Files->files(), $destination_files );
		}

		return (string) $output;
	}


}
