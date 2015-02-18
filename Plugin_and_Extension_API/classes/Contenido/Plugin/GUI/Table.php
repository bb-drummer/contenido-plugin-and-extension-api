<?php
if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}
cInclude('includes', 'Contenido/Object.php');

/**
 * 'simple' configurable (HTML) table generator object
 * 
 * @author	bba
 * @package	Contenido_Plugin_GUI_Table
 * @uses	Contenido_Object
 */
class Contenido_Plugin_GUI_Table extends Contenido_Object {
	
	/**
	 * table object container id
	 * @var		STRING	$_sTableId
	 */
	public $_sTableId = null;
	
	/**
	 * table object container id prefix string
	 * @var		STRING	$_sTableIdPrefix
	 */
	public $_sTableIdPrefix = 'table_';
	
	/**
	 * table object session hash
	 * @var		STRING	$_sTableHash
	 */
	public $_sTableHashId = null;
	
	/**
	 * collection of table columns
	 * @var 	ARRAY		$_aColumns
	 */
	public $_aColumns = array();
	
	/**
	 * collection of table footer cells
	 * @var 	ARRAY		$_aFooterCells
	 */
	public $_aFooterCells = array();
	
	/**
	 * collection of table rows
	 * @var 	ARRAY		$_aRows
	 */
	public $_aRows = array();
	
	/**
	 * db table SQL query string
	 * @var 	STRING		$_sQuery
	 */
	public $_sQuery = "";
	
	/**
	 * db table name string
	 * @var 	STRING		$_sTableName
	 */
	protected $_sTableName = "";
	
	/**
	 * table db object container
	 * @var		DB_Contenido	$_oTable
	 */
	public $_oTable = null;
	
	/**
	 * table data error container object
	 * @var		Exception	$_oTableDataError
	 */
	public $_oTableDataError = null;
	
	/**
	 * table session container
	 * @var		ARRAY	$_oTableSession
	 */
	public $_oTableSession = null;
	
	/**
	 * (JS) table configuration parameters
	 *
	 * @var ARRAY
	 */
	public $_oTableConfig = array(
		"data"				=> false,						// array of data
		"url"				=> false,						// url to a JSON application containing the data
		"allowMultiselect"	=> true,						// Not implemented yet
		"unsortedColumn"	=> array(),						// array of column you don't want to sort
		"dateFormat"		=> 'd.m.Y H:i,s',				// d|m ; d => dd/mm/yyyy; m => mm/dd/yyyy
		
		"filter"			=> 'both',						// show Filter Option at the bottom of the table
		"pagination"		=> 'both',						// show Pagination Option: false|top|bottom
		"sort"				=> 'both',						// show Search Option: false|top|bottom	
		"tableactions"		=> 'both',						// show table action group: false|top|bottom|both
		"rowactions"		=> 'right',						// show row action group: false|left|right|both
		"rowselector"		=> 'left',						// show row selector checkbox: false|left|right|both
	
		// table tools (sort, filter, pagination) settings
		"oTableTools"		=> array(
			"filterCol"			=> '',							// name of column to use for filtering
			"filterData"		=> '',							// filter query string
			"page"				=> 1,							// page to display
			"pages"				=> 1,							// number of overall pages
			"pageCount"			=> 25,							// list items per page
			"pageCountOptions"	=> array(						// items per page option lists
				"all", "15", "25", "50", "100", 
			),
			"searchData"		=> '',							// search query string	
			"orderField"		=> '',							// name of the field to sort by
			"sortOrder"			=> '',							// order name to sort field by (ASC or DESC)
			"onlyActive"		=> 0,							// show only records marked `active`='1'
		),
		
		// extended option set default settings
		"debugMode"			=> true,						// turn debug mode 'on' (true) and 'off' (false)
		"hash"				=> true,						// session's unique table container hash
		"primaryKey"		=> "id",						// session's unique table container hash
		"cssClassName"		=> "", 							// additional user defined CSS class name
		"noDataRowClickNew"	=> false,						// click on 'no data' row display will open edit mask for new record
		"editOnDblClickRow"	=> false,						// doubble-click on data row display will open edit mask for selected record

		"showHeaderRow"		=> true,
		"showFooterRow"		=> false,
		"showRowActions"	=> true,
		"showTopPanel"		=> true,
		"showBottomPanel"	=> true,
		"generateJS"		=> true,
	
		"oColumnSets"		=> array(),						// set of table column options
		"oFooterCells"		=> array(),						// set of table footer options
		
		"oTableActions"		=> array(),						// set of table specific action options
		"oRowActions"		=> array(						// set of row specific action options
			/*
			 * An action is defined via a '_template' which could either be 
			 * 	a) a static string
			 * or 
			 * 	b) a template-like string
			 * If '_template' is a template like string all entries in that action-set, 
			 * except for '_template' (and '_dyn' if used), are taken as static template
			 * replacements. 
			 * If the '_dyn' option is used those sets are tkes as block replacements in 
			 * that given template. 
			 * Alike Contenido's template object the '_template' string could also contain 
			 * a template filename to look for. The following template variables are reserved 
			 * and, if defined, will be overwritten with generated/determined values:
			 * 	form actions:
			 * 		'{ACTIONNAME}'	= that action-set's array key
			 * 		'{ACTIONCLASS}'	= action specific class names, if static variable 'class' is set, they are merged
			 * 		'{TABLEID}'		= value of the 'id' field of that data row
			 * 	row actions:
			 * 		'{ACTIONNAME}'	= that action-set's array key
			 * 		'{ACTIONCLASS}'	= action specific class names, if static variable 'class' is set, they are merged
			 * 		'{ROWID}'		= value of the 'id' field of that data row
			 * 		'{IDX}'			= that data row's continuous zero-based index
			 * 
			 * a row action example:
			 
			"editRowAction"	=> array(
				"template"	=>	'<a id="{ACTIONNAME}_{ROWID}" class="{ACTIONCLASS}" href="{href}" title="{label}" {onclick}>'.
									'<img src="{imgSrc}" border="0" height="16" alt="{label}" title="{label}" />'.
								'</a>',
				"static"		=>	array(
					"label"		=>	"edit row",
					"class"		=>	"my class names",
					"imgSrc"	=>	"img/btn_edit.png",
					"href"		=>	$sess->url('main.php?action=editrow&rowid={ROWID}),
					"onclick"	=>	"alert( 'action clicked: '+this.id );",
				),
				"dynamic"		=>	array(
					array(
						"dyn_var1"	=>	"...",
					),
					// ...
				)
			),
			
			 * ... will produce an output like...
			  
			 <a id="editRowAction_123" class="tableRowActionBtn editRowAction" href="main.php?action=editrow&rowid=123&contenido=1a2b3c4d5e6f7g8h" title="edit row" onclick="alert( 'action clicked: '+this.id );"><img src="img/btn_edit.png" border="0" height="17" /></a>
			
			 */
		),
	
		"oTemplates"		=> array(						// path to table templates
			"base"		=> "",
			"tools"		=> "",
			"browse"	=> "",
		),
		
	);
	
