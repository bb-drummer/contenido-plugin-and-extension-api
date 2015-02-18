<?php
if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}
cInclude('includes', 'Contenido/Object.php');

/**
 * 'simple' configurable (HTML) (multi-page) form generator object
 * 
 * @author	bba
 * @package	Contenido_Plugin_GUI_Form
 * @uses	Contenido_Object
 */
class Contenido_Plugin_GUI_Form extends Contenido_Object {
	
	/**
	 * form object container id
	 * @var		STRING	$_sFormId
	 */
	public $_sFormId = NULL;
	
	/**
	 * form object container id prefix string
	 * @var		STRING	$_sFormIdPrefix
	 */
	public $_sFormIdPrefix = 'form_';
	
	/**
	 * form object session hash
	 * @var		STRING	$_sFormHash
	 */
	public $_sFormHashId = NULL;
	
	/**
	 * collection of form elements
	 * @var 	ARRAY		$_aColumns
	 */
	public $_aElements = array();
	
	/**
	 * db form SQL query string
	 * @var 	STRING		$_sQuery
	 */
	public $_sQuery = "";
	
	/**
	 * db form name string
	 * @var 	STRING		$_sFormName
	 */
	protected $_sFormName = "";
	
	/**
	 * form db object container
	 * @var		DB_Contenido	$_oDB
	 */
	public $_oDB = null;
	
	/**
	 * form data error container object
	 * @var		Exception	$_oFormDataError
	 */
	public $_oFormDataError = NULL;
	
	/**
	 * @return the $_notifications
	 */
	public $_oFormNotifications = array();
	
	/**
	 * form session container
	 * @var		ARRAY	$_oFormSession
	 */
	public $_oFormSession = NULL;
	
	/**
	 * (JS) form configuration parameters
	 *
	 * @var ARRAY
	 */
	public $_oFormConfig = array(
		"name"				=> "formOverviewTable",			// generic form name
		"url"				=> FALSE,						// url to a JSON application containing the data
		"async"				=> FALSE,						// use xmlhttprequest for form submition
		"method"			=> 'POST',						// http method to submit form
		"enctype"			=> 'DATA',						// form encoding type: URL(encoded), (form-)DATA or (text/)PLAIN
		"token"				=> '',							// form identity token
	
		"showTopPanel"		=> FALSE,
		"showBottomPanel"	=> FALSE,
		"generateJS"		=> TRUE,
	
		"dbTableName"		=> FALSE,						// db table name
		"primaryKey"		=> "id",						// db table primary index column name
	
		"formdataname"		=> "formdata",					// used in inputs name attribute
		
		"pagination"		=> 'both',						// show Pagination Option: false|top|bottom
		"formactions"		=> 'both',						// show form action group: false|top|bottom|both
	
		// form tools (sort, filter, pagination) settings
		"oFormTools"		=> array(
			"page"				=> 1,							// page to display
			"pages"				=> 1,							// number of overall pages
		),
		
		// extended option set default settings
		"debugMode"			=> TRUE,						// turn debug mode 'on' (true) and 'off' (false)
		"hash"				=> TRUE,						// session's unique form container hash
		"cssClassName"		=> "", 							// additional user defined CSS class name
		"noDataRowClickNew"	=> FALSE,						// click on 'no data' row display will open edit mask for new record
		"editOnDblClickRow"	=> FALSE,						// doubble-click on data row display will open edit mask for selected record
		"dateFormat"		=> 'd.m.Y H:i,s',				// d|m ; d => dd/mm/yyyy; m => mm/dd/yyyy
		
		"elements"			=> array(),						// set of form input controls options
		/*array(
			"title"		=> i18n("basic information"),
			"label"		=> i18n("basic information"),		// for fieldset legend
			"name"		=> "basicdata",
			"type"		=> "fieldset",
			"page"		=> 1,								// for multipage forms include a 'page' parameter on 1. level (other level page parameters will not get recognised)
			"elements"		=> array(
				array(
					"title"		=> i18n("name"),
					"field"		=> "name",
					"type"		=> "text",
					"required"	=> true,
					"default"	=> "",
					"attr"		=> array()
				),
				// ...
			),
			// ...
		),*/
		
		
		// additionally some more type specific options can be set
		"oFormPages"		=> array(),						// set of form pages options
		"oFieldsets"		=> array(),						// set of form fieldset options
		"oFormActions"		=> array(),						// set of form specific action options, 'submit' and 'reset' are defined automatically
		
		"oTemplates"		=> array(						// path to form templates
			"form"			=> '{FORM_NOTIFICATIONS}<form id="{FORM_ID}" name="{FORM_NAME}" class="{FORM_CLASSES}" action="{FORM_URL}" method="{FORM_METHOD}" enctype="{FORM_ENCTYPE}" target="{FORM_TARGET}"><ul>{FORM_ELEMENTS}<li>{FORM_ACTIONS}</li></ul></form>{FORM_NOTIFICATIONS}',
			"fieldset"		=> '<li><fieldset class="{FORM_FIELDSET_CLASSES}"><legend>{FORM_FIELDSET_CAPTION}</legend><ul>{FORM_FIELDSET_ELEMENTS}</ul></fieldset></li>',
			"element"		=> '<li><label class="{FORM_ELEMENT_LABEL_CLASSES}" for="{FORM_ELEMENT_ID}"><span class="label">{FORM_ELEMENT_LABEL}</span><span class="control">{FORM_ELEMENT_CONTROL}</span></label><span class="{FORM_ELEMENT_ACTIONS_CLASSES}">{FORM_ELEMENT_ACTIONS}</span></li>',
			"elementaction"	=> '<span class="{FORM_ELEMENTACTION_CLASSES}" for="{FORM_ELEMENTACTION_ID}"><span class="label">{FORM_ELEMENTACTION_LABEL}</span><span class="control">{FORM_ELEMENTACTION_CONTROL}</span></span>',
			"actions"		=> '<li><span class="{FORM_ACTIONS_CLASSES}">{FORM_RESET}{FORM_SUBMIT}</span></li>',
			"notifications"	=> '{FORM_PROCESS_ERRORS}{FORM_PROCESS_NOTIFICATIONS}',
			"javascript"	=> '<script type="text/javascript">var oForm = new Contenido_Plugin_Form(FORM_CONFIG_JSON);</script>',
		),
		
		"oMessages"		=> array(						// form messages
			// error messages
			"error_field_required"		=> "Field <strong>%s</strong> has to be filled!",
			"error_field_outofrange"	=> "Field <strong>%s</strong> has to be in range <strong>%s</strong>!",
			"error_field_invalid"		=> "Field <strong>%s</strong> has an invalid value!",
		
			// other messages
		),
	);
	
	/**
	 * AJAX form tools parameters
	 *
	 * @var ARRAY
	 */
	public $_oFormTools = array(
		"page"				=> 1,							// page to display
		"pages"				=> 1,							// number of overall pages
		"autocomplete"		=> FALSE,						// activate autocomlete feature for form values
		"filterFields"		=> FALSE,						// show filter to reduce numbers of fields to display
	);
	
	/**
	 * availabe form field types
	 * @var ARRAY
	 */
	public $types = array("text", "password", "button", "file", "radio", "checkbox", "textarea", "select", "hidden");
	
