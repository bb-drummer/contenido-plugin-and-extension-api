<?php
if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}
cInclude('includes', 'Contenido/Plugin/Abstract.php');

class Contenido_Plugin_Base extends Contenido_Plugin_Abstract {
	
	public function __construct($options = array()) {
		parent::__construct($options);
		return ($this);
	}
	
}