	/**
	 * AJAX table tools parameters
	 *
	 * @var ARRAY
	 */
	public $_oTableTools = array(
		"filterCol"			=> '',							// name of column to use for filtering
		"filterData"		=> '',							// filter query string
		"page"				=> 1,							// page to display
		"pages"				=> 1,							// number of overall pages
		"pageCount"			=> 25,							// list items per page
		"searchData"		=> '',							// search query string	
		"orderField"		=> '',							// name of the field to sort by
		"sortOrder"			=> '',							// order name to sort field by (ASC or DESC)
		"onlyActive"		=> 0,							// show only records marked `active`='1'
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
		"gettabledata"
	);
	
	/**
	 * overridable method to initialize user's table configurations and object options
	 */
	public function configTable () {
	}
	
	/**
	 * initialize default table configurations and object options
	 * @see Contenido_Object::init()
	 */
	public function __init ( $options = array() ) {
		parent::__init();
		
		$aRequest						= $this->_getAllParams();
		$this->_oTable					= $this->getDb();
		
		if ( !isset($aRequest["PHPSESSID"]) || empty($aRequest["PHPSESSID"]) ) {
			unset( $aRequest["PHPSESSID"] );
		}

		if ( !isset($aRequest["hash"]) || empty($aRequest["hash"]) ) {
			$aRequest["hash"]			= $this->getHash();
		} else {
			$this->_setTableHashId($aRequest["hash"]);
		}
		
		$this->_oTableConfig["hash"]	= $aRequest["hash"]; // $this->getHash();
		$this->_oTableConfig["id"]		= $this->getId();
				
		$aNonTableToolsParams = $aRequest;
		foreach ((array)$this->getTableTools() as $key => $value) {
			if (array_key_exists( $key, $aRequest) ) {
				unset($aNonTableToolsParams[$key]);
			}
		}
		$this->_oTableConfig["url"]		= $this->_con('sess')->url( 'main.php?'.http_build_query($aNonTableToolsParams) );
		
		$defaults = $this->getTableConfig();
		//$mergedOptions = array_merge_recursive( $defaults, $options );
		$mergedOptions = array_merge( $defaults, $options );
		$this->setTableConfig( $mergedOptions );
		$this->setTableTools( $options["oTableTools"] );
		$this->setColumns( $options["oColumnSets"] );
		$this->setTableTemplates($options["oTemplates"]);
		$this->setRowActions($options["oRowActions"]);
		
		$this->_parseToolsParams();
		if (isset($options["dbTableName"])) { $this->setTableName( trim($options["dbTableName"]) ); }
		$this->getSession();
		
		$this->configTable();
	}
	
	public function __construct ( $options = array() ) {
		parent::__construct($options);
		$this->__init($options);
		return ($this);
	}
	