	/**
	 * availabe form encoding types
	 * @var ARRAY
	 */
	public $aEncTypes = array(
		"URL"	=> "application/x-www-form-urlencoded",
		"DATA"	=> "multipart/form-data",
		"PLAIN"	=> "text/plain"
	);
	
	/**
	 * name of actions to skip default framework output rendering process
	 * @var ARRAY
	 */
	public $aNoRenderActions = array(
		"reset", 
		"update", 
		"config", 
		"prepare", 
		"get", 
		"put", 
		"delete", 
		"getsessionhash", 
		"getsessiondata",
		"getformdata"
	);
	
	/**
	 * overridable method to initialize user's form configurations and object options
	 */
	public function configForm () {
	}
	
	/**
	 * initialize default form configurations and object options
	 * @see Contenido_Object::init()
	 */
	public function __init ( $options = array() ) {
		parent::__init();
		
		$aRequest						= $this->_getAllParams();
		$this->_oForm					= $this->getDb();
		
		if ( !isset($aRequest["PHPSESSID"]) || empty($aRequest["PHPSESSID"]) ) {
			unset( $aRequest["PHPSESSID"] );
		}

		if ( !isset($aRequest["hash"]) || empty($aRequest["hash"]) ) {
			$aRequest["hash"]			= $this->getHash();
		} else {
			$this->_setFormHashId($aRequest["hash"]);
		}
		
		$this->_oFormConfig["hash"]	= $aRequest["hash"]; // $this->getHash();
		$this->_oFormConfig["id"]		= $this->getId();
				
		$aNonFormToolsParams = $aRequest;
		foreach ((array)$this->getFormTools() as $key => $value) {
			if (array_key_exists( $key, $aRequest) ) {
				unset($aNonFormToolsParams[$key]);
			}
		}
		$this->_oFormConfig["url"]		= $this->_con('sess')->url( 'main.php?'.http_build_query($aNonFormToolsParams) );
		
		$defaults = $this->getFormConfig();
		//$mergedOptions = array_merge_recursive( $defaults, $options );
		$mergedOptions = array_merge( $defaults, $options );
		$this->setFormConfig( $mergedOptions );
		$this->setFormTools( $mergedOptions["oFormTools"] );
		$this->setElements( $mergedOptions["elements"] );
		$this->setFormTemplates($mergedOptions["oTemplates"]);
		$this->setFormActions($mergedOptions["oFormActions"]);
		
		$this->_parseToolsParams();
		if (isset($options["dbFormName"])) { $this->setFormName( trim($options["dbFormName"]) ); }
		$this->getSession();
		
		$this->configForm();
	}
	
	public function __construct ( $options = array() ) {
		parent::__construct($options);
		$this->__init($options);
		return ($this);
	}
	
	public function generateForm ( $bReturn = TRUE ) {
		$sHTML				= "";
		$aElements			= array(); 
		$aHTMLFormElements	= $this->getElements();
		$oTpl				= $this->getTpl('baseform', TRUE);
		$cfg				= $this->getCfg();
		
		$sHTMLFormHeader = "";
		$sHTMLFormFooter = "";
		$sHTMLFormColumnGroup = "";
		
		if ($this->getFormConfig(TRUE)->showHeaderRow) {
			$sHTMLFormHeader	=  "";
		}
		
		if ($this->getFormConfig(TRUE)->showFooterRow) {
			$sHTMLFormFooter	=	$this->getFooter();
		}
		
		$oTpl->template->set('s', 'php_session_id',			$this->_con('sess')->id);
		$oTpl->template->set('s', 'CONTENIDO_URL_BASE',		$this->getCfg()->path['contenido_fullhtml']);
		
		$oTpl->template->set('s', 'NOTIFICATION',			$sPageOutput);
		$oTpl->template->set('s', 'BROWSE',					$sPageOutput);

		
		
		$oTpl->template->set('s', 'FORM_ID',				$this->getId());
		$oTpl->template->set('s', 'FORM_NAME',				$this->getFormName());
		$oTpl->template->set('s', 'FORM_CLASSES',			$this->getFormConfig(TRUE)->classnames);
		$oTpl->template->set('s', 'FORM_URL',				$this->getFormConfig(TRUE)->action);
		$oTpl->template->set('s', 'FORM_METHOD',			strtoupper($this->getFormConfig(TRUE)->method) );
		$oTpl->template->set('s', 'FORM_ENCTYPE',			( isset($this->aEncTypes[strtoupper($this->getFormConfig(TRUE)->enctype)]) ) ? $this->aEncTypes[strtoupper($this->getFormConfig(TRUE)->enctype)] : $this->aEncTypes['DATA'] );
		$oTpl->template->set('s', 'FORM_TARGET',			$this->getFormConfig(TRUE)->target);
		$oTpl->template->set('s', 'FORM_NOTIFICATIONS',		implode("", (array)$this->getNotifications()) );
		
		$oTpl->template->set('s', 'FORM_HEADER',			$sHTMLFormHeader);
		$oTpl->template->set('s', 'FORM_FOOTER',			$sHTMLFormFooter);
		
		$oTpl->template->set('s', 'FORM_ACTIONS',			$this->generateFormAction($oAction));
		
		$oTpl->template->set('s', 'FORM_ELEMENTS',			$this->generateFormElements($aHTMLFormElements));
		
//			"form"			=> '{FORM_NOTIFICATIONS}<form id="{FORM_ID}" name="{FORM_NAME}" class="{FORM_CLASSES}" action="{FORM_URL}" method="{FORM_METHOD}" enctype="{FORM_ENCTYPE}" target="{FORM_TARGET}">{FORM_ELEMENTS}{FORM_ACTIONS}</form>{FORM_NOTIFICATIONS}',
		
		/* foreach ($aHTMLFormElements as $iRow => $sRow) {
			$sActions = "";
			$sFormTitle = $aHTMLFormElements[$iRow]["name"];
			$aActions = $this->getRowActions();
			foreach ($aActions as $key => $aAction) {
				if ( !isset($aAction["static"]) ) { $aAction["static"] = array(); } 
				$aAction["static"]["ACTIONNAME"] = $key;
				$aAction["static"]["ACTIONCLASS"] = "con_formaction" . (($aHTMLFormElements[$iRow]["active"] != 1) ? " inactive" : "") ;
				$aAction["static"]["ROWID"] = $aHTMLFormElements[$iRow]["id"];
				$aAction["static"]["IDX"] = $iRow;
				/ *if ($aHTMLFormElements[$iRow]["active"] == 1) {
					$sActions .= "<a class=\"deactivate\" href=\"#deactivate\"><img src=\"images/online.gif\" border=\"0\" alt=\"[deactivate -".$sFormTitle."-]\" title=\"deactivate\" /></a> ";
				} else {
					$sActions .= "<a class=\"activate\" href=\"#activate\"><img src=\"images/offline.gif\" border=\"0\" alt=\"[activate -".$sFormTitle."-]\" title=\"activate\" /></a> ";
				}* /
				$sActions .= $this->generateFormAction($aAction);
			}
			//$sActions .= "<input type=\"hidden\" name=\"rowid[]\" value=\"".$aForms[$iRow]["id"]."\" />";
			$sRow = str_replace("!ACTIONS_".$aHTMLFormElements[$iRow]["id"]."!", $sActions, $sRow);
			$oTpl->template->set('d', 'ROWID', "row_".$aHTMLFormElements[$iRow]["id"]);
			$oTpl->template->set('d', 'CELLS', $sRow);
			if (($iRow % 2) == 0) {
				$oTpl->template->set('d', 'CSS_CLASS', 'even');
			} else {
				$oTpl->template->set('d', 'CSS_CLASS', 'odd');
			}
			$oTpl->template->next();
		} */
		$sHTML .= $oTpl->template->generate( $this->getFormConfig(TRUE)->oTemplates["form"], TRUE );
		if ($bReturn) {
			return ($sHTML);
		}
		echo ($sHTML);
		return ($this);
	}
	
