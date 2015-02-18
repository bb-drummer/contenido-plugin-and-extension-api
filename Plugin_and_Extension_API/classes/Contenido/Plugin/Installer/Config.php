<?php
if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}

class Contenido_Plugin_Installer_Config {
	
	var $plugin_name	= '';
	var $menuless		= false;
	
	var $modules		= true;
	
	var $frames			= array(
		// 1	=> "{file}",
		// 2	=> "{file}",
		// 3	=> "{file}",
		// 4	=> "{file}",
	);
	
	var $templates		= array(
		// "{name}"	=> "{file}"
	);
	
	var $actions		= array(
		// "{name}"	=> "{file}"
	);
	
	var $sqlfiles		= array(
		"install"	=>	"sql/install.sql",
		"update"	=>	"sql/update.sql",
		"uninstall"	=>	"sql/uninstall.sql"
	);
	
	var $copy2client	= array(
		"js"		=> true,
		"css"		=> true,
		"images"	=> true,
		"includes"	=> true,
		"path"		=> ""	
	);
	
	public function __construct ($param) {
		$this->merge($param);
		return ($this);
	}
	
	static function factory ($param) {
		$config =  new self (params);
		return ($config);
	}
	
	private function merge ($param) {
		if ( $param instanceof Contenido_Plugin_Installer_Config ) {
			$param = $param->toArray();
		} else if (!is_array($param)) {
			throw new Exception("invalid installer configuration" , 1, null);
		}
		if ( !isset($param["name"]) || empty($param["name"]) || !is_string($param["name"]) ) {
			throw new Exception("no plugin name given" , 2, null);
		}
		$this->plugin_name = trim($param["name"]);
		
		if ( is_array($param["frames"]) ) {
			$this->frames		= array_merge_recursive($this->frames, $param["frames"]);
		}
		if ( is_array($param["templates"]) ) {
			$this->templates	= array_merge_recursive($this->templates, $param["templates"]);
		}
		if ( is_array($param["actions"]) ) {
			$this->actions		= array_merge_recursive($this->actions, $param["actions"]);
		}
		if ( is_array($param["sqlfiles"]) ) {
			$this->sqlfiles		= array_merge_recursive($this->sqlfiles, $param["sqlfiles"]);
		}
		if ( is_array($param["copy2client"]) ) {
			$this->copy2client	= array_merge_recursive($this->copy2client, $param["copy2client"]);
		}
		if ( is_bool($param["menuless"]) || is_numeric($param["menuless"]) ) {
			$this->menuless		= !!$param["menuless"];
		}
		if ( is_bool($param["modules"]) || is_numeric($param["modules"]) ) {
			$this->modules		= !!$param["modules"];
		}
	}
	
	public function toArray () {
		return (array)$this;
	}
}
