<?php

	define('ROOT', "");    
define('DB', "mf");
define('HOST', "localhost");
define('USER', "root"); 
define('PASW', "root");

$con = mysql_connect(HOST, USER, PASW);
mysql_select_db(DB);
// mysql_query("SET NAMES cp1251");

$log = array();
function my_mysql_query($query) {
	global $log;
	$log[] = $query;
	$res = mysql_query($query);
	return $res;
}

?>