	public function generateFormElements ( $aElements = NULL, $parent = NULL, $level = 0, $bReturn = TRUE ) {
		$sHTML = "";
		if ( !is_array($aElements) ) {
			return "";
		} 
		foreach ($aElements as $key => $element) {
			if ( is_array($element) ) { $element = (object)$element; }
			if ( ($element->type == 'fieldset') && (is_array($element->elements)) ) {
				// generate fieldset mark-up
				$element->fieldID = ( ($parent != NULL) ? $parent->fieldID . '_' : $this->getId() . '_' ). 'fieldset_'.$level.'_'.$key;
				$sElementsHTML = $this->generateFormElements($element->elements, $element, 1+$level, $bReturn);
				$oTpl = $this->getTpl('fieldset_'.$level.'_'.$key, TRUE);
				$oTpl->template->set('s', 'FORM_FIELDSET_CLASSES'	, "formFieldset ".$element->class);
				$oTpl->template->set('s', 'FORM_FIELDSET_ELEMENTS'	, $sElementsHTML);
				$oTpl->template->set('s', 'FORM_FIELDSET_CAPTION'	, $element->label);
				$sHTML .= $oTpl->template->generate( $this->getFormConfig(TRUE)->oTemplates["fieldset"], TRUE );
			} else {
				// generate singe form control mark-up
				$sInputHTML		=	"";
				$bRequired		=	($element->required === true);
				$bError			=	($element->error != false);
				$sClassnames	=	
					"formInput".
					" ".$element->field."".
					(($bRequired) ? " required" : "").
					(($bError) ? " error" : "").
					(($element->classnames) ? " ".$element->classnames : "");
				$sAttributes = "";
				if ( is_array($element->attr) ) {
					foreach ($element->attr as $key => $value) {
						$sAttributes .= " ".($key)."=\"".($value)."\"";
					}
				}
				$element->fieldID =  ( ($parent != NULL) ? $parent->fieldID . '_' : $this->getId() . '_' ). 'element_'.$element->field.'_'.$level.'_'.$key;
				switch ($element->type) {
					case "select":
						$sInputHTML = "<select ".
							"id=\"".$element->fieldID."\" ".
							"name=\"".$element->field."\" ".
							"class=\"".$sClassnames."\" ".
							"size=\"".(((int)$element->size > 0) ? (int)$element->size : 1)."\" ".
						">".
							"!_SELECTOPTIONS_".$element->field."_!".
						"</select>";
						$sSelectOptions = "";
						if ( is_array($element->options) ) {
							foreach ($element->options as $key => $oOption) {
								$sSelectOptions .= "<option ".
									"value=\"".$oOption["value"]."\" ".
										( ( !is_array($element->value) && ($oOption["value"] == $element->value) ) ? "selected=\"selected\" " : "" ).
										( ( is_array($element->value) && in_array($oOption["value"], $element->value) ) ? "selected=\"selected\" " : "" ).
								">".
									"".$oOption["text"]."".
								"</option>";
							}
						}
						$sInputHTML = str_replace("!_SELECTOPTIONS_".$element->field."_!", $sSelectOptions, $sInputHTML);
					break;
					case "radio":
					case "checkbox":
						$sInputHTML = "<ul class=\"".$element->type."list\">";
						if ( is_array($element->options) ) {
							foreach ($element->options as $key => $oOption) {
								$sInputHTML .= "<li>".
									"<label>".
										"<input ".
											"id=\"".$element->fieldID."_".$key."\" ".
											"type=\"".$element->type."\" ".
											"name=\"".$element->field."\" ".
											"class=\"".$sClassnames."\" ".
											"value=\"".$oOption["value"]."\" ".
												( ( !is_array($element->value) && ($oOption["value"] == $element->value) ) ? "checked=\"checked\" " : "" ).
												( ( is_array($element->value) && in_array($oOption["value"], $element->value) ) ? "checked=\"checked\" " : "" ).
											(!empty($sAttributes) ? $sAttributes : "").
										"/>".
										"".$oOption["text"]."".
									"</label>".
								"</li>";
							}
						} else {
							$sInputHTML = "";
						}
						if ( !empty($sInputHTML) ) {
							$sInputHTML .= "</ul>";
						}
					break;
					case "textarea":
						$sInputHTML = "<textarea ".
							"id=\"".$element->fieldID."\" ".
							"name=\"".$element->field."\" ".
							"class=\"".$sClassnames."\" ".
							(((int)$element->rows > 0) ? "rows=\"".((int)$element->rows)."\" " : "").
							(((int)$element->cols > 0) ? "cols=\"".((int)$element->cols)."\" " : "").
							(!empty($sAttributes) ? $sAttributes : "").
							">".
							$element->value.
						"</textarea>";
					break;
					
					default:
						if ( !in_array( strtolower($element->type), (array)$this->types ) ) {
							$sType = "text";
						} else  {
							$sType = strtolower($element->type);
						}
						$sInputHTML = "<input ".
							"id=\"".$element->fieldID."\" ".
							"type=\"".$sType."\" ".
							"name=\"".$element->field."\" ".
							"class=\"".$sClassnames."\" ".
							"value=\"".$element->value."\" ".
							(((int)$element->size > 0) ? "size=\"".((int)$element->size)."\" " : "").
							(!empty($sAttributes) ? $sAttributes : "").
							"/>";
					break;
				}
				if ( isset($element->setHTML) && !empty($element->setHTML) ) {
					$sInputHTML = trim($element->setHTML, " \n\t\r");
				}
				
				$sElementActions = "";
			    if ( isset($element->actions) && is_array($element->actions) ) {
			    	foreach ($element->actions as $key => $oAction) {
			    		$oAction["element"] = $element;
			    		$sElementActions .= "".$this->generateFormElementAction($oAction);
			    	}
			    }
				$oTpl = $this->getTpl('element_'.$element->field.'_'.$level.'_'.$key, TRUE); 
				$oTpl->template->set('s', 'FORM_ELEMENT_ID'					, $element->fieldID);
				$oTpl->template->set('s', 'FORM_ELEMENT_LABEL'				, $element->title);
				$oTpl->template->set('s', 'FORM_ELEMENT_LABEL_CLASSES'		, "formLabel");
				$oTpl->template->set('s', 'FORM_ELEMENT_CONTROL'			, $sInputHTML);
				$oTpl->template->set('s', 'FORM_ELEMENT_ACTIONS'			, $sElementActions);
				$oTpl->template->set('s', 'FORM_ELEMENT_ACTIONS_CLASSES'	, "formElementActions");
				$sHTML .= $oTpl->template->generate( $this->getFormConfig(TRUE)->oTemplates["element"], TRUE );
			}
		}
		
		/*
		"oTemplates"		=> array(						// path to form templates
			"form"			=> '{FORM_NOTIFICATIONS}<form id="{FORM_ID}" name="{FORM_NAME}" class="{FORM_CLASSES}" action="{FORM_URL}" method="{FORM_METHOD}" enctype="{FORM_ENCTYPE}" target="{FORM_TARGET}">{FORM_ELEMENTS}{FORM_ACTIONS}</form>{FORM_NOTIFICATIONS}',
			"fieldset"		=> '<fieldset class="{FORM_FIELDSET_CLASSES}"><legend>{FORM_FIELDSET_CAPTION}<legend>{FORM_FIELDSET_ELEMENTS}</fieldset>',
			"element"		=> '<label class="{FORM_ELEMENT_LABEL_CLASSES}" for="{FORM_ELEMENT_ID}"><span class="label">{FORM_ELEMENT_LABEL}</span>{FORM_ELEMENT_CONTROL}</label><span class="{FORM_ELEMENT_ACTIONS_CLASSES}">{FORM_ELEMENT_ACTIONS}</span>',
			"elementaction"	=> '<span class="{FORM_ELEMENTACTION_CLASSES}" for="{FORM_ELEMENTACTION_ID}"><span class="label">{FORM_ELEMENTACTION_LABEL}</span><span class="control">{FORM_ELEMENTACTION_CONTROL}</span></span>',
			"actions"		=> '<li><span class="{FORM_ACTIONS_CLASSES}">{FORM_RESET}{FORM_SUBMIT}</span></li>',
			"notifications"	=> '{FORM_PROCESS_ERRORS}{FORM_PROCESS_NOTIFICATIONS}',
			"javascript"	=> '<script type="text/javascript">var oForm = new Contenido_Plugin_Form(FORM_CONFIG_JSON);</script>',
		),
		*/
		
		if ($bReturn) {
			return ($sHTML);
		}
		echo ($sHTML);
		return ($this);
	}
	
