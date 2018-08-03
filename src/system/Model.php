<?php

namespace VanDragt\Carbon;

use Michelf\Markdown;
use Spyc;

if ( ! defined( 'BASE_FILEPATH' ) ) {
	exit( 'No direct script access allowed' );
}

class Model {
	public $contents = array();
	public $model = array();

	public function __construct( $records ) {
		try {
			if ( array_unique( $this->model ) !== $this->model ) {
				throw new \Exception( 'Array values not unique for model' );
			}
		} catch ( \Exception $e ) {
			Log::error( $e->getMessage() );
		}
		$this->contents( $records );
	}

	function contents( $records ) {
		// implement $this->contents in your controller
	}

	function limit( $max ) {
		$this->contents = array_slice( $this->contents, 0, $max );

		return $this;
	}

	function list_contents( $record, $loaded_classes ) {
		$Content = new \StdClass();

		$Url  = new Url();
		$File = new File( $record );

		$content_sections = preg_split( '/\R\R\R/', trim( file_get_contents( $File->path ) ), count( $this->model ) );
		$section_keys     = array_keys( $this->model );
		$section_values   = array_values( $this->model );

		try {
			if ( count( $section_keys ) != count( $content_sections ) ) {
				throw new \Exception( 'Model (' . get_class( $this ) . ') definition (' . count( $section_keys ) . ') does not match number of content sections (' . count( $content_sections ) . ').' );
			}
		} catch ( \Exception $e ) {
			Log::error( $e->getMessage() );
			exit();
		}

		$Content->link = $Url->file_to_url( $File )->index()->make_absolute()->url;

		for ( $i = 0; $i < count( $this->model ); $i ++ ) {
			$content_section         = $content_sections[ $i ];
			$section_key             = $section_keys[ $i ];
			$section_value           = $section_values[ $i ];
			$Content->$section_value = $this->section( $content_section, $section_key, $loaded_classes );
		}

		return $Content;
	}

	public function section( $content_section, $section_key, $loaded_classes ) {
		// assign classes to their variables
		foreach ( $loaded_classes as $class_name => $obj ) {
			$$class_name = $obj;
		}

		$Section = new \StdClass();
		switch ( $section_key ) {
			case 'yaml':
				$yaml = Spyc::YAMLLoadString( $content_section );

				foreach ( $yaml as $key => $value ) {
					$Section->$key = $value;
				}
				break;
			case 'markdown|html':
				$md_sections    = preg_split( '/=\R/', trim( $content_section ), 2 );
				$title_sections = preg_split( '/\R/', trim( $md_sections[0] ), 2 );
				$Section->title = $title_sections[0];

				$Section->main = Markdown::defaultTransform( $md_sections[1] );

				break;

			default:
				break;
		}

		return $Section;
	}

	public function sortByPublished( $a, $b ) {
		return strcmp( $b, $a );
	}
}