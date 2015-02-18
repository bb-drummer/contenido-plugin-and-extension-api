<?php
if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}
global  $tpl, $perm, $area, $action, $cfg, $cfgClient, $sess, $file, $filename, $client, $lang, $frame;

class Contenido_Object_Abstract {
	
	/**
	 * object options container
	 * @var	mixed	$_options
	 */
	private $_options = array();
	
	/**
	 * Contenido global parameters container
	 * @var	mixed	$_conVars
	 */
	private $_conVars = array();
	
	/**
	 * Contenido global parameters container
	 * @var	array	$_conGlobals
	 */
	private $_conGlobals = array( 
		// Contenido basic global parameters
		"cfg", "cfgClient", "sess", "client", "lang", "cronjob", "auth", "user", "perm", "db", "tpl", 
		// Contenido plugin global parameters	
		"file", "filename", "frame", "area", "actionarea", "action", "select",
		// Contenido article and category global parameters							
		"idcat", "idcatart", "idartlang", "idart", "idtpl", "idmod", "idlay", 
		// Contenido module global parameters
		"cnumber", "cCurrentModule", "lang", "mi18nTranslator",
		// 'CMS_' vars, values, types and language mapping parameters						
		"VARS", "VALUES", "MI18N", "ELEMENTS", 
		// basic extensional and other global parameters
		"debug", "plugin_name"
	);
	
	/**
	 * singleton instance container
	 * @var Contenido_Object_Abstract
	 */
	private static $_instance = null;
	
	/**
	 * Contenido configuration container
	 * @var ARRAY
	 */
	private $_config = array();
	
	/**
	 * Contenido templates container
	 * @var mixed
	 */
	public $_tpls = array( /*
		'tplName'	=> (object)array(
			'name'		=> 'tplName',
			'template'	=> new Template(),
			'content'	=> '',
			'static'	=> array(
				'TPL_VAR1'	=> 'tpl_value1',
				'TPL_VAR2'	=> 'tpl_value2',
				...
			),
			'dynamic'	=> array(
				array(
					'TPL_VAR1'	=> 'tpl_value1',
					'TPL_VAR2'	=> 'tpl_value2',
				),
				array(
					'TPL_VAR1'	=> 'tpl_value1',
					'TPL_VAR2'	=> 'tpl_value2',
				)
			),
		), */
	);
	
	/**
	 * database connection instance container
	 * @var DB_Contenido
	 */
	private $_db = null;
	
	/**
	 * contructor
	 * 
	 * @param	mixed	$options
	 * @return	Contenido_Object_Abstract
	 */
	public function __construct( $options = array() ) {
		$this->__init($options);
		return ($this);
	}
	
	/**
	 * get singelton instance object
	 * 
	 * @param	array|stdClass	$config
	 * @return	Contenido_Object_Abstract
	 */
	public static function getInstance ( $options = array() ) {
		if ( self::$_instance === NULL ) {
			self::$_instance = new self( $options );
		}
		return self::$_instance;
	}

	/**
	 * init (Contenido) object global parameters
	 * 
	 * @param	mixed	$params
	 * @return	Contenido_Object_Abstract
	 */
	protected function __init ($params = null) {
		
		// basic object setup
		$oGlobals = (object)$this->getConVars( true );
		$this->setConfig( $oGlobals );
		$this->_config->tmplPath	=	getEffectiveSetting('global', 'templatepath', 'templates/');
		$this->_config->debug		=	(getEffectiveSetting('global', 'debug', 'false') == 'false') ? false : true;
		if ($this->_config->db instanceof DB_Contenido) {
			$this->_db =& $this->_config->db;
		}
		$this->setOptions($params);
		if (method_exists($this, "__initOptions")) {
			$this->__initOptions( $params );
		}
		return ($this);
	}

	/**
	 * init (Contenido) object option parameters
	 * 
	 * @param	mixed	$params
	 * @return	Contenido_Object_Abstract
	 */
	private function __initOptions ($params = null) {
		return ($this);
	}
	