	public function generateFormElementAction ( $oAction, $bReturn = TRUE ) {
		if (is_array($oAction)) { $oAction = (object)$oAction; }
		$sHTML	= "";
		if ( !empty( $this->getFormConfig(TRUE)->oTemplates["elementaction"] ) ) {
			$accessGranted	= true;
			$area			= $this->_con('area'); 
			$action			= $this->_con('action');
			$plugin_name	= $this->_con('plugin_name');
			if ( defined('CON_FRAMEWORK') && !empty($plugin_name) && !empty($area) && !empty($action) ) {
				// if we are in a plugin context, check for access permission...
				$accessGranted = $this->_con('perm')->have_perm_area_action($area, $action);
			}
			$oActionTpl = $this->getTpl('elementaction', TRUE);
			$oActionTpl->template->set('s', 'FORM_ELEMENTACTION_CLASSES', "formElementAction ".$oAction->classnames);
			$oActionTpl->template->set('s', 'FORM_ELEMENTACTION_LABEL', $oAction->label);
			$oActionTpl->template->set('s', 'FORM_ELEMENTACTION_ID', "formAction_".$oAction->name."_".$oAction->element->fieldid);
			$aActionTrigger = array(
				'<a href="#" title="'.$oAction->label.'">', '</a>'
			);
			$oActionTpl->template->set('s', 'FORM_ELEMENTACTION_CONTROL', implode("", $aActionTrigger));
			
			if ( !$accessGranted && !empty($oAction->_template) ) {
				$sHTML .= $oActionTpl->template->generate($this->getFormConfig(TRUE)->oTemplates["elementaction"], TRUE);
			} else {
				$sHTML .= $oActionTpl->template->generate($this->getFormConfig(TRUE)->oTemplates["elementaction"], TRUE);	
			}
		}
		if ($bReturn) {
			return ($sHTML);
		}
		echo ($sHTML);
		return ($this);
	}
	
	public function generateFormAction ( $oAction, $bReturn = TRUE ) {
		if (is_array($oAction)) { $oAction = (object)$oAction; }
		$sHTML	= "";
		if ( !empty($oAction->template) ) {
			$accessGranted	= true;
			$area			= $this->_con('area'); 
			$action			= $this->_con('action');
			$plugin_name	= $this->_con('plugin_name');
			if ( defined('CON_FRAMEWORK') && !empty($plugin_name) && !empty($area) && !empty($action) ) {
				// if we are in a plugin context, check for access permission...
				$accessGranted = $this->_con('perm')->have_perm_area_action($area, $action);
			}
			$oActionTpl = $this->getTpl('rowaction', TRUE);
			$this->setTplVars('rowaction', $oAction);
			
			if ( !$accessGranted && !empty($oAction->_template) ) {
				$sHTML .= $oActionTpl->template->generate($oAction->_template, TRUE);
			} else {
				$sHTML .= $oActionTpl->template->generate($oAction->template, TRUE);	
			}
		}
		if ($bReturn) {
			return ($sHTML);
		}
		echo ($sHTML);
		return ($this);
	}
	
	public function generateFormTools ( $sTemplate = "", $bReturn = TRUE ) {
		$sHTML	= "";
		if ( !empty($sTemplate) ) {
			$config		= $this->getFormConfig(TRUE);
			$tools		= $this->getFormTools(TRUE);
			$oToolTpl	= $this->getTpl('formtools', TRUE);
			$seperator	= " || ";
			$sPaginationOutput	= (!$config->pagination)	? null : $this->generateFormToolsPagination($tools->page, $tools->pageCount);
			$sFormActions		= (!$config->formActions)	? null : $this->generateFormToolsActions();

			$oToolTpl->template->set('s', 'FORM_TOOLS_CLASSNAMES',		"formTools");
			$oToolTpl->template->set('s', 'FORM_TOOLS_PAGINATION',		trim(implode($seperator, array(
				$sPaginationOutput, $sRowsPerPageOutput, 
			)) , " |"));
			$oToolTpl->template->set('s', 'FORM_TOOLS_ACTIONS',		$sFormActions);
			$oToolTpl->template->set('s', 'FORM_NOTIFICATIONS',				""); //$sToolNotificationOutput);
			
			$sHTML .= $oToolTpl->template->generate( $sTemplate,	true );
		}
		if ($bReturn) {
			return ($sHTML);
		}
		echo ($sHTML);
		return ($this);
	}
			
	public function generateFormToolsPagination ( $sPageStart = "", $sRowsPerPage = 15, $bReturn = TRUE ) {
		$sHTML	= "";
		$iRows	= count($this->getRows(TRUE));
		$sPageBrowserOutput	= i18n("go to page").' '. '<span class="input">'. 
			'<select id="ePageStart" name="page" class="text_medium text_right" onchange="/*artSort(this)*/">';
			if ( !is_numeric($sRowsPerPage) ) {
				$pages = 1;
			} else {
				$pages = (int)(($iRows) / (int)$sRowsPerPage)    +   ( ( ($iRows % (int)$sRowsPerPage) > 0) ? 1 : 0 );
			}
			for ($i = 1; $i <= $pages; $i++) {
				$sPageBrowserOutput	.= '<option value="'.($i).'"'.
					( ($sPageStart == ($i-1)) ? ' selected="selected"' : '' ) .
				'>'.$i.'</option>';
			}
		$sPageBrowserOutput	.= '</select> '; // . Contenido_Plugin_GUI::submitImage('browse', 'images/submit.gif');
		$sHTML .= $sPageBrowserOutput;
		if ($bReturn) {
			return ($sHTML);
		}
		echo ($sHTML);
		return ($this);
	}
		
