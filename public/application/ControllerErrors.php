<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

class ControllerErrors extends Controller {

	// single page

	function records() {
		$this->Records = new StdClass();
		$this->Records->collection = array(
 			Filesystem::url_to_path('/content/errors/' . implode($this->args,"/") . '.' . $this->ext),
 		);
	}

	function model() {
		$this->Model = new ModelPage( $this->Records->collection, $this->_parent->Environment);
	}

	function view() {
		parent::view();

		$this->View = new Html( $this->Model->contents, array(
			'layout'     => 'single.php',
			'controller' => 'errors',
			'model'      => 'page',
		) ) ;
	}		
}
