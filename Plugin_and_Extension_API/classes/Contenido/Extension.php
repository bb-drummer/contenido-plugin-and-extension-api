<?php
if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}
cInclude('includes', 'Contenido/Extension/Abstract.php');

class Contenido_Extension extends Contenido_Extension_Abstract {
	
	public function __construct( $options = array() ) {
		parent::__construct($options);
		return ($this);
	}
	
}