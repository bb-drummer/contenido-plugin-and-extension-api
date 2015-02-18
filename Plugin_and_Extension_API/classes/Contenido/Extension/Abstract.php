<?php
if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}
cInclude('includes', 'Contenido/Object.php');

class Contenido_Extension_Abstract extends Contenido_Object {
	
	public function __construct( $options = array() ) {
		parent::__construct($options);
		return ($this);
	}
	
}