	public function generateFormToolsNotification ( $sMessage = "", $bReturn = TRUE ) {
		$sHTML	= "";
		if ( !empty($sTemplate) ) {
			$sToolNotificationOutput		= '';
			//...
			$sHTML .= $sToolNotificationOutput;
		}
		if ($bReturn) {
			return ($sHTML);
		}
		echo ($sHTML);
		return ($this);
	}
	
	public function generateFormToolsActions ( $bReturn = TRUE ) {
		$sHTML	= "";
		$sFormActions					= '<ul class="formActionList">'.
			( ( $this->_con('perm')->have_perm_area_action($this->_con('plugin_name'), "createform") ) ? '<li class="formActionItem">'. sprintf(
				$this->getCfg()->templates[$this->_con('plugin_name').'_multilink_3Frames'], 
				"con_link_icon createfunction",
				"left_bottom",
				$this->_con('sess')->url("main.php?". "area=".$this->_con('plugin_name') ."&". "frame="."2" ."&". "actionarea="."forms" ."&". "action="."createform"),
				"right_top",
				$this->_con('sess')->url("main.php?". "area=".$this->_con('plugin_name') ."&". "frame="."3" ."&". "actionarea="."forms" ."&". "action="."createform"),
				"right_bottom",
				$this->_con('sess')->url("main.php?". "area=".$this->_con('plugin_name') ."&". "frame="."4" ."&". "actionarea="."forms" ."&". "action="."createform"),
				i18n("create new form")
			) ."</li>" : "" ) .
		/*( ( $perm->have_perm_area_action($plugin_name, "reportoverview") ) ? '<li class="formActionItem">'. sprintf(
				$cfg['templates'][$plugin_name.'_multilink_3Frames'], 
				"con_link_icon openoverview",
				"left_bottom",
				$sess->url("main.php?". "area=".$oPlugin->_con('plugin_name') ."&". "frame="."2" ."&". "actionarea="."reports" ."&". "action="."reportoverview"),
				"right_top",
				$sess->url("main.php?". "area=".$oPlugin->_con('plugin_name') ."&". "frame="."3" ."&". "actionarea="."reports" ."&". "action="."reportoverview"),
				"right_bottom",
				$sess->url("main.php?". "area=".$oPlugin->_con('plugin_name') ."&". "frame="."4" ."&". "actionarea="."reports" ."&". "action="."reportoverview"),
				i18n("form reports")
			) ."</li>" : "" ) .*/
		"</ul>";
		$sHTML .= $sFormActions;

		if ($bReturn) {
			return ($sHTML);
		}
		echo ($sHTML);
		return ($this);
	}
	
	public function generateFormTopPanel ( $bReturn = TRUE ) {
		$sHTML	= "";
		$sHTML .= $this->generateFormTools( $this->getFormConfig(TRUE)->oTemplates["top"] );
		if ($bReturn) {
			return ($sHTML);
		}
		echo ($sHTML);
		return ($this);
	}
	
	public function generateFormBottomPanel ( $bReturn = TRUE ) {
		$sHTML	= "";
		$sHTML .= $this->generateFormTools( $this->getFormConfig(TRUE)->oTemplates["bottom"] );
		if ($bReturn) {
			return ($sHTML);
		}
		echo ($sHTML);
		return ($this);
	}
	
	public function generateFormJS ( $sJSTemplate = "", $bReturn = TRUE ) {
		$this->getTpl("formJS", TRUE);
		$this->setTplVars("formJS", array(
			"static" => array(
				"FORM_ID" => ($this->getId()),
				"FORM_CONFIG_JSON" => json_encode($this->getFormConfig(TRUE)),
			)
		));
		$sJS = $this->getTpl("formJS")->template->generate($sJSTemplate, TRUE);
		if ($bReturn) {
			return ($sJS);
		}
		echo ($sJS);
		return ($this);
	}
	
	public function generate ( $bReturn = TRUE ) {
		$options = (object)$this->getFormConfig();
		$sHTML =
			(($options->showTopPanel) ? $this->generateFormTopPanel() : ""). 
			$this->generateForm() . 
			(($options->showBottomPanel) ? $this->generateFormBottomPanel() : "") . 
			(($options->generateJS) ? $this->generateFormJS($this->getFormConfig(TRUE)->oTemplates["javascript"]) : "");
		if ($bReturn) {
			return ($sHTML);
		}
		echo ($sHTML);
		return ($this);
	}
	
	/*****************************************************
	 ** form private/internal methods
	 ****************************************************/
	
	/**
	 * retrieve db form's column definitions
	 * @return	Contenido_Plugin_GUI_Form
	 */
	protected function _getDbFormColumns ( $full = FALSE, $prependFormName = FALSE ) {
		$sFormName	= $this->getFormName();
		$aFields	= array();
		if (!empty($sFormName)) {
			$oDB = $this->getDb();
			$sQuery = "SHOW FULL COLUMNS FROM `".Contenido_Security::escapeDB($sFormName, $oDB)."`;";
			$oDB->query($sQuery);
			while ($oDB->next_record()) {
				if ( $full ) {
					$aFields[$oDB->f('Field')] = array(
						"Field"			=> (($prependFormName) ? $sFormName."." : "" ) .$oDB->f('Field'),
						"Type"			=> $oDB->f('Type'),
						"Collation"		=> $oDB->f('Collation'),
						"Null"			=> ($oDB->f('Null') == 'NO') ? FALSE : !!$oDB->f('Null'),
						"Key"			=> $oDB->f('Key'),
						"Default"		=> $oDB->f('Default'),
						"Extra"			=> $oDB->f('Extra'),
						"Privileges"	=> $oDB->f('Privileges'),
						"Comment"		=> $oDB->f('Comment'),
					);
				} else {
					array_push($aFields, $oDB->f('Field'));
				}
			}
		}
		return $aFields;
	}
	
	protected function _buildEqualLikeWhere ( $aFields, $searchData = "", $conjunction = "OR" ) {
		$searchData = trim($searchData);
		if ( empty($searchData) ) {
			return "";
		}
		$oDB			= $this->getDb();
		$aWheres		= array();
		$conjunction	= ($conjunction == "OR") ? "OR" : "AND";
		foreach ((array)$aFields as $key => $aField) {
			if ( !empty($aField["Collation"]) || ($aField["Type"] == 'timestamp') ) {
				$op	= "LIKE";
			} else {
				$op	= "=";
			}
			array_push(
				$aWheres,
				("`".$aField["Field"]."`") . 
					(" ".$op." ") . 
				("'".(($op == "LIKE") ? "%" : "").Contenido_Security::escapeDB($searchData, $oDB).(($op == "LIKE") ? "%" : "")."'")
			);
		} 
		$glue = " ) ".$conjunction." ( ";
		$sWhere =  "( ".implode($glue, $aWheres)." )" . " " ;
		return $sWhere;
	}

