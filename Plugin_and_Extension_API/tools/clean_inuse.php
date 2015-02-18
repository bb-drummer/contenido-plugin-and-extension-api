<?php
if (!defined("CON_FRAMEWORK")) {
    define("CON_FRAMEWORK", true);
}

// Contenido startup process
include_once ('../classes/class.security.php');
include_once ('../startup.php');

try {
	
	$oDB = new DB_Contenido;
	$oDB->query("TRUNCATE con_inuse;");
	echo '<h1>truncated "con_inuse":</h1>';
	
} catch (Exception $e) {
	
	echo '<h1>error truncating "con_inuse":</h1>'.
		'<pre>'.print_r($e, true).'</pre>'."\n";
}

