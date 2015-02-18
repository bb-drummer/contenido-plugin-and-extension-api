<?php
if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}
cInclude('includes', 'Contenido/Plugin/Base.php');

/**
 * Contenido Plugin GUI
 * 
 * @author	Björn Bartels, P.AD. Werbeagentur GmbH <bba@p-ad.de>
 * @package	Contenido Plugin GUI
 */
class Contenido_Plugin_GUI extends Contenido_Plugin_Base {
	
	/**
	 * plugin frame list container
	 * @var ARRAY
	 */
	private $_frames = array(
		"left_top",
		"left_bottom",
		"right_top",
		"right_bottom"
	);
	
	/**
	 * singleton instance container
	 * @var Contenido_Plugin_GUI
	 */
	private static $_instance = null;
	
	/**
	 * contructor
	 * 
	 * @param	mixed	$options
	 * @return	Contenido_Plugin_GUI
	 */
	public function __construct( $options = array() ) {
		parent::__construct( $options );
		return ($this);
	}
	
	/**
	 * get singelton instance object
	 * 
	 * @param	array|stdClass	$config
	 * @return	Contenido_Plugin_GUI
	 */
	public static function getInstance ( $options = array() ) {
		if ( self::$_instance === NULL ) {
			self::$_instance = new self( $options );
		}
		return self::$_instance;
	}

	/**
	 * get frame specific template object
	 * 
	 * @param	string	$tplName
	 * @param	booloean	$reset
	 * @return	stdClass|NULL
	 */
	public function getFrame ( $tplName, $reset = FALSE ) {
		if ( in_array($tplName, $this->_frames) ) {
			return $this->getFrameTemplate( 'frame_'.$tplName, $reset );	
		}
		return NULL;
	}
	
	/**
	 * create plugin specific template object
	 * 
	 * @param	string	$tplName
	 * @param	booloean	$reset
	 * @return	stdClass
	 */
	public function getFrameTemplate ( $tplName, $reset = FALSE ) {
		$oTemplate	= $this->getTpl($tplName, $reset);
		if ( !empty( $this->getConfig()->cfg['templates'][$this->_con('plugin_name').'_'.$tplName] ) ) {
			$oTemplate->content = $this->getConfig()->cfg['templates'][$this->_con('plugin_name').'_'.$tplName];
		}
		return $oTemplate;
	}
	
