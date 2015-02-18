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
	

		"dbTableName"		=> FALSE,						// db table name
		"primaryKey"		=> "id",						// db table primary index column name
		
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
		
		"oElements"			=> array(),						// set of form input controls options
		/*array(
			"title"		=> i18n("basic information"),
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
			"form"			=> '{FORM_NOTIFICATIONS}<form id="{FORM_ID}" name="{FORM_NAME}" class="{FORM_CLASSES}" action="{FORM_URL}" method="{FORM_METHOD}" enctype="{FORM_ENCTYPE}" target="{FORM_TARGET}">{FORM_ELEMENTS}{FORM_ACTIONS}</form>{FORM_NOTIFICATIONS}',
			"fieldset"		=> '<fieldset class="{FORM_FIELDSET_CLASSES}"><caption>{FORM_FIELDSET_CAPTION}<caption>{FORM_FIELDSET_ELEMENTS}</fieldset>',
			"element"		=> '<label class="{FORM_ELEMENT_LABEL_CLASSES}" for="{FORM_ELEMENT_ID}">{FORM_ELEMENT_LABEL}{FORM_ELEMENT_CONTROL}</label><span class="{FORM_ELEMENT_ACTIONS_CLASSES}">{FORM_ELEMENT_ACTIONS}</span>',
			"actions"		=> '<span class="{FORM_ACTIONS_CLASSES}">{FORM_RESET}{FORM_SUBMIT}</span>',
			"notifications"	=> '{FORM_PROCESS_ERRORS}{FORM_PROCESS_NOTIFICATIONS}',
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
		$this->setElements( $mergedOptions["oElements"] );
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
		_debug($aHTMLFormElements);
		
		$sHTMLFormHeader = "";
		$sHTMLFormFooter = "";
		$sHTMLFormColumnGroup = "";
		
		if ($this->getFormConfig(TRUE)->showHeaderRow) {
			$sHTMLFormFooter	=  +"";
		}
		
		if ($this->getFormConfig(TRUE)->showFooterRow) {
			$sHTMLFormFooter	= $this->getFooter();
		}
		
		$oTpl->template->set('s', 'php_session_id',			$this->_con('sess')->id);
		$oTpl->template->set('s', 'URLBASE',				$this->getCfg()->path['contenido_fullhtml']);
		
		$oTpl->template->set('s', 'NOTIFICATION',			$sPageOutput);
		$oTpl->template->set('s', 'BROWSE',					$sPageOutput);
		$oTpl->template->set('s', 'CLICK_ROW_NOTIFICATION',	$sPageOutput);

		$oTpl->template->set('s', 'HEADERS',				$sHTMLFormHeader);
		$oTpl->template->set('s', 'FOOTERS',				$sHTMLFormFooter);
		$oTpl->template->set('s', 'COLUMNGROUP',			"<columns>".$sHTMLFormColumnGroup."</columns>");
		
		
		foreach ($aHTMLFormElements as $iRow => $sRow) {
			$sActions = "";
			$sFormTitle = $aHTMLFormElements[$iRow]["name"];
			$aActions = $this->getRowActions();
			foreach ($aActions as $key => $aAction) {
				if ( !isset($aAction["static"]) ) { $aAction["static"] = array(); } 
				$aAction["static"]["ACTIONNAME"] = $key;
				$aAction["static"]["ACTIONCLASS"] = "con_formaction" . (($aHTMLFormElements[$iRow]["active"] != 1) ? " inactive" : "") ;
				$aAction["static"]["ROWID"] = $aHTMLFormElements[$iRow]["id"];
				$aAction["static"]["IDX"] = $iRow;
				/*if ($aHTMLFormElements[$iRow]["active"] == 1) {
					$sActions .= "<a class=\"deactivate\" href=\"#deactivate\"><img src=\"images/online.gif\" border=\"0\" alt=\"[deactivate -".$sFormTitle."-]\" title=\"deactivate\" /></a> ";
				} else {
					$sActions .= "<a class=\"activate\" href=\"#activate\"><img src=\"images/offline.gif\" border=\"0\" alt=\"[activate -".$sFormTitle."-]\" title=\"activate\" /></a> ";
				}*/
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
		}
		$sHTML .= $oTpl->template->generate( $this->getFormConfig(TRUE)->oTemplates["base"], TRUE );
		if ($bReturn) {
			return ($sHTML);
		}
		echo ($sHTML);
		return ($this);
	}
	
	public function generateFormElements ( $aElements = array(), $parent = NULL, $level = 0, $bReturn = TRUE ) {
		$sHTML = "";
		foreach ($aElements as $key => $element) { 
			$element = (object)$element;
			if ( ($element->type == 'fieldset') && (is_array($element->elements)) ) {
				$sHTML .= $this->getFormConfig(TRUE)->oTemplates["fieldset"];
				$sHTML .= $this->generateFormElements($element->elements, $element, ++$level, $bReturn);
			} else {
				switch ($element->type) {
					case 'date'		: break;
					case 'digit'	: break;
					case 'email'	: break;
					case 'range'	: break;
					case 'file'		: break;
					case 'hidden'	: break;
					case 'inList'	: break;
					case 'url'		: break;
					case 'oneWord'	: break;
					
					
					default: /* text */ break;
				}
				$sHTML .= $this->getFormConfig(TRUE)->oTemplates["element"];
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
				"TABLE_CONFIG_JSON" => json_encode($this->getFormConfig(TRUE)),
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
		$this->_oFormConfig["oElements"] =& $this->_aElements;
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

	

}