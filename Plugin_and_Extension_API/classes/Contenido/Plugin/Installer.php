<?php
if(!defined('CON_FRAMEWORK')) {
	die('Illegal call');
}
cInclude('includes', 'Contenido/Plugin/Abstract.php');

class Contenido_Plugin_Installer extends Contenido_Plugin_Abstract {
	
	var $installerConfig = null;
	
	var $data = array();
	
	public function __construct($options = array()) {
		if ( $options instanceof Contenido_Plugin_Installer_Config ) {
			$optionsArray = $options->toArray();
			$optionsObject = $options;
		}
		if ( is_array($options) ) {
			$optionsArray = $options;
			$optionsObject = new Contenido_Plugin_Installer_Config($options);
		}
		parent::__construct($optionsArray);
		$this->installerConfig = $optionsObject;
		return ($this);
	}
	
	/**
	 * get singelton instance object
	 * 
	 * @param	array|stdClass	$config
	 * @return	Contenido_Plugin_Installer
	 */
	public static function getInstance ( $options = array() ) {
		if ( self::$_instance === NULL ) {
			self::$_instance = new self( $options );
		}
		return self::$_instance;
	}

	public function installPlugin () {
		// ...do stuff
		page_open(array('sess' => 'Contenido_Session', 'auth' => 'Contenido_Challenge_Crypt_Auth', 'perm' => 'Contenido_Perm'));
		
		$this->checkPluginTable();
		
		$param = $this->installerConfig;
		$this->installSql($param);
		
		page_close();
		
		return ($this);
	}
	
