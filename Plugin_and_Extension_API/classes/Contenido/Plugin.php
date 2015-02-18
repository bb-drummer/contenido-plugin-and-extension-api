<?php
if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}
cInclude('includes', 'Contenido/Plugin/Base.php');

class Contenido_Plugin extends Contenido_Plugin_Base {
	
	function __construct( $options = array() ) {
		parent::__construct( $options );
		return ($this);
	}
	
}