	/**
	 * generate page opening HTML depending on configuarion in plugin's config file
	 * 
	 * @param	array	$param
	 * @param	string	HTML
	 */
	public function page_open ( $param ) {
		$plugin_name	= $this->_con("plugin_name");
		$sTplName		= "plugin_page";
		$oTemplate		= $this->getTpl($sTplName, TRUE);
		
		//$sPagePrintLink	= $this->getCfg()->templates[$plugin_name."_pageprintlink"];
		$config = (object)array("static"	=> array(), "dynamic"	=> array());
		
		if ( !isset($param["page_title"]) || empty($param["page_title"]) ) {
			$config->static["PLUGIN_PAGE_TITLE"]	= "";
		} else	{
			$config->static["PLUGIN_PAGE_TITLE"]	= $param["page_title"];
		}
		if (	!isset($param["page_header_html"]) || empty($param["page_header_html"])	) {
			$config->static["HEADER_HTML"]	= "";
		} else	{
			$config->static["HEADER_HTML"]	= $param["page_header_html"];
		}
		if (	!isset($param["body_onload"]) || empty($param["body_onload"])	) {
			$config->static["BODY_ONLOAD"]	= "";
		} else	{
			$config->static["BODY_ONLOAD"]	= $param["body_onload"];
		}
		//echo "<pre>".print_r($param["js"], TRUE)."</pre>";
		//echo "<pre>".print_r($param["css"], TRUE)."</pre>";
		$js = "";
		foreach ((array)$param["js"] as $key => $jsScriptFile) {
			$checkFile = substr( $jsScriptFile, 0, (strpos($jsScriptFile, "?") !== false) ? strpos($jsScriptFile, "?") : strlen($jsScriptFile));
			//echo "<pre>".print_r($this->getCfg()->plugins[$plugin_name].$jsScriptFile, TRUE)."</pre>";
			if ( is_readable($this->getCfg()->path['contenido'].$checkFile) ) {
				// is it a contenido JS file?
				$js .= sprintf(
					'<script id="%s" type="text/javascript" src="%s"></script>', 
					$key, 
					$jsScriptFile
				);
			} else if ( is_readable($this->getCfg()->plugins[$plugin_name].$checkFile) ) {
				// is it a plugin JS file?
				$js .= sprintf(
					'<script id="%s" type="text/javascript" src="%s"></script>', 
					$key, 
					$this->getCfg()->plugins[$plugin_name."_path"].$jsScriptFile
				);
			} else if ( (strpos($jsScriptFile, "http") !== FALSE) || (strpos($jsScriptFile, "/") !== FALSE)) {
				// is it an external JS file?
				$js .= sprintf(
					'<script id="%s" type="text/javascript" src="%s"></script>', 
					$key, 
					$jsScriptFile
				);
			} else if ( !empty($jsScriptFile) ) {
				// is it a JS source code?
				$js .= sprintf(
					'<script id="%s" type="text/javascript">%s</script>', 
					$key, 
					$jsScriptFile
				);
			}
		}
		$config->static["JS_PLUGIN"]		= $js;
		$config->static["JS_CONTENIDO"]		= "";
		$css = "";
		foreach ((array)$param["css"] as $key => $cssSourceFile) {
			$checkFile = substr( $cssSourceFile, 0, (strpos($cssSourceFile, "?") !== false) ? strpos($cssSourceFile, "?") : strlen($cssSourceFile));
			if ( is_readable($this->getCfg()->path['contenido'].$checkFile) ) {
				// is it a contenido css file?
				$css .= sprintf(
					'<link id="%s" rel="stylesheet" type="text/css" href="%s" />', 
					$key, 
					$cssSourceFile
				);
			} else if ( is_readable($this->getCfg()->plugins[$plugin_name].$checkFile) ) {
				// is it a plugin css file?
				$css .= sprintf(
					'<link id="%s" rel="stylesheet" type="text/css" href="%s" />', 
					$key, 
					$this->getCfg()->plugins[$plugin_name."_path"].$cssSourceFile
				);
			} else if ( (strpos($cssSourceFile, "http") !== FALSE) || (strpos($cssSourceFile, "/") !== FALSE)) {
				// is it an external css file?
				$css .= sprintf(
					'<link id="%s" rel="stylesheet" type="text/css" href="%s" />', 
					$key, 
					$cssSourceFile
				);
			} else if ( !empty($cssSourceFile) ) {
				// is it a css source code?
				$css .= sprintf(
					'<style id="%s">%s</style>', 
					$key, 
					$cssSourceFile
				);
			}
		}
		$config->static["CSS_PLUGIN"]		= $css;
		$config->static["CSS_CONTENIDO"]	= "";
		
		$this->setTplVars($sTplName, $config);
		$sHTML = $oTemplate->template->generate(
			$this->getCfg()->templates[$plugin_name."_header"], 
			TRUE
		);
		return ( $sHTML );
	}
	
	
	/**
	 * generate page closing HTML depending on configuarion in plugin's config file
	 * 
	 * @param	array	$param
	 * @param	array	$param
	 */
	public function page_close ( $html = "" ) {
		$plugin_name	= $this->_con("plugin_name");
		$sTplName		= "plugin_page";
		$oTemplate		= $this->getTpl($sTplName, TRUE);
		$html			= $this->getCfg()->templates[$plugin_name."_pageprintlink"];
		if ( empty($html) ) {
			$config = (object)array("static" => array("FOOTER_HTML" => ""));
		} else	{
			$config = (object)array("static" => array("FOOTER_HTML" => $html));
		}
		$this->setTplVars($sTplName, $config);
		$sHTML = $oTemplate->template->generate(
			$this->getCfg()->templates[$plugin_name."_footer"], 
			TRUE
		);
		return ( $sHTML );
	}
	