	public function generateTable ( $bReturn = TRUE ) {
		$sHTML				= "";
		$aRows				= array();
		$aData				= $this->getRows(TRUE);
		$aHTMLTableColumns	= $this->getColumns();
		$aHTMLTableFooter	= array();
		$oTpl				= $this->getTpl('basetable', TRUE);
		$cfg				= $this->getCfg();
		
		$sHTMLTableHeader = "";
		$sHTMLTableFooter = "";
		$sHTMLTableColumnGroup = "";
		
		if ($this->getTableConfig(TRUE)->showHeaderRow) {
			$aColumns = array();
			$hasPredefinedActionColumn	= FALSE;
			$sActionCell				= "<td class=\"_rowaction_ headerbordercell\">".i18n("actions")."</td>";
			$sActionColumn				= "<column class=\"_rowaction_\" />";
			$showTableAction			= $this->getTableConfig(TRUE)->showRowActions;
			$actionPos					= $this->getTableConfig(TRUE)->rowactions;
			foreach ($aHTMLTableColumns as $iColumn => $aColumn) {
				if ($aColumn["field"]) {
					$aColumns[] = $aColumn["field"];
					$sHTMLTableHeader .= "<td class=\"".$aColumn["field"]." headerbordercell\">".$aColumn["title"]."</td>";
					$sHTMLTableColumnGroup .= "<column class=\"".$aColumn["field"]."\" />";
				} else {
					$sHTMLTableHeader .= "<td class=\"col_".$iColumn." headerbordercell\">".$aColumn["title"]."</td>";
					$sHTMLTableColumnGroup .= "<column class=\"col_".$iColumn."\" />";
				}
			}
			if (!$hasPredefinedActionColumn && $showTableAction) {
				$sHTMLTableHeader = ( (($actionPos == 'left') ||  ($actionPos == 'both')) ? $sActionCell : "" ) . 
					$sHTMLTableHeader . ( (($actionPos == 'right') ||  ($actionPos == 'both')) ? $sActionCell : "" );
				$sHTMLTableColumnGroup = ( (($actionPos == 'left') ||  ($actionPos == 'both')) ? $sActionColumn : "" ) . 
					$sHTMLTableColumnGroup . ( (($actionPos == 'right') ||  ($actionPos == 'both')) ? $sActionColumn : "" );
			}
		}
		
		if ($this->getTableConfig(TRUE)->showFooterRow) {
			$aHTMLTableFooter	= $this->getFooter();
		}
		
		$oTpl->template->set('s', 'php_session_id',			$this->_con('sess')->id);
		$oTpl->template->set('s', 'URLBASE',				$this->getCfg()->path['contenido_fullhtml']);
		
		$oTpl->template->set('s', 'NOTIFICATION',			$sPageOutput);
		$oTpl->template->set('s', 'BROWSE',					$sPageOutput);
		$oTpl->template->set('s', 'CLICK_ROW_NOTIFICATION',	$sPageOutput);

		$oTpl->template->set('s', 'HEADERS',				$sHTMLTableHeader);
		$oTpl->template->set('s', 'FOOTERS',				$sHTMLTableFooter);
		$oTpl->template->set('s', 'COLUMNGROUP',			"<columns>".$sHTMLTableColumnGroup."</columns>");
		
		foreach ( $aData as $iData => $oRowData ) {
			$sCells 					= "";
			$hasPredefinedActionColumn	= FALSE;
			$sActionCell				= "<td class=\"actions bordercell\">" . "!ACTIONS_".$oRowData["id"]."!";
			$showTableAction			= $this->getTableConfig(TRUE)->showRowActions;
			$actionPos					= $this->getTableConfig(TRUE)->rowactions;
			foreach ($aHTMLTableColumns as $iColumn => $aColumn) {
				if ( $aColumn["field"] && ($aColumn["field"] != "_rowaction_") ) {
					if ($aColumn["field"] == "lastchange") {
						if ( !empty($oRowData[$aColumn["field"]]) ) {
							$sCells .= "<td class=\"".$aColumn["field"]." bordercell\">".$oRowData[$aColumn["field"]]."</td>";
						} else if ( !empty($oRowData["created"]) ) {
							$sCells .= "<td class=\"".$aColumn["field"]." bordercell\">".$oRowData["created"]."</td>";
						} else {
							$sCells .= "<td class=\"".$aColumn["field"]." bordercell\"> - </td>";
						}
					} else if ($aColumn["field"] == "deadline") {
						if ( !empty($oRowData[$aColumn["field"]]) ) {
							$sCells .= "<td class=\"".$aColumn["field"]." bordercell\">".$oRowData[$aColumn["field"]]."</td>";
						} else {
							$sCells .= "<td class=\"".$aColumn["field"]." bordercell\"> - </td>";
						}
					} else {
						$sCells .= "<td class=\"".$aColumn["field"]." bordercell\">".$oRowData[$aColumn["field"]]."</td>";
					}
				} else {
					if ($aColumn["field"] == "_rowaction_") {
						$sCells .= $sActionCell;
						$hasPredefinedActionColumn = TRUE;
					} else {
						$sCells .= "<td class=\"col_".$iColumn." bordercell\">";
					}
					$sCells .= "</td>";
				}
			}
			if (!$hasPredefinedActionColumn && $showTableAction) {
				$sCells = ( (($actionPos == 'left') ||  ($actionPos == 'both')) ? $sActionCell."</td>" : "" ) . 
					$sCells . ( (($actionPos == 'right') ||  ($actionPos == 'both')) ? $sActionCell."</td>" : "" );
			}
			$aRows[] = $sCells;
		}
		
		foreach ($aRows as $iRow => $sRow) {
			$sActions = "";
			$sFormTitle = $aData[$iRow]["name"];
			$aActions = $this->getRowActions();
			foreach ($aActions as $key => $aAction) {
				if ( !isset($aAction["static"]) ) { $aAction["static"] = array(); } 
				$aAction["static"]["ACTIONNAME"] = $key;
				$aAction["static"]["ACTIONCLASS"] = "con_tableaction" . (($aData[$iRow]["active"] != 1) ? " inactive" : "") ;
				$aAction["static"]["ROWID"] = $aData[$iRow]["id"];
				$aAction["static"]["IDX"] = $iRow;
				/*if ($aData[$iRow]["active"] == 1) {
					$sActions .= "<a class=\"deactivate\" href=\"#deactivate\"><img src=\"images/online.gif\" border=\"0\" alt=\"[deactivate -".$sFormTitle."-]\" title=\"deactivate\" /></a> ";
				} else {
					$sActions .= "<a class=\"activate\" href=\"#activate\"><img src=\"images/offline.gif\" border=\"0\" alt=\"[activate -".$sFormTitle."-]\" title=\"activate\" /></a> ";
				}*/
				$sActions .= $this->generateTableAction($aAction);
			}
			//$sActions .= "<input type=\"hidden\" name=\"rowid[]\" value=\"".$aForms[$iRow]["id"]."\" />";
			$sRow = str_replace("!ACTIONS_".$aData[$iRow]["id"]."!", $sActions, $sRow);
			$oTpl->template->set('d', 'ROWID', "row_".$aData[$iRow]["id"]);
			$oTpl->template->set('d', 'CELLS', $sRow);
			if (($iRow % 2) == 0) {
				$oTpl->template->set('d', 'CSS_CLASS', 'even');
			} else {
				$oTpl->template->set('d', 'CSS_CLASS', 'odd');
			}
			$oTpl->template->next();
		}
		$sHTML .= $oTpl->template->generate( $this->getTableConfig(TRUE)->oTemplates["base"], TRUE );
		if ($bReturn) {
			return ($sHTML);
		}
		echo ($sHTML);
		return ($this);
	}
	