	public function installSql ($param) {
		if ($installsql = file_get_contents($param["sqlfiles"]["install"])) {
		    // get info from sql file
		    if (preg_match ("/####(.*)####/i", $installsql, $pinfo)) {
		        $pinfo = explode (";", $pinfo[1]);
		        // take some nice names easier to work with...
		        $pname       = $pinfo[0];
		        $pversion    = $pinfo[1];
		        $pauthor     = $pinfo[2];
		        $pinternalid = $pinfo[3];
		
		        unset($pinfo);
		        // first show info
		        $this->data['content'] .= "<div class='col1'>Plugin Name:</div><div class='col2'>" . $pname . "</div><br class='clear' />\n";
		        $this->data['content'] .= "<div class='col1'>Plugin Version:</div><div class='col2'>" . $pversion . "</div><br class='clear' />\n";
		        $this->data['content'] .= "<div class='col1'>Author:</div><div class='col2'>" . $pauthor . "</div><br class='clear' />\n";
		        $this->data['content'] .= "<div class='col1'>Internal ID:</div><div class='col2'>" . $pinternalid . "</div><br class='clear' />\n";
		        $this->data['content'] .= "<br />\n";
		
		        // the user don't need this info...
		        $installsql = preg_replace ("/####(.*)####/i", "", $installsql);
		
		        $pstatus = true;
		    } else {
		        $this->data['content'] .= "Info missing. First line of install.sql should include following line:<br />";
		        $this->data['content'] .= "<strong>####NAME;VERSION;AUTHOR;INTERNAL_ID####</strong><br />";
		        $this->data['content'] .= "No further action takes place<br />";
		
		        $pstatus = false;
		    }
		
		    // check if idinternal is allready available in table
		    $sql = "SELECT * FROM " . $cfg["tab"]["plugins"] . " WHERE idinternal='" . $pinternalid . "';";
		    $db->query($sql);
		    if ($db->next_record()) {
		        $mode     = "update";
		        $message .= "Plugin with this internal id allready exists in table.<br />\n";
		        if ($pversion == $db->f('version')) {
		            $message .= "This version is allready installed.<br />\n";
		            $mode     = "uninstall";
		        } else {
		            $message .= "Switching to upgrade mode.<br />\n";
		        }
		        $pluginid = $db->f('idplugin');
		    } else {
		        $mode     = "install";
		        $message .= "No plugin with this internal id exists in table.<br />\n";
		        $pluginid = false;
		    }
		
		    if (!$install && !$uninstall) {
		        $this->data['content'] .= "<br />" . $message;
		    }
		
		    if (!$install && $mode == "update") {
		        $this->data['content'] .= "<br /><a class=\"submit\" href=\"$PHP_SELF?install=1&amp;contenido=$contenido\" title=\"Update plugin\">Update $pname $pversion</a><br />\n";
		    }
		
		    if (!$install && $mode == "install") {
		        $this->data['content'] .= "<br /><a class=\"submit\" href=\"$PHP_SELF?install=1&amp;contenido=$contenido\" title=\"Install plugin\">Install $pname $pversion</a><br />\n";
		    }
		
		    if (!$uninstall && $mode == "uninstall") {
		        $this->data['content'] .= "<br /><a class=\"submit\" href=\"$PHP_SELF?uninstall=1&amp;contenido=$contenido\" title=\"UnInstall plugin\">UnInstall $pname $pversion</a><br />\n";
		        $this->data['content'] .= "<br /><br /><strong>Note:</strong><br />";
		        $this->data['content'] .= "The UnInstaller will only remove plugin related entries from database (plugins table). Any done changes on directories or files must be reset manually.<br />";
		    }
		
		    if ($uninstall && $pluginid) {
		        $sql = "SELECT uninstall FROM " . $cfg["tab"]["plugins"] . " WHERE idplugin='" . $pluginid . "'";
		        $this->msg($sql);
		        $db->query($sql);
		        $db->next_record();
		
		        $uninstallsql = $db->f('uninstall');
		        $sSqlData     = $this->remove_remarks($uninstallsql);
		        $aSqlPieces   = $this->split_sql_file($sSqlData, ';');
		
		        $this->msg(count($aSqlPieces)." queries", "Executing:");
		        foreach ($aSqlPieces as $sqlinit) {
		            $db->query($sqlinit);
		            $this->msg($sqlinit);
		        }
		
		        $this->uninstall();
		
		        $this->data['content'] .= "<br /><strong>Uninstall complete.</strong><br />\n";
		    }
		
		    if ($pstatus && $install) {
		        if ($mode == "install") { // insert all data from install.sql
		            $pluginid = $db->nextid($cfg["tab"]["plugins"]); // get next free id using phplib method
		
		            $PID = 100 + $pluginid; // generate !PID! replacement
		            $replace = array('!PREFIX!' => $cfg['sql']['sqlprefix'], '!PID!' => $PID);
		
		            $installsql = strtr($installsql, $replace);
		
		            $sql = "INSERT INTO " . $cfg["tab"]["plugins"] . " (idplugin,name,`version`,author,idinternal,`status`,`date`) VALUES ('" . $pluginid . "','" . $pname . "','" . $pversion . "','" . $pauthor . "','" . $pinternalid . "','0','" . date("Y-m-d H:i:s") . "');";
		            $uninstallsql = "DELETE FROM " . $cfg["tab"]["plugins"] . " WHERE idplugin='" . $pluginid . "';\r\n";
		            $this->msg($sql, "Insert statement for plugin: ");
		            $db->query($sql);
		
		            msg ($installsql, "Install query:");
		
		            $sSqlData   = $this->remove_remarks($installsql);
		            $aSqlPieces = $this->split_sql_file($sSqlData, ';');
		            $this->msg(count($aSqlPieces) . " queries", "Executing:");
		            foreach ($aSqlPieces as $sqlinit) {
		                // $sqlinit = strtr($sqlinit, $replace);
		                // create uninstall.sql for each insert entry
		                if (preg_match("/INSERT\s+INTO\s+(.*)\s+VALUES\s*\([�\"'\s]*(\d+)/i", $sqlinit, $tmpsql)) {
		                    $tmpidname = $db->metadata(trim(str_replace("`", "", $tmpsql[1])));
		                    $tmpidname = $tmpidname[0]['name'];
		                    $uninstallsql = "DELETE FROM " . trim($tmpsql[1]) . " WHERE " . $tmpidname . "='" . trim($tmpsql[2]) . "';\r\n" . $uninstallsql;
		                }
		
		                $db->query($sqlinit);
		                $this->msg($sqlinit);
		            }
		
		            if ($uninstallsqlfile = file_get_contents($param["sqlfiles"]["uninstall"])) {
		                $uninstallsqlfile = $this->remove_remarks($uninstallsqlfile); // remove all comments
		
		                $uninstallsql .= strtr($uninstallsqlfile, $replace); // add to generated sql
		                $this->data['content'] .= "I found uninstall.sql in " . dirname(__FILE__) . "<br />Statements added to uninstall query.<br />\n";
		            }
		
		            msg ($uninstallsql, "Uninstall query:");
		
		            $sql = "UPDATE " . $cfg["tab"]["plugins"] . " SET install=0x" . bin2hex($installsql) . ", uninstall=0x" . bin2hex($uninstallsql) . " WHERE (idplugin='" . $pluginid . "');";
		            $this->msg($sql, "un/install statements stored");
		            $db->query($sql);
		
		            $this->install();
		
		            $this->data['content'] .= "<br /><strong>Install complete.</strong><br />\n";
		        }
		
		        if ($mode == "update") {
		            $sql  = "UPDATE " . $cfg["tab"]["plugins"] . " SET\n";
		            $sql .= " version = '" . $pversion . "'\n";
		            $sql .= "WHERE (idplugin='" . $pluginid . "');";
		            $this->msg($sql, "Store new plugin version: ");
		            $db->query($sql);
		            if ($updatesqlfile = @file_get_contents($param["sqlfiles"]["update"])) {
		                $sql = "SELECT uninstall FROM " . $cfg["tab"]["plugins"] . " WHERE idplugin='" . $pluginid . "'";
		                $this->msg($sql, "Getting stored uninstall statements: ");
		                $db->query($sql);
		                $db->next_record();
		
		                $uninstallsql  = $db->f('uninstall');
		                $updatesqlfile = $this->remove_remarks($updatesqlfile); // remove all comments
		
		                $this->data['content'] .= "I found update.sql in " . dirname(__FILE__) . "<br />\n";
		
		                $PID = 100 + $pluginid; // generate !PID! replacement
		                $replace = array('!PREFIX!' => $cfg['sql']['sqlprefix'], '!PID!' => $PID);
		                $updatesql .= strtr($updatesqlfile, $replace); // add to generated sql
		
		                $aSqlPieces = $this->split_sql_file($updatesql, ';');
		                $this->msg(count($aSqlPieces) . " queries", "Executing:");
		                foreach ($aSqlPieces as $sqlinit) {
		                    // $sqlinit = strtr($sqlinit, $replace);
		                    // create uninstall.sql for each insert entry
		                    if (preg_match("/INSERT\s+INTO\s+(.*)\s+VALUES\s*\([�\"'\s]*(\d+)/i", $sqlinit, $tmpsql)) {
		                        $tmpidname    = $db->metadata(trim(str_replace("`", "", $tmpsql[1])));
		                        $tmpidname    = $tmpidname[0]['name'];
		                        $uninstallsql = "DELETE FROM " . trim($tmpsql[1]) . " WHERE " . $tmpidname . "='" . trim($tmpsql[2]) . "';\r\n" . $uninstallsql;
		                    } else if (preg_match("/REPLACE \s+INTO\s+(.*)\s+VALUES\s*\([�\"'\s]*(\d+)/i", $sqlinit, $tmpsql)) {
		                        $tmpidname    = $db->metadata(trim(str_replace("`", "", $tmpsql[1])));
		                        $tmpidname    = $tmpidname[0]['name'];
		                        $uninstallsql = "DELETE FROM " . trim($tmpsql[1]) . " WHERE " . $tmpidname . "='" . trim($tmpsql[2]) . "';\r\n" . $uninstallsql;
		                    }
		
		                    $db->query($sqlinit);
		                    $this->msg($sqlinit);
		                }
		                $sql = "UPDATE " . $cfg["tab"]["plugins"] . " SET uninstall = 0x" . bin2hex($uninstallsql) . " WHERE (idplugin='" . $pluginid . "');";
		                $this->msg($sql, "New uninstall statements stored: ");
		                $db->query($sql);
		            }
		
		            $this->upgrade();
		
		            $this->data['content'] .= "<br /><strong>Update complete.</strong><br />\n";
		        }
		
		        // con_sequence update
		        $this->updateSequence();
		    }
		} else {
		    $this->data['content'] .= "Sorry i found no install.sql in " . dirname(__FILE__) . "<br />\n";
		}

	}
	
