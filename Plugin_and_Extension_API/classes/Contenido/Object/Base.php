<?php
if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}
cInclude('includes', 'Contenido/Object/Abstract.php');

class Contenido_Object_Base extends Contenido_Object_Abstract {
	
	public function __construct( $options = array() ) {
		parent::__construct($options);
		
		return ($this);
	}
	
}