	/**
	 * apply tavle tools filter/sort parameter
	 * @return	Contenido_Plugin_GUI_Form
	 */
	protected function _buildQuery () {
		$this->_parseToolsParams();
		$sFormName	= $this->getFormName();
		$sQuery		= "";
		
		
		if (!empty($sFormName)) {
			$oDB		= $this->getDb();
			$sQuery		= "SELECT * FROM `".Contenido_Security::escapeDB($sFormName, $oDB)."` ";
			$oTools		= $this->getFormTools(TRUE);
			$aFields	= $this->_getDbFormColumns(TRUE);
			if ( !empty($oTools->searchData) && empty($oTools->filterData) ) {
				$oTools->filterCol = '*'; $oTools->filterData = trim($oTools->searchData);
			}
			if ( !empty($oTools->filterCol) && !empty($oTools->filterData) && array_key_exists($oTools->filterCol, $aFields) ) {
				if ( ($oTools->filterCol != '*') ) {
					if ( !empty($aFields[$oTools->filterCol]["Collation"]) || ($aFields[$oTools->filterCol]["Type"] == 'timestamp') ) {
						$op	= "LIKE";
					} else {
						$op	= "=";
					}
					$sQuery		.= "WHERE" ." (". 
										("`".Contenido_Security::escapeDB($oTools->filterCol, $oDB)."`") ."". 
								   (" ".$op." ").
								   		("'"."%".Contenido_Security::escapeDB($oTools->filterData, $oDB)."%"."'") .") " ;
				} else {
				}
			} else if ( ($oTools->filterCol == '*') && !empty($oTools->filterData) ) {
					$sQuery 	.= "WHERE" ." (". $this->_buildEqualLikeWhere($aFields, $oTools->filterData) . ") ";
			} else {
				$sQuery		.= "WHERE" ." " ."1" ." ";
			}
			if ( ($oTools->onlyActive == 'true') ) {
				$sQuery		.= "AND (`active`='1') ";
			}
			if ( !empty($oTools->orderField) && !empty($oTools->sortOrder) ) {
				$sQuery		.= "ORDER BY ".
									("`".Contenido_Security::escapeDB($oTools->orderField, $oDB)."`") . " " . 
									(($oTools->sortOrder == 'asc') ? "ASC" : "DESC") . " ";
			}
			if ( !empty($oTools->page) && !empty($oTools->pageCount) && ($oTools->pageCount != 'all') ) {
				$sCountQuery = str_replace("SELECT *", "SELECT count(`active`) AS `numRows`", $sQuery);
				$oDB->query($sCountQuery);
				while ( $oDB->next_record() ) {
					$iRows = $oDB->f('numRows');
				}
				$oTools->pages = (int)($iRows / $oTools->pageCount) + ( (($iRows % $oTools->pageCount) > 0) ? 1 : 0);
				$lStart = ( (int)$oTools->page - 1 ) * (int)$oTools->pageCount;
				if ($oTools->page > $oTools->pageCount) {
					$lStart = 0;
				}
				$sQuery		.= "LIMIT " . $lStart . ", " . (int)$oTools->pageCount;
			}
		}
		$this->setQuery($sQuery);
		return $this;
	}
	
	/**
	 * parse request's form tools parameters
	 * 
	 * @param	ARRAY	$aParams
	 * @return	Contenido_Plugin_GUI_Form
	 */
	protected function _parseToolsParams ( $aParams = NULL ) {
		$aParams	= ($aParams) ? $aParams : $this->_getAllParams();
		$aTools		= (array)$this->getFormTools();
		foreach ($aTools as $key => $value) {
			if ( array_key_exists($key, $aParams) && !empty($aParams[$key]) ) {
				$aTools[$key] = trim($aParams[$key]);
			} else if ( array_key_exists($key, $aParams) ) {
				$aTools[$key] = NULL;
			}
		}
		$this->setFormTools((object)$aTools);
		return $this;
	}
	
	/**
	 * get panel's element id
	 * @return	STRING
	 */
	protected function getId () {
		if (empty($this->_sFormId)) {
			$this->_setFormId();
		}
		return $this->_sFormId;
	}
	
	/**
	 * set form's element id
	 * @param	STRING	$sFormId
	 * @return	Contenido_Plugin_GUI_Form
	 */
	protected function _setFormId ( $sFormId = "" ) {
		if (empty($sFormId)) {
			$sFormId = uniqid($this->_sFormIdPrefix, TRUE);
		}
		$this->_sFormId = str_replace(".", "_", $sFormId);
		return $this;
	}
	
	/**
	 * get panel hash id
	 * @return	STRING
	 */
	protected function getHash () {
		if (empty($this->_sFormHashId)) {
			
			$this->_setFormHashId();
		}
		return $this->_sFormHashId;
	}
	
	/**
	 * create or set panel hash id
	 * @param	STRING	$sFormHashId
	 * @return	self
	 */
	protected function _setFormHashId ( $sFormHashId = "" ) {
		if ( empty($sFormHashId) && empty($this->_sFormHashId) ) {
			// create new panel hash id
			$sHash = md5( uniqid(NULL, FALSE)."_".uniqid(NULL, TRUE) );
		} else if ( empty($sFormHashId) && !empty($this->_sFormHashId) ) {
			// apply previously set hash id
			$sHash = $this->_sFormHashId;
		} else {
			// set given hash id
			$sHash = $sFormHashId;
		}
		$this->_sFormHashId = $sHash;
		$this->_oFormConfig["hash"]  = $this->_sFormHashId;
		//$this->getSession();
		return $this;
	}
	
	/**
	 * get session
	 * @return	Zend_Session_Namespace
	 */
	public function getSession () {
		if ($this->_oFormSession === NULL) {
			$this->_setFormSession(NULL, FALSE);
		}
		return $this->_oFormSession;
	}
	
	/**
	 * create panel session object
	 * @param	STRING	$sFormHashId
	 * @param	BOOLEAN	$bReset
	 * @return	void
	 */
	protected function _setFormSession ( $sFormHashId = "", $bReset = TRUE ) {
		if (empty($sFormHashId)) {
			$sFormHashId = $this->getHash();
		}
		if ( (($this->_oFormSession === NULL) ) || $bReset) {
			$sNamespace = $this->_sFormIdPrefix . $sFormHashId;
			if ( !isset($_SESSION[$sNamespace]) ) {
				$_SESSION[$sNamespace] = array();
			}
			$this->_oFormSession = $_SESSION[$sNamespace]; //new Zend_Session_Namespace($sNamespace);
			// $this->_oFormSession->setExpirationSeconds(300, 'accept_request');
			if ( !$this->_oFormSession["initialized"] ) {
				$this->_oFormSession["sId"]				= $this->getId();
				$this->_oFormSession["iRequestCount"]		= 0;
				$this->_oFormSession["accept_request"]		= TRUE;
				$this->_oFormSession["initialized"]		= TRUE;
			}
			if (!$bReset) {
				//$this->_oFormSession["iRequestCount"]++;
			}
		} 
		$this->_oFormSession["iRequestCount"]++;
		$this->_oFormSession["aFormConfig"]		= $this->getFormConfig();
		$this->_oFormSession["aFormTools"]		= $this->getFormTools();
			
		$_SESSION[$sNamespace] = $this->_oFormSession;
		
		return $this;
	}
	
