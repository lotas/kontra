<?php
session_start();


if (!isset($_SESSION["status"]) || ($_SESSION["status"] == "loggedout"))
{

	header("HTTP/1.0 500 Not authorized.");

	die('not authorized');

}


include("initdb.php");

include("funcs.php");

include_once "mytempl.php";

include_once "mysys.php";


$action = get_var("action", "");


switch ($action)
{

	case "uinfo":
	setContentType('text/html');

	$userName = get_var("name", "");

	echo Service::getUserInfo($userName);

	break;


	case "questions.js":
	setContentType('application/javascript');

	echo 'alert("yeah");';

	break;


	case "voteq":
	setContentType("text/plain");

	$qid = substr(get_var("id", " "), 1);
	// 	qXXX - getting XXX part
	$rate = get_var("rate", "0");

	echo ajaxQuestionVote($qid, $rate);

	break;


	case "votea":
	setContentType("text/plain");

	$aid = substr(get_var("id", " "), 1);
	// 	aXXX - getting XXX part
	$rate = get_var("rate", "0");

	echo ajaxAnswerVote($aid, $rate);

	break;


	case "chatsay":
	$msg = get_var("message", "");

	Service::chatSay($msg);

	case "chat":
	setContentType('text/html');

	$lastId = get_var('id', '0');

	echo Service::getChat($lastId);

	break;

}



class Service
{

	function getUserInfo($name)	{

		$res = my_mysql_query("SELECT * FROM kusers WHERE nick='".mysql_real_escape_string($name)."'");

		if ($row = mysql_fetch_assoc($res))	{

			$res = array();

			$res[] = '<div id="userPopup">';

			$res[] = '<div class="userpic"><img src="up/'. $row['nick'] . '_sm.jpg' . '" width="100" /></div>';

			$res[] = '<div class="uinfo">' . "<b>Name:</b> " . $row['fullname'] . "<br/>";

			$res[] = (isUserOnline($row['id'])) ? "On-line!!!" : "<b>Last login:</b> " . $row['last_login_date'];

			$res[] = "<br/><b>Questions:</b> " . $row['questions'];

			$res[] = "<br/><b>Answers:</b> " . $row['answers'];

			$res[] = "<br/><b>Rating:</b> " . $row['rating'];

			$res[] = '</div>';

			return join("\n", $res);

		}

		echo 'Invalid request!';

	}


	function chatSay($msg) {

		$user = $_SESSION['user']['nick'];

		$date = date('Y-m-d H:i:s');

		$msg = htmlspecialchars(mysql_real_escape_string($msg));

		my_mysql_query("INSERT INTO chat (`name`, `date`, `message`) VALUES ('$user', '$date', '$msg')") or die (mysql_error());

		return mysql_insert_id();

	}


	function getChat($lastId) {

		$result = array();


		$where = ($lastId > 0) ? ' WHERE id>'.$lastId.' ' : '';

		$mres = my_mysql_query("SELECT * FROM chat ". $where ." ORDER BY id DESC LIMIT 50");

		while ($row = mysql_fetch_assoc($mres))	{

			preg_match('/(\d+)\-(\d+)\-(\d+)\s(\d+):(\d+):(\d+)/', $row['date'], $m);

			$date = $m[3] . '/' . $m[2] . '  ' . $m[4] . ':' . $m[5];

			$result[] = '<div class="'.$row['name'].'" cid="'.$row['id'].'"><span class="user">'.$row['name']
			.'</span> <span class="date">'.$date.'</span> '. $row['message'] . '</div>';

		}

		return join("\n", $result);

	}

}


?>