	public function checkPluginTable ($bCheckTableStatus = true) {
		if ($bCheckTableStatus) {
		    $aRequiredFields = array(
		        "idplugin", "name", "version", "author", "idinternal", "url",
		        "status", "description", "install", "uninstall", "date"
		    );
			$cfg = $this->getCfg();
			$db = $this->getDb();
			// $sRequiredTable = "DROP TABLE " . $cfg['tab']['plugins'] . ";
		    $sRequiredTable = "RENAME TABLE " . $cfg['tab']['plugins'] . " TO " . $cfg['tab']['plugins'] . "_" . date('Ymd') . ";
		                      CREATE TABLE " . $cfg['tab']['plugins'] . " (
		                          idplugin INT(10) NOT NULL default '0',
		                          name VARCHAR(60) default NULL,
		                          version VARCHAR(10) NOT NULL default '0',
		                          author VARCHAR(60) default NULL,
		                          idinternal VARCHAR(32) NOT NULL default '0',
		                          url TEXT,
		                          status INT(10) NOT NULL default '0',
		                          description TEXT,
		                          install TEXT,
		                          uninstall TEXT,
		                          date DATETIME NOT NULL default '0000-00-00 00:00:00',
		                          PRIMARY KEY (idplugin)
		                      ) TYPE=MyISAM;";
		    // now we check if the plugin table has the right format...
		    $this->msg("Checking status " . $cfg['tab']['plugins']);
		    $aPluginTableMeta = $db->metadata($cfg['tab']['plugins']);
		
		    foreach ($aPluginTableMeta as $key) {
		        if (!in_array($key['name'], $aRequiredFields)) {
		            $this->msg($key['name'] . " (this key can be deleted)", "unused key");
		        } else {
		            $aAvailableKeys[] = $key['name'];
		        }
		        $aFoundKeys[] = $key['name'];
		    }
		    foreach ($aRequiredFields as $key) {
		        if (!in_array($key, $aFoundKeys)) {
		            $this->msg($key . " (this key must be added)", "missing key");
		            $aMissingKeys[] = $key;
		        }
		    }
		    unset ($aFoundKeys, $key);
		    // available elements in table are stored in array -> $aAvailableKeys;
		    // missing elements in table are stored in array -> $aMissingKeys;
		    // this is a possible way to handle new versions of plugin installer
		    // since this is initial release the table will be dropped and recreated
		    // when a missing element is found.
		    if (count($aMissingKeys) > 0) {
		        $sSqlData   = $this->remove_remarks($sRequiredTable);
		        $aSqlPieces = $this->split_sql_file($sSqlData, ';');
		        $this->msg(count($aSqlPieces) . " queries", "Executing:");
		        foreach ($aSqlPieces as $sqlinit) {
		            $db->query($sqlinit);
		            $this->msg($sqlinit);
		        }
		    } else {
		        $this->msg("ok");
		    }
		}
		// con_sequence update
		$this->updateSequence();

	}
	
