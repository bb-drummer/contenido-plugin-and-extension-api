<?php
if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}
cInclude('includes', 'Contenido/Extension.php');

class Contenido_Plugin_Abstract extends Contenido_Extension {
	
	public function __construct( $options = array() ) {
		parent::__construct( $options );
		return ($this);
	}
	
}