	/**
	 * get translated string converting special characters to html entities
	 * 
	 * @link http://www.php.net/manual/en/function.htmlentities.php
	 * @param	string	$text
	 * @param	string	$domain
	 * @param	integer	$quote_style [optional] <p>
	 * Like htmlspecialchars, the optional second
	 * quote_style parameter lets you define what will
	 * be done with 'single' and "double" quotes. It takes on one of three
	 * constants with the default being ENT_COMPAT:
	 * <table>
	 * Available quote_style constants
	 * <tr valign="top">
	 * <td>Constant Name</td>
	 * <td>Description</td>
	 * </tr>
	 * <tr valign="top">
	 * <td>ENT_COMPAT</td>
	 * <td>Will convert double-quotes and leave single-quotes alone.</td>
	 * </tr>
	 * <tr valign="top">
	 * <td>ENT_QUOTES</td>
	 * <td>Will convert both double and single quotes.</td>
	 * </tr>
	 * <tr valign="top">
	 * <td>ENT_NOQUOTES</td>
	 * <td>Will leave both double and single quotes unconverted.</td>
	 * </tr>
	 * </table>
	 * </p>
	 * @param	string	$charset	[optional] <p>
	 * Like htmlspecialchars, it takes an optional
	 * third argument charset which defines character
	 * set used in conversion.
	 * Presently, the ISO-8859-1 character set is used as the default.
	 * </p>
	 * &reference.strings.charsets;
	 * @param	boolean	$double_encode	[optional] <p>
	 * When double_encode is turned off PHP will not
	 * encode existing html entities. The default is to convert everything.
	 * </p>
	 * @param	integer	$mode	[optional] <p>0 = using htmlentities() (default), 1 = using htmlspecialchars()</p>
	 * @return	string
	 */
	public function _html ( $text, $domain = NULL, $quote_style = null, $charset = null, $double_encode = null, $mode = 0 ) {
		return ($mode == 1) ? htmlspecialchars( i18n($text, $domain), $quote_style, $charsetl, $double_encode ) : htmlentities( i18n($text, $domain), $quote_style, $charsetl, $double_encode );
	}
	
	/**
	 * get translated string
	 * 
	 * @param	string	$text
	 * @param	string	$domain
	 * @return	string
	 */
	public function _text ( $text, $domain = NULL ) {
		$_mi18n = $this->_con("MI18N");
		$_string = $text;
		if ( is_array($_mi18n) && isset($_mi18n[$text]) ) {
			$_string = (string)$_mi18n[$text];
		} else 
		if ( is_array($_mi18n) && is_array($_mi18n[$domain]) && isset($_mi18n[$domain][$text]) ) {
			$_string = (string)$_mi18n[$domain][$text];
		} else
		if ( ($this->_con("cnumber") !== NULL) && ( $this->_con("mi18nTranslator") !== NULL ) ) {
			$_string =  mi18n($text, $domain);
		} else {
			$_string = i18n($text, $domain);
		}
		return $_string;
	}
	
	/**
	 * get Contenido effective CMS setting(s)
	 * 
	 * @param	string	$type
	 * @param	string	$name
	 * @param	string	$default
	 * @return	string|array
	 */
	public function _setting ( $type, $name = null, $default = "" ) {
		if ( empty($name) ) {
			return getEffectiveSettingsByType($type);
		}
		return getEffectiveSetting($type, $name, $default);
	}

	/**
	 * return/set a given contenido global parameter
	 * -> it is NOT recommended to set/override a global contenido parameter!
	 * 
	 * @param	string	$varname
	 * @param	mixed	$varvalue
	 * @return	mixed
	 */
	public function _con ( $varname, $varvalue = NULL ) {
		$aConVars = $this->getConVars();
		if ( $varvalue === NULL ) {
			return ((isset($aConVars[$varname])) ? $aConVars[$varname] : NULL);
		} else {
			$this->__setConVar($varname, $varvalue);
			return $this->_con($varname);
		}
	}

	/**
	 * return configuration settings array
	 * 
	 * @return	stdClass|array
	 */
	public function getConfig () {
		return (object)$this->_config;
	}
	
	/**
	 * set configuration settings array
	 * 
	 * @param	array|stdClass	$config
	 * @return	Contenido_Object_Abstract
	 */
	private function setConfig ( $config ) {
		$this->_config = (object)$config ;
		return ($this);
	}
	
	/**
	 * generate and get template objects
	 * 
	 * @param	string			$tplName
	 * @param	boolean			$reset
	 * @return	Template
	 */
	public function getTpl ( $tplName = NULL, $reset = FALSE ) {
		if ( empty($tplName) ) {
			$tplName = 'default';
		}
		if ( ( !isset($this->_tpls[$tplName]) || !isset($this->_tpls[$tplName]->template) || !($this->_tpls[$tplName]->template instanceof Template) ) || $reset) {
			$oTemplate = new Template();
			$oTemplate->reset();
			$this->_tpls[$tplName] = (object)array(
				"name"		=>	$tplName,
				"template"	=>	$oTemplate,
				"content"	=>	'',
				"static"	=>	array(),
				"dynamic"	=>	array(),
			);
		}
		if ($reset) {
			$this->_tpls[$tplName]->template->reset();
		}
		
		return $this->_tpls[$tplName];
	}
	
	/**
	 * generate template and set template variables (static and dynamic) array
	 * 
	 * @param	string			$tplName
	 * @param	array|stdClass	$config
	 * @param	boolean			$reset
	 * @param	boolean			$default
	 * @return	Contenido_Object_Abstract
	 */
	private function setTpl ( $tplName, $config, $reset = FALSE, $default = FALSE ) {
		if ( empty($tplName) ) {
			$tplName = 'default';
		}
		$oTemplate = $this->getTpl( $tplName, $reset );
		if ( is_array($config->static) || is_array($config->dynamic) ) {
			$this->setTplVars($tplName, $config);
		}
		if ( $default && ($tplName != "default") ) {
			$this->_tpls["default"] = & $this->_tpls[$tplName];
		}
		return ($this);
	}
	