	public function output ($param) {
		####################################################################################################
		##### Output
		
		echo ('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
		    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
		<html xmlns="http://www.w3.org/1999/xhtml">
		<head>
		    <title>CONTENIDO Plugin Installer</title>
		    <meta http-equiv="expires" content="0" />
		    <meta http-equiv="cache-control" content="no-cache" />
		    <meta http-equiv="pragma" content="no-cache" />
		    <link rel="stylesheet" type="text/css" href="{$contenido_html}styles/contenido.css" />
		    <style type="text/css"><!--
		    body {font-family:verdana; font-size:12px;}
		    #wrap {width:750px; margin:50px auto; border:1px solid #b3b3b3;}
		    #head {width:100%; border-bottom:1px solid black;}
		    #content_top {background-color:#e2e2e2; font-weight:bold; padding:5px 0 5px 10px; border-bottom:1px solid #b3b3b3;}
		    #content {padding:10px;}
		    a:link, a:visited, a:hover {color:#0060b1; font-size:12px;}
		    a:hover {text-decoration:underline;}
		    a.submit, a.submit:hover {display:block; height:18px; padding-left:20px;}
		    a.submit {background:transparent url({$contenido_html}images/submit.gif) no-repeat;}
		    a.submit:hover {background:transparent url({$contenido_html}images/submit_hover.gif) no-repeat;}
		    .col1 {width:10em; float:left; padding-bottom:0.3em;}
		    .col2 {width:auto; float:left; padding-bottom:0.3em;}
		    .clear {clear:both; font-size:0px; line-height:0px; }
		    --></style>
		</head>
		<body>
		
		<div id="wrap">
		    <div id="head">
		      <a id="head_logo" href="{$contenido_html}?contenido=$contenido" title="Switch to Contenido backend">
		        <img src="{$contenido_html}images/conlogo.gif" alt="Contenido Logo" /></a>
		    </div>
		    <br class="clear" />
		
		    <div id="content_top">
		        {$this->data[\'top\']}
		    </div>
		
		    <div id="content">
		        <form name="frmPluginInstall" id="frmPluginInstall" method="post" action="$PHP_SELF">
		        <input type="hidden" name="contenido" value="$contenido" />
		        {$this->data[\'content\']}
		        {$this->data[\'bottom\']}
		        </form>
		        {$this->data[\'body_bottom\']}
		    </div>
		</div>
		
		</body>
		</html>');
	}
		
	
	####################################################################################################
	##### Functions
	
	// some functions to work with...
	/**
	 * removes '# blabla...' from the mysql_dump.
	 * This function was originally developed for phpbb 2.01
	 * (C) 2001 The phpBB Group http://www.phpbb.com
	 *
	 * @return string input_without_#
	 */
	public function remove_remarks($sql) {
	    $lines = explode("\n", $sql);
	    // try to keep mem. use down
	    $sql = "";
	
	    $linecount = count($lines);
	    $output = "";
	
	    for ($i = 0; $i < $linecount; $i++) {
	        if (($i != ($linecount - 1)) || (strlen($lines[$i]) > 0)) {
	            $output .= ($lines[$i][0] != "#")  ? $lines[$i]."\n" : "\n";
	
	            // Trading a bit of speed for lower mem. use here.
	            $lines[$i] = "";
	        }
	    }
	    return $output;
	}
	
	/**
	 * Splits sql- statements into handy pieces.
	 * This function was original developed for the phpbb 2.01
	 * (C) 2001 The phpBB Group http://www.phpbb.com
	 *
	 * @return array sql_pieces
	 */
	public function split_sql_file($sql, $delimiter) {
	  // Split up our string into "possible" SQL statements.
	  $tokens = explode($delimiter, $sql);
	  // try to save mem.
	  $sql = "";
	  $output = array();
	  // we don't actually care about the matches preg gives us.
	  $matches = array();
	  // this is faster than calling count($oktens) every time thru the loop.
	  $token_count = count($tokens);
	  for ($i = 0; $i < $token_count; $i++) {
	    // Dont wanna add an empty string as the last thing in the array.
	    if (($i != ($token_count - 1)) || (strlen($tokens[$i] > 0))) {
	      // This is the total number of single quotes in the token.
	      $total_quotes = preg_match_all("/'/", $tokens[$i], $matches);
	      // Counts single quotes that are preceded by an odd number of backslashes,
	      // which means they're escaped quotes.
	      $escaped_quotes = preg_match_all("/(?<!\\\\)(\\\\\\\\)*\\\\'/", $tokens[$i], $matches);
	
	      $unescaped_quotes = $total_quotes - $escaped_quotes;
	      // If the number of unescaped quotes is even, then the delimiter did NOT occur inside a string literal.
	      if (($unescaped_quotes % 2) == 0) {
	        // It's a complete sql statement.
	        $output[] = $tokens[$i];
	        // save memory.
	        $tokens[$i] = "";
	      } else {
	        // incomplete sql statement. keep adding tokens until we have a complete one.
	        // $temp will hold what we have so far.
	        $temp = $tokens[$i] . $delimiter;
	        // save memory..
	        $tokens[$i] = "";
	        // Do we have a complete statement yet?
	        $complete_stmt = false;
	
	        for ($j = $i + 1; (!$complete_stmt && ($j < $token_count)); $j++) {
	          // This is the total number of single quotes in the token.
	          $total_quotes = preg_match_all("/'/", $tokens[$j], $matches);
	          // Counts single quotes that are preceded by an odd number of backslashes,
	          // which means theyre escaped quotes.
	          $escaped_quotes = preg_match_all("/(?<!\\\\)(\\\\\\\\)*\\\\'/", $tokens[$j], $matches);
	
	          $unescaped_quotes = $total_quotes - $escaped_quotes;
	
	          if (($unescaped_quotes % 2) == 1) {
	            // odd number of unescaped quotes. In combination with the previous incomplete
	            // statement(s), we now have a complete statement. (2 odds always make an even)
	            $output[] = $temp . $tokens[$j];
	            // save memory.
	            $tokens[$j] = "";
	            $temp = "";
	            // exit the loop.
	            $complete_stmt = true;
	            // make sure the outer loop continues at the right point.
	            $i = $j;
	          } else {
	            // even number of unescaped quotes. We still dont have a complete statement.
	            // (1 odd and 1 even always make an odd)
	            $temp .= $tokens[$j] . $delimiter;
	            // save memory.
	            $tokens[$j] = "";
	          }
	        } // for..
	      } // else
	    }
	  }
	  return $output;
	}
	
	
	// simple function to update con_sequence
	public function updateSequence($table = false) {
	    global $db, $cfg;
	    if (!$table) {
	        $sql = "SHOW TABLES";
	        $db->query($sql);
	        while ($db->next_record()) {
	            dbUpdateSequence($cfg['sql']['sqlprefix'] . "_sequence", $db->f(0));
	        }
	    } else {
	        dbUpdateSequence($cfg['sql']['sqlprefix'] . "_sequence", $table);
	    }
	}
	
	
	// read out next free id * deprecated
	public function getSequenceId($table) {
	    global $db2, $cfg;
	    $sql = "SELECT nextid FROM " . $cfg['sql']['sqlprefix'] . "_sequence" . " where seq_name = '$table'";
	    $db2->query($sql);
	    if ($db2->next_record()) {
	        return ($db2->f("nextid") + 1);
	    } else {
	        $this->msg($table, "missing in " . $cfg['sql']['sqlprefix'] . "_sequence");
	        return 0;
	    }
	}
	
	
	// debug functions
	public function msg($value, $info = false) {
	    global $cfg;
	    if (trim($cfg["debug"]["messages"]) == "") $cfg["debug"]["messages"] = "<br /><strong>DEBUG:</strong>";
	    if (!$cfg["debug"]["installer"]) {
	        return;
	    }
	    if ($info) {
	        $cfg["debug"]["messages"] .= "<strong>$info</strong> -> ";
	    }
	    if (is_array($value)) {
	        ob_start();
	        print_r($value);
	        $output = ob_get_contents();
	        ob_end_clean();
	        $cfg["debug"]["messages"] .= "<pre>" . htmlspecialchars($output) . "</pre>";
	    } else {
	        $cfg["debug"]["messages"] .= htmlspecialchars($value) . "<br />";
	    }
	}
	
	public function getDebugMsg() {
	    global $cfg;
	    if ($cfg["debug"]["installer"]) {
	        return "<div style=\"font-family: Verdana, Arial, Helvetica, Sans-Serif; font-size: 11px; color: #000000\">"
	            . $cfg["debug"]["messages"]
	            . "</div>";
	    } else {
	        return '';
	    }
	}
	
	
	/**
	 * isWriteable:
	 * Checks if a specific file is writeable. Includes a PHP 4.0.4
	 * workaround where is_writable doesn't return a value of type
	 * boolean. Also clears the stat cache and checks if the file
	 * exists.
	 *
	 * Copied from /setup/lib/functions.filesystem.php
	 *
	 * @param $file string	Path to the file, accepts absolute and relative files
	 * @return boolean true if the file exists and is writeable, false otherwise
	 */
	public function isWriteable($file) {
	    clearstatcache();
	    if (!file_exists($file)) {
	        return false;
	    }
	
	    $bStatus = is_writable($file);
	    /* PHP 4.0.4 workaround */
	    settype($bStatus, "boolean");
	
	    return $bStatus;
	}
	
	
	public function copyFile($source, $destination, $backupName=null) {
	    global $cfg;
	
	    // check source and destination, allow filesystem processes only inside htdocs
	    if (strpos($source, $cfg['path']['frontend']) === false) {
	        return false;
	    } elseif (strpos($destination, $cfg['path']['frontend']) === false) {
	        return false;
	    } elseif (isset($backupName) && strpos($backupName, $cfg['path']['frontend']) === false) {
	        return false;
	    }
	
	    if ($backupName !== null) {
	        if (!rename($destination, $backupName)) {
	            return false;
	        }
	    }
	
	    if (!copy($source, $destination . '.bak')) {
	        return false;
	    }
	
	    return true;
	}
	
	// methods to overwrite...
	public function install () {}
	public function update () {}
	public function uninstall () {}
	
}