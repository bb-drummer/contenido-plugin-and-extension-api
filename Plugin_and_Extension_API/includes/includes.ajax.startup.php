<?php
// catch if idartlang and idclient are set at minimum
if ( empty($_REQUEST['idartlang']) || empty($_REQUEST['idclient']) || 
	 ((int)$_REQUEST['idartlang'] == 0) || ((int)$_REQUEST['idclient'] == 0) ) {
	header ("Error: 500 Internal Server Error", true, 500);
	die ('invalid parameters given or parameters missing');
}

// Contenido startup process
if (!defined("CON_FRAMEWORK")) {
    define("CON_FRAMEWORK", true);
}
if (isset($_REQUEST['cfg']) || isset($_REQUEST['contenido_path'])) {
    die ('Illegal call!');
}
include_once ('../../classes/class.security.php');
require_once ('../../startup.php');
require_once ('../src/inc/functions.phpctions.php');

// do ajax stuff...