	public function generateNavigationList ( $navitems = array(), $navname = "navlist", $navitemname = "navitem", $level = 0 ) {
		$nav_name		= (empty($navname)) ? "navlist" : $navname;
		$navitem_name	= (empty($navitemname)) ? "navitem" : $navitemname;
		$level			= ($level) ? (int)$level : 0;
		$sess			= $this->_con('sess');
		$plugin_name	= $this->_con('plugin_name');
		$template_name	= $nav_name.'_level_'.$level;
		$oTemplate		= $this->getTpl($template_name, true);
		$statics		= array(
			"NAV_LIST_ID"			=> (($level > 0) ? "sub".$nav_name : $nav_name ),
			"NAV_LIST_CLASSNAMES"	=> $nav_name." ".$template_name." ".(($level > 0) ? "sub".$nav_name : "" ),
		);
		$dynamics		= array();
		$html			= "";
		if ( !empty( $this->getConfig()->cfg['templates'][$plugin_name.'_'.$nav_name] ) ) {
			$oTemplate->content	= $this->getConfig()->cfg['templates'][$plugin_name.'_'.$nav_name];
		}
		foreach ((array)$navitems as $key => $navitem) {
			
			//echo "<pre>".print_r($navitem, true)."</pre>";
			$aTplItem = array(
				"NAV_ITEM_ID"			=> "" .$navitem_name."_".$key. "",
				"NAV_ITEM_CLASSNAMES"	=> $navitem_name. " " .$navitem_name."_".$key. "",
				"NAV_ITEM_CONTENT"		=> ""
			);
			if ( !empty($navitem["hrefstr"]) && isset($navitem["frames"]) && is_array($navitem["frames"]) ) {
				if (count($navitem["frames"]) == 3) {
					$sItem = sprintf(
						$navitem["hrefstr"],
						$navitem["classname"],
						'left_bottom',
						$sess->url("main.php?area=$plugin_name&frame=2".$navitem["frames"]['left_bottom'].""),
						'right_top',
						$sess->url("main.php?area=$plugin_name&frame=3".$navitem["frames"]['right_top'].""),
						'right_bottom',
						$sess->url("main.php?area=$plugin_name&frame=4".$navitem["frames"]['right_bottom'].""),
						i18n($navitem["label"])
					);
					$aTplItem["NAV_ITEM_CONTENT"] .= $sItem;
				}
				if (count($navitem["frames"]) == 2) {
					$sItem = sprintf(
						$navitem["hrefstr"],
						$navitem["classname"],
						'right_top',
						$sess->url("main.php?area=$plugin_name&frame=3".$navitem["frames"]['right_top'].""),
						'right_bottom',
						$sess->url("main.php?area=$plugin_name&frame=4".$navitem["frames"]['right_bottom'].""),
						i18n($navitem["label"])
					);
					$aTplItem["NAV_ITEM_CONTENT"] .= $sItem;
				}
			} else if ( !empty($navitem["hrefstr"]) && isset($navitem["parameters"]) && is_array($navitem["parameters"]) ) {
					$sItem = vsprintf(
						$navitem["hrefstr"], 
						$navitem["parameters"]
					);
					$aTplItem["NAV_ITEM_CONTENT"] .= $sItem;
			} else if ( !empty($navitem["hrefstr"]) ) {
					$aTplItem["NAV_ITEM_CONTENT"] .= $navitem["hrefstr"];
			}
			if ( isset($navitem["subitems"]) && is_array($navitem["subitems"]) ) {
				$aTplItem["NAV_ITEM_CONTENT"] .= $this->generateNavigationList(
					$navitem["subitems"], 
					$nav_name, 
					$navitem_name, 
					$level+1
				);
			}
			array_push($dynamics, $aTplItem);
		}
		$vars = (object)array("static" => $statics, "dynamic" => $dynamics);
		$this->setTplVars($template_name, $vars);
		$oTemplate		= $this->getTpl($template_name);
		$html .= $oTemplate->template->generate($oTemplate->content, true);
		return ($html);
	}
	
	/**
	 * create image <input> tag
	 */
	public static function submitImage ($inputName, $imgSrc, $inputId = "", $inputClass = "", $value = 1, $bReturn = true, $style = 0) {
		$sHTML = "";
		$sHTML .= sprintf(
			'<input type="%s" id="%s" class="%s" name="%s" src="%s" value="%s" />',
			'image',
			$inputId,
			$inputClass,
			$inputName,
			$imgSrc,
			$value
		);
		if ($bReturn) {
			return $sHTML;
		}
		echo $sHTML;
	}
	
	/**
	 * create <input type="button,submit,reset,image"> (style=0) or respectivly <button></button> (style=1, $type!='image') tag
	 */
	public static function button ($inputName, $type, $value = "", $onclick = "", $inputId = "", $inputClass = "", $bReturn = true, $style = 0, $imgSrc = NULL) {
		$sHTML = "";
		$types = array('button', 'submit', 'reset', 'image');
		$type = (in_array(strtolower($type), $types)) ? strtolower($type) : "button";
		if ( ($style == 1) && ($type != 'image') ) {
			$sHTML .= sprintf(
				'<button id="%s" class="%s" name="%s" />%s</button>',
				$inputId,
				$inputClass,
				$inputName,
				$value
			);
		} else {
			$sHTML .= sprintf(
				'<input type="%s" id="%s" class="%s" name="%s" value="%s" %s />',
				$type,
				$inputId,
				$inputClass,
				$inputName,
				(($type == 'image') ? 'src="'.$imgSrc.'"' : ''),
				$value
			);
		}
		if ($bReturn) {
			return $sHTML;
		}
		echo $sHTML;
	}
	