	/**
	 * set template variables (static and dynamic) array
	 * 
	 * @param	string			$tplName
	 * @param	array|stdClass	$config
	 * @param	boolean			$reset
	 * @return	Template
	 */
	public function setTplVars ( $tplName, $config, $reset = FALSE ) {
		if ( empty($tplName) ) {
			$tplName = 'default';
		}
		if ( is_array($config) ) { $config = (object)$config; }
		$oTemplate = $this->getTpl( $tplName, $reset );
		if ( is_array($config->static) || is_object($config->static) ) {
			foreach ((array)$config->static as $needle => $replacement) {
				$oTemplate->template->set("s", $needle, $replacement);
			}
		}
		if ( is_array($config->dynamic) || is_object($config->dynamic) ) {
			foreach ((array)$config->dynamic as $idx => $vars) {
				$oTemplate->template->set("d", "BLOCKINDEX", $replacement);
				foreach ((array)$vars as $needle => $replacement) {
					$oTemplate->template->set("d", $needle, $replacement);
				}
				$oTemplate->template->next();
			}
		}
		
		return ($oTemplate->template);
	}
	
	/**
	 * get (current) instance of DB_Contenido
	 * @param	DB_Contenido	$db
	 * @return	DB_Contenido
	 */
	public function getDb ( $db = null ) {
		if ( $this->_db instanceof DB_Contenido ) {
			return ($this->_db);
		}
		if ($db instanceof DB_Contenido) {
			$this->_db = $db;
		} else {
			$this->_db = new DB_Contenido;
		}
		return ($this->_db);
	}
	
	/**
	 * get object options array
	 * 
	 * @return	array|stdClass
	 */
	public function getOptions() {
		return (array)$this->_options;
	}

	/**
	 * set object options array
	 * 
	 * @param	array|stdClass	$options
	 * @return	Contenido_Object_Abstract
	 */
	public function setOptions($options) {
		$aOptions = $this->getOptions();
		$this->_options = array_merge_recursive($aOptions, (array)$options);
		return ($this);
	}

	/**
	 * get all '$_REQUEST' parameters
	 * 
	 * @return	array|stdClass
	 */
	public function _getAllParams( $asObject = false ) {
		return ($asObject) ? (object)$_REQUEST : (array)$_REQUEST;
	}

	/**
	 * load global Contenido parameters
	 * @return the $conVars
	 */
	private function getConVars( $forceReset = false ) {
		foreach ($this->_conGlobals as $key => $varname) {
			if ( isset($GLOBALS[$varname]) ) {
				if ( $forceReset || !isset($this->_conVars[$varname]) ) {
					$this->_conVars[$varname] = & $GLOBALS[$varname];
				}
			}
		}
		return ((array)$this->_conVars);
	}

	/**
	 * @param field_type $conVars
	 * @return	Contenido_Object_Abstract
	 */
	private function __setConVar( $varname, $varvalue = NULL, $setGlobal = false ) {
		if ( in_array($varname, $this->_conGlobals) && array_key_exists($varname, $GLOBALS) && ( $varvalue !== NULL ) ) {
			if ($setGlobal) {
				$GLOBALS[$varname] = $varvalue;
				$this->_conVars[$varname] = & $GLOBALS[$varname];
			} else {
				$this->_conVars[$varname] = $varvalue;
			}
		} else if ( in_array($varname, $this->_conGlobals) ) {
			$this->_conVars[$varname] = $varvalue;
		}
		if ( isset($this->_conVars[$varname]) && ($this->_conVars[$varname] === NULL) ) {
			unset ($this->_conVars[$varname]);
		}
		if ( ($setGlobal) && isset($GLOBALS[$varname]) && ($GLOBALS[$varname] === NULL) ) {
			unset ($GLOBALS[$varname]);
		}
		return ($this);
	}
	
	/**
	 * return Contenido configuration
	 * @return stdClass|array
	 */
	public function getCfg () {
		return (object)$this->getConfig()->cfg;
	}
	
	/**
	 * return Contenido client configuration
	 * @return stdClass|array
	 */
	public function getCfgClient () {
		return (object)$this->getConfig()->cfgClient;
	}
	
	/**
	 * return Contenido authorization object
	 * @return stdClass|array
	 */
	public function getAuth () {
		return (object)$this->getConfig()->auth->auth;
	}
	
	/**
	 * return Contenido authorized user id
	 * @return string
	 */
	public function getAuthId () {
		return $this->getAuth()->uid;
	}
	
	/**
	 * determine if current Contenido user is not an authorized user
	 * @return boolean
	 */
	public function isAuthNobody () {
		return $this->getAuthId() == "nobody";
	}
	
	/**
	 * determine if debug mode is set to true
	 * @return boolean
	 */
	public function isDebug () {
		return $this->getConfig()->debug === true;
	}
	
	
}