	public function generateTableAction ( $oAction, $bReturn = TRUE ) {
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
	
	public function generateTableTools ( $sTemplate = "", $bReturn = TRUE ) {
		$sHTML	= "";
		if ( !empty($sTemplate) ) {
			$config		= $this->getTableConfig(TRUE);
			$tools		= $this->getTableTools(TRUE);
			$oToolTpl	= $this->getTpl('tabletools', TRUE);
			$seperator	= " || ";
			$sFilterOutput		= (!$config->filter)		? null : $this->generateTableToolsFilter($tools->filterCol, $tools->filterData);
			$sOnlyActiveOutput	= (!$config->onlyActive)	? null : $this->generateTableToolsOnlyActive($tools->onlyActive);
			$sRowsPerPageOutput	= (!$config->pageCount)		? null : $this->generateTableToolsPageCount($tools->pageCount, $tools->pageCountOptions);
			$sPaginationOutput	= (!$config->pagination)	? null : $this->generateTableToolsPagination($tools->page, $tools->pageCount);
			$sSortOptionsOutput	= (!$config->sort)			? null : $this->generateTableToolsSort($tools->orderField, $tools->sortOrder);
			$sSearchOutput		= (!$config->search)		? null : null; // ""; // $this->generateTableToolsSearch($tools->searchData);
			$sTableActions		= (!$config->tableActions)	? null : $this->generateTableToolsActions();
			$oToolTpl->template->set('s', 'TABLE_TOOLS_CLASSNAMES',		"tableTools");
			$oToolTpl->template->set('s', 'TABLE_TOOLS_PAGINATION',		trim(implode($seperator, array(
				$sPaginationOutput, $sRowsPerPageOutput, 
			)) , " |"));
			$oToolTpl->template->set('s', 'TABLE_TOOLS_PAGEBROWSER',	$sPaginationOutput);
			$oToolTpl->template->set('s', 'TABLE_TOOLS_PAGECOUNT',		$sRowsPerPageOutput);
			
			$oToolTpl->template->set('s', 'TABLE_TOOLS_FILTER',			trim(implode($seperator, array(
				$sOnlyActiveOutput, $sFilterOutput, $sSearchOutput, 
			)) , " |"));
			$oToolTpl->template->set('s', 'TABLE_TOOLS_SEARCH',			$sSearchOutput);
			$oToolTpl->template->set('s', 'TABLE_TOOLS_COLUMNFILTER',	$sFilterOutput);
			$oToolTpl->template->set('s', 'TABLE_TOOLS_ONLYACTIVE',		$sOnlyActiveOutput);
			
			$oToolTpl->template->set('s', 'TABLE_TOOLS_ACTIONS',		$sTableActions);
			
			$oToolTpl->template->set('s', 'TABLE_TOOLS_SORT',			trim(implode(" || ", array(
				$sSortOptionsOutput,
			)) , " |"));
			
			$oToolTpl->template->set('s', 'NOTIFICATION',				""); //$sToolNotificationOutput);
			
			$sHTML .= $oToolTpl->template->generate( $sTemplate,	true );
		}
		if ($bReturn) {
			return ($sHTML);
		}
		echo ($sHTML);
		return ($this);
	}
	
	public function generateTableToolsSort ( $sOrderField = "", $sSortOrder = "asc", $bReturn = TRUE ) {
		$sHTML	= "";
		$aHTMLTableColumns = $this->getTableConfig(TRUE)->oColumnSets;
		$sSortOptionsOutput	= i18n("sort").' '. '<span class="input">'. 
			'<select id="eSortOrder" name="sortOrder" class="text_medium" onchange="/*artSort(this)*/">'. 
				'<option value="asc"'.  ( ($sSortOrder == 'asc') ? ' selected="selected"' : '' ) .'>'.i18n("ascending"). '</option>'. 
				'<option value="desc"'. ( ($sSortOrder != 'asc') ? ' selected="selected"' : '' ) .'>'.i18n("descending").'</option>'. 
			'</select> '.i18n("by"). ' '. 
			'<select id="eOrderField" name="orderField" class="text_medium" onchange="/*artSort(this)*/">';
			foreach ((array)$aHTMLTableColumns as $key => $aColumn) {
				if ( !isset($aColumn['sort']) || ($aColumn['sort'] === TRUE) ) {
					$sSortOptionsOutput	.= '<option value="'.$aColumn['field'].'"'.
						( ($sOrderField == $aColumn['field']) ? ' selected="selected"' : '' ) .
					'>'.$aColumn['title'].'</option>';
				}
			}
			$sSortOptionsOutput	.= '</select>'. 
		'</span>';
		$sHTML .= $sSortOptionsOutput;
		if ($bReturn) {
			return ($sHTML);
		}
		echo ($sHTML);
		return ($this);
	}
	
	public function generateTableToolsPageCount ( $sRowsPerPage = "", $aOptionsRowsPerPage = array(), $bReturn = TRUE ) {
		$sHTML	= "";
		$sRowsPerPageOutput	= i18n("show").' '. '<span class="input">'. 
			'<select id="eRowsPerPage" name="pageCount" class="text_medium text_right" onchange="/*artSort(this)*/">';
			foreach ((array)$aOptionsRowsPerPage as $key => $value) {
				$sRowsPerPageOutput	.= '<option style="text-align: right;" value="'.$value.'"'.
					( ($sRowsPerPage == $value) ? ' selected="selected"' : '' ) .
				'>'.$value.'</option>';
			}
			$sRowsPerPageOutput	.= '</select>'. i18n("rows per page") .
		'</span>';
		$sHTML .= $sRowsPerPageOutput;
		if ($bReturn) {
			return ($sHTML);
		}
		echo ($sHTML);
		return ($this);
	}
	
	public function generateTableToolsOnlyActive ( $sOnlyActive = "", $bReturn = TRUE ) {
		$sHTML	= "";
		$sOnlyActiveOutput	= i18n("show only active forms").' '. '<span class="input">'. 
			'<input type="checkbox" value="true" id="eOnlyActive" name="onlyActive" class="text_medium" onchange="/*artSort(this)*/"'.( ($sOnlyActive == 'true') ? ' checked="checked"' : '' ).'>';
		'</span>';
		$sHTML .= $sOnlyActiveOutput;
		if ($bReturn) {
			return ($sHTML);
		}
		echo ($sHTML);
		return ($this);
	}
	
	public function generateTableToolsPagination ( $sPageStart = "", $sRowsPerPage = 15, $bReturn = TRUE ) {
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
	
	public function generateTableToolsFilter ( $sFilterCol = "", $sFilterData = "", $bReturn = TRUE ) {
		$sHTML	= "";
		$aHTMLTableColumns = $this->getTableConfig(TRUE)->oColumnSets;
		$sSearchOutput	= i18n("filter column") . ': ' . '<span class="input">'.
			'<select id="eFilterCol" name="filterCol" class="text_medium" onchange="/*artSort(this)*/">'.
				'<option value="*"'.( ($sFilterCol == '*') ? ' selected="selected"' : '' ).'>' . i18n("any") . "</option>";
				foreach ((array)$aHTMLTableColumns as $key => $aColumn) {
					if ( !isset($aColumn['filter']) || ($aColumn['filter'] === TRUE) ) {
						$sSearchOutput	.= '<option value="'.$aColumn['field'].'"'.
							( ($sFilterCol == $aColumn['field']) ? ' selected="selected"' : '' ) .
						'>'.$aColumn['title'].'</option>';
					}
				}
				$sSearchOutput	.= '</select>'. 
			'</span>' . ' &nbsp; ';
			$sSearchOutput	.= i18n("query") . ': ' . '<span class="input">'.
				'<input type="text" id="eFilterData" name="filterData" class="text_medium" onchange="/*artSort(this)*/" value="'.$sFilterData.'">'.
			'</span>' . 
			//(' ' . Contenido_Plugin_GUI::submitImage('filter', 'images/but_preview.gif') ) . ( (empty($sFilterData)) ? "" :
			//(' ' . Contenido_Plugin_GUI::submitImage('filtercancel', 'images/but_cancel.gif') ) ) . 
		"";
		$sHTML .= $sSearchOutput;
		if ($bReturn) {
			return ($sHTML);
		}
		echo ($sHTML);
		return ($this);
	}
	
	public function generateTableToolsNotification ( $sMessage = "", $bReturn = TRUE ) {
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
	
	public function generateTableToolsActions ( $bReturn = TRUE ) {
		$sHTML	= "";
		$sTableActions					= '<ul class="tableActionList">'.
			( ( $this->_con('perm')->have_perm_area_action($this->_con('plugin_name'), "createform") ) ? '<li class="tableActionItem">'. sprintf(
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
		/*( ( $perm->have_perm_area_action($plugin_name, "reportoverview") ) ? '<li class="tableActionItem">'. sprintf(
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
		$sHTML .= $sTableActions;

		if ($bReturn) {
			return ($sHTML);
		}
		echo ($sHTML);
		return ($this);
	}
	
	public function generateTableTopPanel ( $bReturn = TRUE ) {
		$sHTML	= "";
		$sHTML .= $this->generateTableTools( $this->getTableConfig(TRUE)->oTemplates["top"] );
		if ($bReturn) {
			return ($sHTML);
		}
		echo ($sHTML);
		return ($this);
	}
	
	public function generateTableBottomPanel ( $bReturn = TRUE ) {
		$sHTML	= "";
		$sHTML .= $this->generateTableTools( $this->getTableConfig(TRUE)->oTemplates["bottom"] );
		if ($bReturn) {
			return ($sHTML);
		}
		echo ($sHTML);
		return ($this);
	}
	
	public function generateTableJS ( $sJSTemplate = "", $bReturn = TRUE ) {
		$this->getTpl("tableJS", TRUE);
		$this->setTplVars("tableJS", array(
			"static" => array(
				"TABLE_CONFIG_JSON" => json_encode($this->getTableConfig(TRUE)),
			)
		));
		$sJS = $this->getTpl("tableJS")->template->generate($sJSTemplate, TRUE);
		if ($bReturn) {
			return ($sJS);
		}
		echo ($sJS);
		return ($this);
	}
	
	public function generate ( $bReturn = TRUE ) {
		$options = (object)$this->getTableConfig();
		$sHTML =
			(($options->showTopPanel) ? $this->generateTableTopPanel() : ""). 
			$this->generateTable() . 
			(($options->showBottomPanel) ? $this->generateTableBottomPanel() : "") . 
			(($options->generateJS) ? $this->generateTableJS($this->getTableConfig(TRUE)->oTemplates["javascript"]) : "");
		if ($bReturn) {
			return ($sHTML);
		}
		echo ($sHTML);
		return ($this);
	}
	
	/*****************************************************
	 ** table private/internal methods
	 ****************************************************/
	
	/**
	 * retrieve db table's column definitions
	 * @return	Contenido_Plugin_GUI_Table
	 */
	protected function _getDbTableColumns ( $full = FALSE, $prependTableName = FALSE ) {
		$sTableName	= $this->getTableName();
		$aFields	= array();
		if (!empty($sTableName)) {
			$oDB = $this->getDb();
			$sQuery = "SHOW FULL COLUMNS FROM `".Contenido_Security::escapeDB($sTableName, $oDB)."`;";
			$oDB->query($sQuery);
			while ($oDB->next_record()) {
				if ( $full ) {
					$aFields[$oDB->f('Field')] = array(
						"Field"			=> (($prependTableName) ? $sTableName."." : "" ) .$oDB->f('Field'),
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
	 * @return	Contenido_Plugin_GUI_Table
	 */
	protected function _buildTableQuery () {
		$this->_parseToolsParams();
		$sTableName	= $this->getTableName();
		$sQuery		= "";
		
		
		if (!empty($sTableName)) {
			$oDB		= $this->getDb();
			$sQuery		= "SELECT * FROM `".Contenido_Security::escapeDB($sTableName, $oDB)."` ";
			$oTools		= $this->getTableTools(TRUE);
			$aFields	= $this->_getDbTableColumns(TRUE);
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
	 * parse request's table tools parameters
	 * 
	 * @param	ARRAY	$aParams
	 * @return	Contenido_Plugin_GUI_Table
	 */
	protected function _parseToolsParams ( $aParams = NULL ) {
		$aParams	= ($aParams) ? $aParams : $this->_getAllParams();
		$aTools		= (array)$this->getTableTools();
		foreach ($aTools as $key => $value) {
			if ( array_key_exists($key, $aParams) && !empty($aParams[$key]) ) {
				$aTools[$key] = trim($aParams[$key]);
			} else if ( array_key_exists($key, $aParams) ) {
				$aTools[$key] = NULL;
			}
		}
		$this->setTableTools((object)$aTools);
		return $this;
	}
	
	/**
	 * get panel's element id
	 * @return	STRING
	 */
	protected function getId () {
		if (empty($this->_sTableId)) {
			$this->_setTableId();
		}
		return $this->_sTableId;
	}
	
	/**
	 * set table's element id
	 * @param	STRING	$sTableId
	 * @return	Contenido_Plugin_GUI_Table
	 */
	protected function _setTableId ( $sTableId = "" ) {
		if (empty($sTableId)) {
			$sTableId = uniqid($this->_sTableIdPrefix, TRUE);
		}
		$this->_sTableId = str_replace(".", "_", $sTableId);
		return $this;
	}
	
	/**
	 * get panel hash id
	 * @return	STRING
	 */
	protected function getHash () {
		if (empty($this->_sTableHashId)) {
			
			$this->_setTableHashId();
		}
		return $this->_sTableHashId;
	}
	
	/**
	 * create or set panel hash id
	 * @param	STRING	$sTableHashId
	 * @return	self
	 */
	protected function _setTableHashId ( $sTableHashId = "" ) {
		if ( empty($sTableHashId) && empty($this->_sTableHashId) ) {
			// create new panel hash id
			$sHash = md5( uniqid(NULL, FALSE)."_".uniqid(NULL, TRUE) );
		} else if ( empty($sTableHashId) && !empty($this->_sTableHashId) ) {
			// apply previously set hash id
			$sHash = $this->_sTableHashId;
		} else {
			// set given hash id
			$sHash = $sTableHashId;
		}
		$this->_sTableHashId = $sHash;
		$this->_oTableConfig["hash"]  = $this->_sTableHashId;
		//$this->getSession();
		return $this;
	}
	
	/**
	 * get session
	 * @return	Zend_Session_Namespace
	 */
	public function getSession () {
		if ($this->_oTableSession === NULL) {
			$this->_setTableSession(NULL, FALSE);
		}
		return $this->_oTableSession;
	}
	
	/**
	 * create panel session object
	 * @param	STRING	$sTableHashId
	 * @param	BOOLEAN	$bReset
	 * @return	void
	 */
	protected function _setTableSession ( $sTableHashId = "", $bReset = TRUE ) {
		if (empty($sTableHashId)) {
			$sTableHashId = $this->getHash();
		}
		if ( (($this->_oTableSession === NULL) ) || $bReset) {
			$sNamespace = $this->_sTableIdPrefix . $sTableHashId;
			if ( !isset($_SESSION[$sNamespace]) ) {
				$_SESSION[$sNamespace] = array();
			}
			$this->_oTableSession = $_SESSION[$sNamespace]; //new Zend_Session_Namespace($sNamespace);
			// $this->_oTableSession->setExpirationSeconds(300, 'accept_request');
			if ( !$this->_oTableSession["initialized"] ) {
				$this->_oTableSession["sId"]				= $this->getId();
				$this->_oTableSession["iRequestCount"]		= 0;
				$this->_oTableSession["accept_request"]		= TRUE;
				$this->_oTableSession["initialized"]		= TRUE;
			}
			if (!$bReset) {
				//$this->_oTableSession["iRequestCount"]++;
			}
		} 
		$this->_oTableSession["iRequestCount"]++;
		$this->_oTableSession["aTableConfig"]		= $this->getTableConfig();
		$this->_oTableSession["aTableTools"]		= $this->getTableTools();
			
		$_SESSION[$sNamespace] = $this->_oTableSession;
		
		return $this;
	}
	
	/**
	 * @return	ARRAY	$_aColumns
	 */
	public function getColumns () {
		return (array)$this->_aColumns;
	}

	/**
	 * @param	ARRAY	$_aColumns
	 * @return	Contenido_Plugin_GUI_Table
	 */
	public function setColumns ( $_aColumns = array() ) {
		$this->_aColumns = $_aColumns;
		$this->_oTableConfig["oColumnSets"] =& $this->_aColumns;
		$this->_setTableSession(NULL, FALSE);
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
	 * @return	Contenido_Plugin_GUI_Table
	 */
	public function setFooter ( $_aFooterCells = array() ) {
		$this->_aFooterCells = $_aFooterCells;
		$this->_oTableConfig["oFooterCells"] =& $this->_aFooterCells;
		$this->_setTableSession(NULL, FALSE);
		return $this;
	}

	/**
	 * @return	ARRAY	$aRows
	 */
	protected function _loadDbData  ( ) {
		$aRows			= array();
		$oDB			= $this->getDb();
		$sSQLGetRows	= $this->getQuery();
		if ($sSQLGetRows) {
			$oDB->query($sSQLGetRows);
			while ( $oDB->next_record() ) {
				if ( $asObject ) {
					array_push($aRows, (object)$oDB->Record);
				} else {
					array_push($aRows, $oDB->Record);
				}
			}
		}
		return ($aRows);
	}

	/**
	 * @return	ARRAY	$_aRows
	 */
	public function getRows ( $forceDbReload = FALSE ) {
		if ( !is_array($this->_aRows) || (count($this->_aRows) == 0) || $forceDbReload ) {
			$tableName = trim($this->getTableName());
			if ( !empty($tableName) ) {
				$aRows = $this->_loadDbData();
				$this->setRows($aRows);
			}
		}
		return (array)$this->_aRows;
	}

	/**
	 * @param	ARRAY	$_aRows
	 * @return	Contenido_Plugin_GUI_Table
	 */
	public function setRows ( $_aRows = array() ) {
		$this->_aRows = $_aRows;
		$this->_oTableConfig["data"] =& $this->_aRows;
		$this->_setTableSession(NULL, FALSE);
		return $this;
	}

	/**
	 * @return	STRING	$_sQuery
	 */
	public function getQuery () {
		$tableName = trim($this->getTableName());
		if ( !empty( $tableName ) ) {
			$this->_buildTableQuery();
		} else {
			return (FALSE);
		}
		return $this->_sQuery;
	}

	/**
	 * @param	STRING	$_sQuery
	 * @return	Contenido_Plugin_GUI_Table
	 */
	public function setQuery ( $_sQuery ) {
		$this->_sQuery = $_sQuery;
		$this->_setTableSession(NULL, FALSE);
		return $this;
	}

	/**
	 * @return	DB_Contenido	$_oTable
	 */
	public function _getTable () {
		if ( !($this->_oTable instanceof DB_Contenido) ) {
			$this->_setTable();
		}
		return $this->_oTable;
	}

	/**
	 * @param	DB_Contenido	$_oTable
	 * @return	Contenido_Plugin_GUI_Table
	 */
	public function _setTable ($_oTable) {
		$this->_oTable = $this->getDb();
		$this->_setTableSession(NULL, FALSE);
		return $this;
	}

	/**
	 * @return	Exception	$_oTableDataError
	 */
	public function _getTableDataError () {
		return $this->_oTableDataError;
	}

	/**
	 * @param	Exception	$_oTableDataError
	 * @return	Contenido_Plugin_GUI_Table
	 */
	public function setTableDataError ( $_oTableDataError ) {
		$this->_oTableDataError = $_oTableDataError;
		$this->_setTableSession(NULL, FALSE);
		return $this;
	}

	/**
	 * @return the $_sTableName
	 */
	public function getTableName () {
		return $this->_sTableName;
	}

	/**
	 * @param STRING $_sTableName
	 * @return	Contenido_Plugin_GUI_Table
	 */
	public function setTableName ( $_sTableName ) {
		$this->_sTableName = $_sTableName;
		$this->_setTableSession(NULL, FALSE);
		return $this;
	}

	/**
	 * @return	ARRAY	$_oTableConfig
	 */
	public function getTableConfig ( $asObject = FALSE ) {
		return ($asObject) ? (object)$this->_oTableConfig : (array)$this->_oTableConfig;
	}

	/**
	 * @param	ARRAY	$_oTableConfig
	 * @return	Contenido_Plugin_GUI_Table
	 */
	public function setTableConfig ( $_oTableConfig ) {
		$this->_oTableConfig = $_oTableConfig;
		$this->_setTableSession(NULL, FALSE);
		return $this;
	}

	/**
	 * @return	OBJECT	$_oTableConfig["oRowActions"]
	 */
	public function getTableActions ( $asObject = FALSE ) {
		return ($asObject) ? (object)$this->_oTableConfig["oTableActions"] : (array)$this->_oTableConfig["oTableActions"] ;
	}

	/**
	 * @param	ARRAY	$_oTableActions
	 * @return	Contenido_Plugin_GUI_Table
	 */
	public function setTableActions ( $_oTableActions ) {
		$this->_oTableConfig["oTableActions"] = $_oTableActions;
		$this->_setTableSession(NULL, FALSE);
		return $this;
	}

	/**
	 * @return	OBJECT	$_oTableTools
	 */
	public function getTableTemplates ( $asObject = FALSE ) {
		return ($asObject) ? (object)$this->_oTableConfig["oTemplates"] : (array)$this->_oTableConfig["oTemplates"];
	}

	/**
	 * @param	ARRAY	$_oTableTools
	 * @return	Contenido_Plugin_GUI_Table
	 */
	public function setTableTemplates ( $_oTableTools ) {
		$this->_oTableConfig["oTemplates"] = $_oTableTools;
		$this->_setTableSession(NULL, FALSE);
		return $this;
	}

	/**
	 * @return	OBJECT	$_oTableTools
	 */
	public function getTableTools ( $asObject = FALSE ) {
		return ($asObject) ? (object)$this->_oTableTools : (array)$this->_oTableTools;
	}

	/**
	 * @param	ARRAY	$_oTableTools
	 * @return	Contenido_Plugin_GUI_Table
	 */
	public function setTableTools ( $_oTableTools ) {
		$this->_oTableTools = $_oTableTools;
		$this->_oTableConfig["oTableTools"] =& $this->_oTableTools;
		$this->_setTableSession(NULL, FALSE);
		return $this;
	}

	/**
	 * @return	STRING	$_oTableToolsURL
	 */
	public function getTableToolsURL () {
		$cfg = $this->getTableConfig();
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
	 * @return	Contenido_Plugin_GUI_Table
	 */
	public function setNoRenderActions ( $aNoRenderActions ) {
		$this->aNoRenderActions = $aNoRenderActions;
		$this->_setTableSession(NULL, FALSE);
		return $this;
	}

	/**
	 * @return	OBJECT	$_oTableConfig["oRowActions"]
	 */
	public function getRowActions ( $asObject = FALSE ) {
		return ($asObject) ? (object)$this->_oTableConfig["oRowActions"] : (array)$this->_oTableConfig["oRowActions"];
	}

	/**
	 * @param	ARRAY	$_oRowActions
	 * @return	Contenido_Plugin_GUI_Table
	 */
	public function setRowActions ( $_oRowActions ) {
		$this->_oTableConfig["oRowActions"] = $_oRowActions;
		$this->_setTableSession(NULL, FALSE);
		return $this;
	}

}