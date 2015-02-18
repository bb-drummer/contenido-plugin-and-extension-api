<?php
if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}
cInclude('includes', 'Contenido/Object/Base.php');

class Contenido_Object extends Contenido_Object_Base {
	
	function __construct($options = array()) {
		parent::__construct($options);
		return ($this);
	}
	
}