	/**
	 * @return	ARRAY	$_aElements
	 */
	public function getElements () {
		return (array)$this->_aElements;
	}

	/**
	 * @param	ARRAY	$_aElements
	 * @return	Contenido_Plugin_GUI_Form
	 */
	public function setElements ( $_aElements = array() ) {
		$this->_aElements = $_aElements;
		$this->_oFormConfig["elements"] =& $this->_aElements;
		$this->_setFormSession(NULL, FALSE);
		return $this;
	}

	/**
	 * @return	ARRAY	$_aFooterCells
	 */
	public function getFooter () {
		return (array)$this->_aFooterCells;
	}

	/**
	 * @param	ARRAY	$_aFooterCells
	 * @return	Contenido_Plugin_GUI_Form
	 */
	public function setFooter ( $_aFooterCells = array() ) {
		$this->_aFooterCells = $_aFooterCells;
		$this->_oFormConfig["oFooterCells"] =& $this->_aFooterCells;
		$this->_setFormSession(NULL, FALSE);
		return $this;
	}

	/**
	 * @return	ARRAY	$aHTMLFormElements
	 */
	protected function _loadDbData  ( ) {
		$aHTMLFormElements			= array();
		$oDB			= $this->getDb();
		$sSQLGetRows	= $this->getQuery();
		if ($sSQLGetRows) {
			$oDB->query($sSQLGetRows);
			while ( $oDB->next_record() ) {
				if ( $asObject ) {
					array_push($aHTMLFormElements, (object)$oDB->Record);
				} else {
					array_push($aHTMLFormElements, $oDB->Record);
				}
			}
		}
		return ($aHTMLFormElements);
	}

	/**
	 * @return	STRING	$_sQuery
	 */
	public function getQuery () {
		$formName = trim($this->getFormName());
		if ( !empty( $formName ) ) {
			$this->_buildQuery();
		} else {
			return (FALSE);
		}
		return $this->_sQuery;
	}

	/**
	 * @param	STRING	$_sQuery
	 * @return	Contenido_Plugin_GUI_Form
	 */
	public function setQuery ( $_sQuery ) {
		$this->_sQuery = $_sQuery;
		$this->_setFormSession(NULL, FALSE);
		return $this;
	}

	/**
	 * @return	DB_Contenido	$_oForm
	 */
	public function _getForm () {
		if ( !($this->_oForm instanceof DB_Contenido) ) {
			$this->_setForm();
		}
		return $this->_oForm;
	}

	/**
	 * @param	DB_Contenido	$_oForm
	 * @return	Contenido_Plugin_GUI_Form
	 */
	public function _setForm ($_oForm) {
		$this->_oForm = $this->getDb();
		$this->_setFormSession(NULL, FALSE);
		return $this;
	}

	/**
	 * @return	Exception	$_oFormDataError
	 */
	public function _getFormDataError () {
		return $this->_oFormDataError;
	}

	/**
	 * @param	Exception	$_oFormDataError
	 * @return	Contenido_Plugin_GUI_Form
	 */
	public function setFormDataError ( $_oFormDataError ) {
		$this->_oFormDataError = $_oFormDataError;
		$this->_setFormSession(NULL, FALSE);
		return $this;
	}

	/**
	 * @return the $_sFormName
	 */
	public function getFormName () {
		return $this->_sFormName;
	}

	/**
	 * @param STRING $_sFormName
	 * @return	Contenido_Plugin_GUI_Form
	 */
	public function setFormName ( $_sFormName ) {
		$this->_sFormName = $_sFormName;
		$this->_setFormSession(NULL, FALSE);
		return $this;
	}

	/**
	 * @return	ARRAY	$_oFormConfig
	 */
	public function getFormConfig ( $asObject = FALSE ) {
		return ($asObject) ? (object)$this->_oFormConfig : (array)$this->_oFormConfig;
	}

	/**
	 * @param	ARRAY	$_oFormConfig
	 * @return	Contenido_Plugin_GUI_Form
	 */
	public function setFormConfig ( $_oFormConfig ) {
		$this->_oFormConfig = $_oFormConfig;
		$this->_setFormSession(NULL, FALSE);
		return $this;
	}

	/**
	 * @return	OBJECT	$_oFormConfig["oRowActions"]
	 */
	public function getFormActions ( $asObject = FALSE ) {
		return ($asObject) ? (object)$this->_oFormConfig["oFormActions"] : (array)$this->_oFormConfig["oFormActions"] ;
	}

	/**
	 * @param	ARRAY	$_oFormActions
	 * @return	Contenido_Plugin_GUI_Form
	 */
	public function setFormActions ( $_oFormActions ) {
		$this->_oFormConfig["oFormActions"] = $_oFormActions;
		$this->_setFormSession(NULL, FALSE);
		return $this;
	}

	/**
	 * @return	OBJECT	$_oFormTools
	 */
	public function getFormTemplates ( $asObject = FALSE ) {
		return ($asObject) ? (object)$this->_oFormConfig["oTemplates"] : (array)$this->_oFormConfig["oTemplates"];
	}

	/**
	 * @param	ARRAY	$_oFormTools
	 * @return	Contenido_Plugin_GUI_Form
	 */
	public function setFormTemplates ( $_oFormTools ) {
		$this->_oFormConfig["oTemplates"] = $_oFormTools;
		$this->_setFormSession(NULL, FALSE);
		return $this;
	}

	/**
	 * @return	OBJECT	$_oFormTools
	 */
	public function getFormTools ( $asObject = FALSE ) {
		return ($asObject) ? (object)$this->_oFormTools : (array)$this->_oFormTools;
	}

	/**
	 * @param	ARRAY	$_oFormTools
	 * @return	Contenido_Plugin_GUI_Form
	 */
	public function setFormTools ( $_oFormTools ) {
		$this->_oFormTools = $_oFormTools;
		$this->_oFormConfig["oFormTools"] =& $this->_oFormTools;
		$this->_setFormSession(NULL, FALSE);
		return $this;
	}

	/**
	 * @return	STRING	$_oFormToolsURL
	 */
	public function getFormToolsURL () {
		$cfg = $this->getFormConfig();
		return $cfg["url"];
	}

	/**
	 * @return	ARRAY	$aNoRenderActions
	 */
	public function getNoRenderActions () {
		return $this->aNoRenderActions;
	}

	/**
	 * @param	ARRAY	$aNoRenderActions
	 * @return	Contenido_Plugin_GUI_Form
	 */
	public function setNoRenderActions ( $aNoRenderActions ) {
		$this->aNoRenderActions = $aNoRenderActions;
		$this->_setFormSession(NULL, FALSE);
		return $this;
	}

	/**
	 * @return	OBJECT	$_oFormConfig["oRowActions"]
	 */
	public function getRowActions ( $asObject = FALSE ) {
		return ($asObject) ? (object)$this->_oFormConfig["oRowActions"] : (array)$this->_oFormConfig["oRowActions"];
	}

