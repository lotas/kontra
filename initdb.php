<?php

define('ROOT', "");    
define('DB', "mf");
define('HOST', getenv('MYSQL_1_PORT_3306_TCP_ADDR') ); // "localhost");
define('USER', "root"); 
define('PASW', "root");


// $con = mysqli_connect(HOST, USER, PASW, DB);
$con = mysql_connect(HOST, USER, PASW);

mysql_select_db(DB);
mysql_query("SET NAMES utf8");

$log = array();
function my_mysql_query($query) {
	global $log;
    global $con;
	$log[] = $query;
	$res = mysql_query($query);
	return $res;
}

?>