	/**
	 * New message style without tables - please use this
	 */
	public static function messageBox ($level, $message, $bReturn = true, $style = 0) {
		global $cfg, $auth;
		
		if ( ($level == 'debug') && ($auth->auth["perm"] != 'sysadmin') && ($auth->auth["perm"] != 'admin') ) {
			return '';
		}
		$mid = uniqid();
		switch ($level) {
			case "error":
				$head = i18n('Error');
				$head_class = 'alertbox_error';
				$frameColor = $cfg["color"]["notify_error"];
				$imgPath = $cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"]."icon_fatalerror.gif";
			break;
					
			case "warning":
				$head = i18n('Warning');
				$head_class = 'alertbox_warning';
				$bgColor = $cfg["color"]["notify_warning"];
				$imgPath = $cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"]."icon_warning.gif";
			break;
					
			case "info":
				$head = i18n('Info');
				$head_class = 'alertbox_info';
				$message = '<span style="color:#435d06">'.$message.'</span>';
				$bgColor = $cfg["color"]["notify_info"];
				$imgPath = $cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"]."but_ok.gif";
			break;
	
			case "debug":
				$head = i18n('Debug') . " <span style=\"font-weight: normal;\">(" .date("Y-m-d H:i:s"). ")</span>";
				$head_class = 'alertbox_debug';
				$message = '<span style="color:#435d06"><pre>'.$message.'</pre></span>';
				$bgColor = $cfg["color"]["notify_warning"];
				$imgPath = $cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"]."icon_construction.gif";
			break;

			case "help":
				$head = i18n('Help');
				$head_class = 'alertbox_help';
				$message = '<span style="color:#435d06">'.$message.'</span>';
				$bgColor = $cfg["color"]["notify"];
				$imgPath = $cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"]."but_help.gif";
			break;

			default:
				$head = i18n('Notification');
				$head_class = 'alertbox_notification';
				$message = '<span style="color:#435d06">'.$message.'</span>';
				$bgColor = $cfg["color"]["notify"];
				$imgPath = $cfg["path"]["contenido_fullhtml"].$cfg["path"]["images"]."but_ok.gif";
			break;
		}
		
		if ($style == 1) {
			// Box on login page
			$messageBox = 
				'<div class="alertbox '.$head_class.'_color" id="contenido_'.$level.'_'.$mid.'" style="border-top:0px;">' .
					'<h1 class="alertbox_head ' . $head_class . '">' . $head . '</h1>' .
					'<div class="alertbox_message">' . $message . '</div>' .
				'</div>';
				
		} else {
			// Simple box
			$messageBox = 
				'<div class="alertbox_line '.$head_class.'_color" id="contenido_'.$level.'_'.$mid.'">' .
					'<h1 class=" alertbox_head ' . $head_class . ' '.$head_class.'_color">' . $head . '</h1>' .
					'<div class="alertbox_message '.$head_class.'_color">' . $message . '</div>' .
				'</div>';
		}
		if ($bReturn) {
			return $messageBox;
		}
		echo $messageBox;
	}
	
	/**
	 * generate a HTML string of <option>-tags
	 */
	public function buildSelectOptions ($data = array(), $selected = FALSE) {
		$sHTML = "";
		foreach ($data as $key => $value) {
			$sHTML .= '<option '.
				'value="'.$key.'"'.
				( ( ($selected == $key) || (is_array($selected) && in_array($key, $selected)) ) ? ' selected="selected"' : '' ).
			'>'.
				htmlentities($value).
			'</option>';
		}
		return $sHTML;
	}
	
	/**
	 * simple string serialization
	 */
	public function serializeSimple ($param, $underscore = false) {
		if ( empty($param) ) return "";
		$searchterm = str_replace(
			array("ä","ö","ü","Ä","Ö","Ü","ß"),
			array("ae","oe","ue","Ae","Oe","Ue","ss"),
			trim($param)
		);
		$searchterm = str_replace(
			array(",",".","!","?"),
			array(" "," "," "," "),
			($searchterm)
		);
		$searchterm = $this->stripMultiSpaces($searchterm);
		if ($underscore === true) $searchterm = str_replace(" ", "_", $searchterm);
		return $searchterm;
	}
	
	/**
	 * reduce all multiple occuring whitespaces "   " with to a single one " " (maybe this can be done bettter via regex ;) )
	 */
	public function stripMultiSpaces ($param) {
		if (strpos($param, "  ") === false) return ($param);
		return ( $this->stripMultiSpaces( str_replace("  ", " ", $param) ) );
	}
	
}