	/**
	 * @param	ARRAY	$_oRowActions
	 * @return	Contenido_Plugin_GUI_Form
	 */
	public function setRowActions ( $_oRowActions ) {
		$this->_oFormConfig["oRowActions"] = $_oRowActions;
		$this->_setFormSession(NULL, FALSE);
		return $this;
	}
	
	/**
	 * detect maximum number of pages from elements configuration,
	 * also sets page parameter if not already set
	 * 
	 * @return	INTEGER
	 */
	public function _getMaxPages ( ) {
		$max = 1;
		foreach ($this->getElements() as $key => $element) {
			if (isset($element["page"])) {
				if ($element["page"] > $max) {
					$max = (int)$element["page"];
				} 
			} else {
				$element["page"] = $max;
			}
		}
		return $max;
	}

	/**
	 * @return the $_oFormNotifications
	 */
	public function getNotifications ( $sMode = "" ) {
		switch ($sMode) {
			case 1 :
				return (array)$this->_oFormNotifications;
			break;
			
			default:
				return implode("", (array)$this->_oFormNotifications);
			break;
		}
	}

	/**
	 * @param field_type $_notifications
	 */
	public function addNotification ( $mNotifications ) {
		if (is_array($mNotifications)) {
			$this->_oFormNotifications = array_merge((array)$this->_oFormNotifications, $mNotifications);
		} else if (!empty($mNotifications)) {
			$this->_oFormNotifications = array_merge((array)$this->_oFormNotifications, array($mNotifications));
		}
	}

	/**
	 * @param field_type $_notifications
	 */
	public function resetNotifications () {
		$this->_oFormNotifications = array();
	}

	/**
	 * set current form values
	 * 
	 * @param ARRAY $aData
	 * @param BOOLEAN $bForce
	 */
	public function setValues ( $aData = array(), $bForce = FALSE ) {
		return ($this);
	}

	/**
	 * get current form values, for given field(set) or all
	 * 
	 * @param STRING $field
	 */
	public function getValues ( $field = NULL ) {
		return ($this);
	}

	/**
	 * set error messages to element properties
	 * 
	 * @param ARRAY $aErrorData
	 */
	public function setErrors ( $aErrorData = array() ) {
		return ($this);
	}

	/**
	 * set error messages to element properties
	 * 
	 * @param ARRAY $aErrorData
	 */
	public function setElementError ( &$element = NULL, $aErrorData = array() ) {
		return ($this);
	}

	/**
	 * validate form value data array
	 * retruns TRUE or array with error messages
	 * 
	 * @param BOOLEAN|ARRAY $aData
	 * @param BOOLEAN $bAddNotification
	 */
	public function validateData ( $aData = array(), $bAddNotification = true ) {
		return (true);
	}

	public function validateFormElements ( $aElements = NULL, $aData = NULL, $parent = NULL, $level = 0, $bReturn = TRUE ) {
		$aErrors = array();
		if ( !is_array($aElements) ) {
			return true;
		} 
		foreach ($aElements as $key => $element) {
			if ( is_array($element) ) { $element = (object)$element; }
			if ( ($element->type == 'fieldset') && (is_array($element->elements)) ) {
				// generate fieldset mark-up
				$element->fieldID = ( ($parent != NULL) ? $parent->fieldID . '_' : $this->getId() . '_' ). 'fieldset_'.$level.'_'.$key;
				$aSubErrors = $this->validateFormElements($element->elements, $aData[$element->name], $element, 1+$level, $bReturn);
				if ($aSubErrors !== true) {
					$aErrors[$element->name] = $aSubErrors;
				}
			} else {
				if ( $element->required && (!isset($aData[$element->name]) || empty($aData[$element->name])) ) {
					$aErrors[$element->name] = sprintf(
						$this->getConfig(TRUE)->oMessages["error_field_required"],
						htmlentities($element->label)
					);
				}
				if (false) {}
				switch ($element->type) {
					case "select":
						$sInputHTML = "<select ".
							"id=\"".$element->fieldID."\" ".
							"name=\"".$element->field."\" ".
							"class=\"".$sClassnames."\" ".
							"size=\"".(((int)$element->size > 0) ? (int)$element->size : 1)."\" ".
						">".
							"!_SELECTOPTIONS_".$element->field."_!".
						"</select>";
						$sSelectOptions = "";
						if ( is_array($element->options) ) {
							foreach ($element->options as $key => $oOption) {
								$sSelectOptions .= "<option ".
									"value=\"".$oOption["value"]."\" ".
										( ( !is_array($element->value) && ($oOption["value"] == $element->value) ) ? "selected=\"selected\" " : "" ).
										( ( is_array($element->value) && in_array($oOption["value"], $element->value) ) ? "selected=\"selected\" " : "" ).
								">".
									"".$oOption["text"]."".
								"</option>";
							}
						}
						$sInputHTML = str_replace("!_SELECTOPTIONS_".$element->field."_!", $sSelectOptions, $sInputHTML);
					break;
					case "radio":
					case "checkbox":
						$sInputHTML = "<ul class=\"".$element->type."list\">";
						if ( is_array($element->options) ) {
							foreach ($element->options as $key => $oOption) {
								$sInputHTML .= "<li>".
									"<label>".
										"<input ".
											"id=\"".$element->fieldID."_".$key."\" ".
											"type=\"".$element->type."\" ".
											"name=\"".$element->field."\" ".
											"class=\"".$sClassnames."\" ".
											"value=\"".$oOption["value"]."\" ".
												( ( !is_array($element->value) && ($oOption["value"] == $element->value) ) ? "checked=\"checked\" " : "" ).
												( ( is_array($element->value) && in_array($oOption["value"], $element->value) ) ? "checked=\"checked\" " : "" ).
											(!empty($sAttributes) ? $sAttributes : "").
										"/>".
										"".$oOption["text"]."".
									"</label>".
								"</li>";
							}
						} else {
							$sInputHTML = "";
						}
						if ( !empty($sInputHTML) ) {
							$sInputHTML .= "</ul>";
						}
					break;
					case "textarea":
						$sInputHTML = "<textarea ".
							"id=\"".$element->fieldID."\" ".
							"name=\"".$element->field."\" ".
							"class=\"".$sClassnames."\" ".
							(((int)$element->rows > 0) ? "rows=\"".((int)$element->rows)."\" " : "").
							(((int)$element->cols > 0) ? "cols=\"".((int)$element->cols)."\" " : "").
							(!empty($sAttributes) ? $sAttributes : "").
							">".
							$element->value.
						"</textarea>";
					break;
					
					default:
						if ( !in_array( strtolower($element->type), (array)$this->types ) ) {
							$sType = "text";
						} else  {
							$sType = strtolower($element->type);
						}
						$sInputHTML = "<input ".
							"id=\"".$element->fieldID."\" ".
							"type=\"".$sType."\" ".
							"name=\"".$element->field."\" ".
							"class=\"".$sClassnames."\" ".
							"value=\"".$element->value."\" ".
							(((int)$element->size > 0) ? "size=\"".((int)$element->size)."\" " : "").
							(!empty($sAttributes) ? $sAttributes : "").
							"/>";
					break;
				}
			}
		}
		
		if ( is_array($aErrors) && !empty($aErrors) ) {
			return ($aErrors);
		}
		return (true);
	}
	
	
}