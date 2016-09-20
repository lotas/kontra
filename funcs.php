<?php

/*****************  functions      **************************/

/* --=-=-=-=-= loggin in =-=-=-=-=-=- */
    function lohin() {
      $user = get_var("nick", "none");
      $pasw = get_var("pasw", "none");

      $res = my_mysql_query("select * from kusers where UPPER(nick)='".strtoupper($user)."'
                       and password='$pasw'") or die(mysql_error());

      $r = mysql_fetch_array($res, MYSQL_ASSOC);

      if (! preg_match("/^([0-9]+)$/",$r["id"])) {
        $error = "no such user";
        $_SESSION["status"] = "loggedout";
      } else {
        $error = "";
        $_SESSION["status"] = "loggedin";
        $_SESSION["user"]["id"] = $r["id"];
        update_user_stats();

        /* who's online */
        add_onliner($_SESSION["user"]["id"],$_SESSION["user"]["nick"]);		
      }
      return ($error != "") ? false : true;
    } // lohin()

    function update_user_stats()     	
    {
		$res = my_mysql_query("select * from kusers where id='".
		    $_SESSION["user"]["id"]."'") or die(mysql_error());
		$r = mysql_fetch_array($res, MYSQL_ASSOC);
		
		$_SESSION["user"]["nick"] = $r["nick"];
		$_SESSION["user"]["questions"] = total_questions($_SESSION["user"]["id"]);
		$_SESSION["user"]["answers"] = total_answers($_SESSION["user"]["id"]);
		$_SESSION["user"]["status"] = $r["status"];
    }

    /* adding onliner */
    function add_onliner($id, $nick)     	
    {    	
		my_mysql_query ("delete from onliners where userid='$id' or userid='99'");		
		my_mysql_query ("insert into onliners (userid, nick, logon) values ('$id', '$nick', '".date("Y-m-d H:i:s")."')");	
		my_mysql_query ("insert into onliners (userid, nick, logon) values ('99', 'Kontra', '".date("Y-m-d H:i:s")."')");
		/* storing each access as last_login_date */
		my_mysql_query ("UPDATE kusers SET last_login_date='".date("Y-m-d H:i:s")."' WHERE id='".$id."'");	
		return true;
    }

    /* remove */
    function remove_onliner($nick) {
        return my_mysql_query ("delete from onliners where nick='$nick'");
    }

    /* list onliners */
    function update_onliners() {
		/* purging old onliners */
		$timeout = 60 * 30;	//30 min timeout
		my_mysql_query("delete from onliners where (unix_timestamp(logon)+$timeout) < unix_timestamp('".date("Y-m-d H:i:s")."')")
			or die(mysql_error());
	
		$res = my_mysql_query("SELECT *, k.sex, (UNIX_TIMESTAMP('".date('Y-m-d H:i:s')."')-UNIX_TIMESTAMP(logon)) AS 'sec' FROM onliners o 
				LEFT JOIN kusers k ON k.nick=o.nick ORDER BY logon DESC") or die(mysql_error());
		$onliners = "";
		
		$_SESSION['onliners'] = array();
				
		while ($u = mysql_fetch_assoc($res))
		{
			$_SESSION['onliners'][$u['id']] = $u;
		}
		return $onliners;
    }
    
    function isUserOnline($id) {
    	return (isset($_SESSION['onliners']) && isset($_SESSION['onliners'][$id]));
    }

/* -=-=- chatting -=-=-=- */
         function chat(&$index) {



         }

	function redirect($path) {
        header("location: " . $path);
        echo ' ';
        die;
	}


/* --=-=-=-=-= loggin out =-=-=-=-=-=- */
    function lohout(&$index) {
       remove_onliner($_SESSION["user"]["nick"]);
       unset($_SESSION["user"]);
       session_unregister("user");
       $_SESSION["status"] = "loggedout";
       redirect('kontra.php');
    }//lohout()


/* --=-=-=-=-= right top login box
    showing edits or user info if logged in
 =-=-=-=-=-=- */
    function login_off_box(&$index) {
      /* Checking if user already loggedin */
          $index->define(array(
				"USERNAME" =>   $_SESSION["user"]["nick"],
				"CURTIME" => date("Y-m-d H:i:s"),
				"QUESTIONS" =>  $_SESSION["user"]["questions"],
				"ANSWERS" =>    $_SESSION["user"]["answers"]
          ));
          
		// today stats
		$tq = my_mysql_query("select count(*) from kquest where to_days(data) = to_days(now())");
		$today_q = mysql_result($tq, 0, 0);
		$ta = my_mysql_query("select count(*) from kanswer where to_days(data) = to_days(now())");
		$today_a = mysql_result($ta, 0, 0);
		$yq = my_mysql_query("select count(*) from kquest where to_days(data) = (to_days(now())-1)");
		$yd_q = mysql_result($yq, 0, 0);
		$ya = my_mysql_query("select count(*) from kanswer where to_days(data) = (to_days(now())-1)");
		$yd_a = mysql_result($ya, 0, 0);
		$wq = my_mysql_query("select count(*) from kquest where to_days(data) >= (to_days(now())-7)");
		$wd_q = mysql_result($wq, 0, 0);
		$wa = my_mysql_query("select count(*) from kanswer where to_days(data) >= (to_days(now())-7)");
		$wd_a = mysql_result($wa, 0, 0);
		$aq = my_mysql_query("select count(*) from kquest");
		$ad_q = mysql_result($aq, 0, 0);
		$aa = my_mysql_query("select count(*) from kanswer");
		$ad_a = mysql_result($aa, 0, 0);
	
	  $index->define(array("TODAY_QUESTIONS" => $today_q." ",
				"TODAY_ANSWERS" => $today_a." ",
				"YEST_QUESTIONS" => $yd_q." ",
				"YEST_ANSWERS" => $yd_a." ",
				"WEEK_QUESTIONS" => $wd_q." ",
				"WEEK_ANSWERS" => $wd_a." ",
				"ALL_QUESTIONS" => $ad_q." ",
				"ALL_ANSWERS" => $ad_a." "));
	
		// last newsletter
	    $nwl = file_get_contents("nwl.dat");
	    $out = preg_split("/,/", $nwl);
	    $numb = $out[0];    
	    $data = $out[1];  
		$index->define(array("NWLNUMB" => $numb, "NWLDATE" => $data));
		
		prepareUserStats($index);
    } 
        
    function prepareUserStats(&$index)
    {
		update_onliners();
		$onliners = $_SESSION['onliners'];
						
		// user ratings box
		$ratings = getratings();
		//updating stats
		
		$bAvg = 0; $cnt = 0;
		foreach ($ratings as $userid => $k) {
			my_mysql_query("update kusers set rating='". ($k["quest"] + $k["answ"]). "' where id='".$userid."'");
			
			/* bajes stuff - calc avg rate */
			$bAvg += $k["quest-avg"] + $k["answ-avg"];
			$cnt += 2;
		}
		$bAvg = $bAvg / $cnt;
		// user statistics
		$h = my_mysql_query("select * from kusers order by rating desc, (questions+answers) desc");
		$i = 0;
					
				
		$res = array();
		while ($row = mysql_fetch_assoc($h)) {
			$i++;
			$u_id = &$ratings[$row["id"]];
			$imgName = ($row['sex'] == 'male' ? 'male' : ($row['sex'] == 'bot' ? 'user8' : 'fem'));
			$isOnline = isset($onliners[$row['id']]);
			if (!$isOnline) $imgName .= '_off';
			
			$res[] = array(
				  "CLS" => ($i % 2 == 0) ? 'odd' : 'even',
				  "ADDCLASS" => ($isOnline) ? '' : ' offline',
				  "SUSER" => $row["nick"],
				  "SIMG" => $imgName . '.png',
				  "SQUEST" => $row["questions"],
				  "SAVGQ" => $u_id["quest"],
				  "SAVGQP" => ($row["questions"] > 0) ? sprintf("%.2f",  $u_id["quest-avg"]) : '',
				  "SAVGA" => $u_id["answ"],
				  "SANSW" => $row["answers"],
				  "SAVGAP" => ($row["answers"] > 0) ? sprintf("%.2f", $u_id["answ-avg"]) : '',
				  "SRATE" => trim(substr(($u_id["answ"] + $u_id["quest"]), 0, 4)),
				  "SAVG" => sprintf("%.2f", ($u_id["answ-avg"]+$u_id["quest-avg"]) ),
				  "SQUARE" => sprintf("%.4f", ($u_id["answ-avg"]*$u_id["quest-avg"]) )
			   );
		}

		updateMaxKeyValue($res, "SQUEST");
		updateMaxKeyValue($res, "SAVGA");
		updateMaxKeyValue($res, "SAVGAP");
		updateMaxKeyValue($res, "SAVGQ");
		updateMaxKeyValue($res, "SAVGQP");
		updateMaxKeyValue($res, "SANSW");
		updateMaxKeyValue($res, "SRATE");
		updateMaxKeyValue($res, "SAVG");
		updateMaxKeyValue($res, "SQUARE");

		foreach ($res as $row) {			
			$index->dynamic("STATS", $row);
		}
		
		$curDay = date('d');
		$dayOfWeek = date("w") == 0 ? 7 : date("w");		
		$weekStart = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), $curDay - $dayOfWeek , date('Y')));
		$weekEnd = date('Y-m-d H:i:s', mktime(0, 0, 0, date("m"), $curDay - $dayOfWeek + 7, date('Y')));
						
		// week stats
		$resAns = my_mysql_query("SELECT SUM(va.rating) AS 'rate', u.nick
							FROM kanswer ka 
							LEFT JOIN kusers u ON ka.userid=u.id
							LEFT JOIN votes va ON ka.aid=va.answerid							
							WHERE ka.data >= '".$weekStart."' AND ka.data <= '".$weekEnd."'
						    GROUP BY u.id
						    ORDER BY rate DESC
						    LIMIT 7");
		$i = 0;
		while($row = mysql_fetch_assoc($resAns)) {
			$index->dynamic("WEEK_ANS_STATS", array(
				"USER" => $row['nick'],
				"RATE" => $row['rate'],
				"CLS" => (++$i == 1) ? " class='first'" : ''
			));
		}
		
		$questAns = my_mysql_query("SELECT SUM(vq.rating) AS 'rate', u.nick
							FROM kquest kq 
							LEFT JOIN kusers u ON kq.userid=u.id
							LEFT JOIN votes vq ON kq.qid=vq.questid	
							WHERE kq.data >= '".$weekStart."' AND kq.data <= '".$weekEnd."'
						    GROUP BY u.id
						    ORDER BY rate DESC
						    LIMIT 7") or die(mysql_error()) ;
		$i = 0;
		while($row = mysql_fetch_assoc($questAns)) {
			$index->dynamic("WEEK_QUEST_STATS", array(
				"USER" => $row['nick'],
				"RATE" => $row['rate'],
				"CLS" => (++$i == 1) ? " class='first'" : ''
			));
		}
    }
    
    /* wraping in <b></b> max element */
    function updateMaxKeyValue(&$res, $key) {
    	$max = 0;
    	foreach ($res as $i => $row) 
    		if ($row[$key] > $max) $max = $row[$key];
    	
    	foreach ($res as $i => $row) 
    		if ($row[$key] == $max) $res[$i][$key] = "<b>".$max."</b>";
    }

/* -=-=-= total number of questions -=-=-=- */
        function total_questions($uid) {
    $res = my_mysql_query("select count(*) as total from kquest where userid='$uid'");
    if ($a = mysql_fetch_array($res, MYSQL_ASSOC)) {
            return $a["total"];
    } else {
             return "0";
    }
        } //func

/* -=-=-= total number of answers -=-=-=- */
        function total_answers($uid) {
    $res = my_mysql_query("select count(*) as total from kanswer where userid='$uid'");
    if ($a = mysql_fetch_array($res, MYSQL_ASSOC)) {
            return $a["total"];
    } else {
             return "0";
    }
        } //func


/* --=-=-=-=-= remember the question  =-=-=-=-=-=- */
    function ask_question(&$index, $q) {
		if (strlen($q) > 3) {
   			$query = "insert into kquest (userid, question, data) VALUES ('".
     			$_SESSION["user"]["id"]."', '$q', '". date("Y-m-d H:i:s") . "') ";

			my_mysql_query($query) or die(mysql_error());

			//real number of questions of the user in the database
			$res = my_mysql_query("select COUNT(qid) as 'cnt' from kquest where userid='".$_SESSION["user"]["id"]."'");
			$tquest = mysql_result($res, 0, 0);
			
			$query2 = "update kusers set questions=$tquest  where id='".$_SESSION["user"]["id"]."'";			
			my_mysql_query($query2) or die(mysql_error());
    	}
    }

/* --=-=-=-=-= add answer for the question  =-=-=-=-=-=- */
    function add_answer($answer, $aid, $qid, $uid) {
		if (! preg_match("/^([0-9]+)$/", $qid)) {return false;}
		if (! preg_match("/^([0-9]+)$/", $uid)) {return false;}
		if (! preg_match("/^([0-9]+)$/", $aid) and ($aid!="")) {return false;}
		
		/* if editing previous one */
		if ($aid == "") {
			$query = "insert into kanswer (userid, aquestion, answer, data) VALUES ('$uid', '$qid', '$answer', '".date("Y-m-d H:i:s")." ')";
		} else {
			$query = "update kanswer set answer='$answer' where aid='$aid'   and userid='$uid'";
		}
		
		my_mysql_query($query) or die(mysql_error());
		update_user_answers_count();
		return true;
    }
    
    function add_kontra_answer($qid, $aid) {
    	$res = my_mysql_query("select answer from kanswer where aid='$aid'");
    	$answer = mysql_result($res, 0, 0);
    	$res = my_mysql_query("insert into kanswer (userid, aquestion, answer, data) VALUES
         	('99', '$qid', '$answer', '".date("Y-m-d H:i:s")." ')") or die(mysql_error());
        update_user_answers_count(99);
		return true;
    }
    
    function update_user_answers_count($id = '') {
    	if ($id == '') $id = $_SESSION["user"]["id"];
		$res2 = my_mysql_query("select COUNT(aid) as 'cnt' from kanswer where userid='". $id ."'");
		$tansw = mysql_result($res2, 0, 0);
		my_mysql_query("update kusers set answers=$tansw where id='". $id ."'");
    }

 /* --=-=-=-=-= deletes an answer  =-=-=-=-=-=- */
    function del_answer($aid, $uid) {
     if (! preg_match("/^([0-9]+)$/", $uid)) {return false;}
     if (! preg_match("/^([0-9]+)$/", $aid)) {return false;}

      $query = "delete from kanswer where aid='$aid' and userid='$uid'";
      my_mysql_query($query) or die(mysql_error());

     return true;
    }

 /* --=-=-=-=-= deletes a question  =-=-=-=-=-=- */
    function del_question($qid, $uid) {
		if (! preg_match("/^([0-9]+)$/", $uid)) {return false;}
		if (! preg_match("/^([0-9]+)$/", $qid)) {return false;}
		
		if ($_SESSION["user"]["status"] != "admin") {$addon="and userid='$uid'";}
		$query = "delete from kquest where qid='$qid' $addon";
		//also delete all answers for that question and votes
		$query3 = "delete from kanswer where aquestion='$qid'";
		$query4 = "delete from votes where questid='$qid'";
		$query2 = "delete from votes, kanswer using votes,kanswer
		         where votes.answerid=kanswer.aid and kanswer.aquestion='$qid'";
		
		$res = my_mysql_query($query);

		my_mysql_query($query4) or die(mysql_error());

		$res2=@my_mysql_query("select aid from kanswer where aquestion='$qid'");
		$indexes="";
		while ($ni = @mysql_fetch_array($res2, MYSQL_ASSOC)) {
		      if ($indexes != "") $indexes .= " or ";
		   $indexes .= " answerid='".$ni["aid"]."'";
		}

		my_mysql_query($query3) or die(mysql_error());
        if ($indexes != "")
        	my_mysql_query("DELETE FROM votes WHERE ".$indexes) or die(mysql_error());

        //statistics
        $res2 = my_mysql_query("select COUNT(qid) as 'cnt' from kquest where userid='".$_SESSION["user"]["id"]."'");
        $tquest = mysql_result($res2, 0, 0);

        my_mysql_query("update kusers set questions=$tquest where id='". $_SESSION["user"]["id"]."'");
     	return true;
    }

/* --=-=-=-=-= get the question by id  =-=-=-=-=-=- */	
    function getquestion($q) {
		if (!isset($_SESSION['questions'])) $_SESSION['questions'] = array();
		
		if (!isset($_SESSION['questions'][$q])) {
			$res = my_mysql_query("select question from kquest where qid='$q'");
			$_SESSION['questions'][$q] = mysql_result($res, 0, 0);
		}
		return (isset($_SESSION['questions'][$q])) ? $_SESSION['questions'][$q] : '';
    }
    
    
    function getQuestionRating($qid) {
    	$ret = "0/0";
    	$res = my_mysql_query("SELECT *, (SELECT AVG(rating) FROM votes WHERE kquest.qid=votes.questid) AS avg FROM kquest WHERE qid='$qid'") or die(mysql_error());
    	if ($row = mysql_fetch_assoc($res)) {
    		$ret = $row['rating'] . "/" . number_format($row['avg'], 1);
    	}    	
    	return $ret;
    }
    
    function getAnswerRating($aid) {
    	$ret = "0/0";
    	$res = my_mysql_query("SELECT *, (SELECT AVG(rating) FROM votes WHERE kanswer.aid=votes.answerid) AS avg FROM kanswer WHERE aid='$aid'") or die(mysql_error());
    	if ($row = mysql_fetch_assoc($res)) {
    		$ret = $row['rating'] . "/" . number_format($row['avg'], 1);
    	}    	
    	return $ret;
    }
    
    
/* --=-=-=-=-= get the answers by id  =-=-=-=-=-=- */
    function getanswer($q) {
        $rest = array();
      if (preg_match("/^([0-9]+)$/", $q)) {
        $res = my_mysql_query("select aid,answer from kanswer
            where aquestion='$q'
            and userid='".$_SESSION["user"]["id"]."'");
            $i = "1";
        while ($qq = mysql_fetch_array($res, MYSQL_ASSOC) ){
            $rest [$i]["q"] = $qq["answer"];
            $rest [$i]["id"] = $qq["aid"];
            $i++;
        }
        for (; $i<="5"; $i++) {$rest[$i]["q"]=""; $rest[$i]["id"]="";}
      }
        return $rest;
    } //function getanswer

/* --=-=-=-=-= get the answers of everyone  =-=-=-=-=-=- */
    function getanswers($q) {
		$rest = array();		
		if (preg_match("/^([0-9]+)$/", $q)) {
			$res = my_mysql_query("select aid,answer,userid from kanswer where aquestion='$q' order by rating desc");
				$i = "1";
			while ($qq = mysql_fetch_assoc($res)) {
				$rest[$i]["q"] = $qq["answer"];
				$rest[$i]["id"] = $qq["aid"];
				$rest[$i]["user"] = id_nick($qq["userid"]);
				$i++;
			}
		}
        return $rest;
    } 

/* --=-=-=-=-= get the answers of everyone  =-=-=-=-=-=- */
    function getratings() {
        $rest = array();
        $query1 = "select userid, sum(votes.rating) as r, count(votes.uid) as c, avg(votes.rating) as a
        	       from votes, kquest
                   where votes.questid = qid
                   group by userid";
        $query2 = "select userid, sum(votes.rating) as r, count(votes.uid) as c, avg(votes.rating) as a
        	       from votes, kanswer
                   where votes.answerid = aid
                   group by userid";

        $res = my_mysql_query($query1);
        while ($qq = mysql_fetch_array($res, MYSQL_ASSOC) ){
            $rest [$qq["userid"]]["quest"] = $qq["r"];
            $rest [$qq["userid"]]["quest-count"] = $qq["c"];
            $rest [$qq["userid"]]["quest-avg"] = $qq["a"];
        }
        
        $res2 = my_mysql_query($query2);
        while ($qq = mysql_fetch_array($res2, MYSQL_ASSOC)) {
            $rest [$qq["userid"]]["answ"] = $qq["r"];
            $rest [$qq["userid"]]["answ-avg"] = $qq["a"];
            $rest [$qq["userid"]]["answ-count"] = $qq["c"];
        }        
        return $rest;    
    } //function

/* --=-=-=-=-= get the number of questions asked   =-=-=-=-=-=- */
    function q_asked() {
      $ret = "0";
      $q = $_SESSION["user"]["id"];
        $res = my_mysql_query("select count(*) as total from kquest
            where userid='$q'
            and  to_days(data) >= to_days(( now() - interval 24 hour ))")
            or die(mysql_error());
        if ($qq = mysql_fetch_array($res, MYSQL_ASSOC) ){
            $ret = $qq["total"];
        }
        return $ret;
    }


/* --=-=-=-=-= get the number of answers for the question   =-=-=-=-=-=- */
	$userAnsCache = array();
    function q_answers($q) {
		global $userAnsCache;
		
		if (count($userAnsCache) == 0) {
			$res = my_mysql_query("select count(*) as total, aquestion from kanswer
                WHERE userid='".$_SESSION["user"]["id"]."' 
                GROUP BY aquestion ORDER BY aquestion DESC	
			");
			while ($row = mysql_fetch_assoc($res)) {				
				$userAnsCache[$row['aquestion']] = $row['total'];			    
			}
		}
		return isset($userAnsCache[$q]) ? $userAnsCache[$q] : '0';
    }
/* --=-=-=-=-= get the number of answers for users   =-=-=-=-=-=- */
	$totalsAnsCache = array();
    function q_totalanswers($q) {
    	global $totalsAnsCache;

		if (count($totalsAnsCache) == 0) {
			$res = my_mysql_query("select count(*) as total, aquestion from kanswer 
						GROUP BY aquestion ORDER BY aquestion DESC");
			while ($row = mysql_fetch_assoc($res)) {
				$totalsAnsCache[$row['aquestion']] = $row['total'];			    
			}
		}
		return isset($totalsAnsCache[$q]) ? $totalsAnsCache[$q] : '0';
    }
    
/* --=-=-=-=-= get the average vote for question   =-=-=-=-=-=- */
    function q_avgrating($qid) {
		$res = my_mysql_query("select avg(rating) as avg from votes WHERE questid='".$qid."'");
		$avg = mysql_result($res, 0, 0);
		return ($avg > 0) ? substr($avg, 0, 3) : '0';
    }    

/* --=-=-=-=-= get the number of times the question was viewed   =-=-=-=-=-=- */
    function timesviewed($qid) {
      $ret = "0";
        $res = my_mysql_query("select shows from kquest where qid='$qid' ");
        if ($qq = mysql_fetch_array($res, MYSQL_ASSOC) ){
            $ret = $qq["shows"];
        }
        return $ret;
    }

/* --=-=-=-=-= answer the question  =-=-=-=-=-=- */
    function answer_question(&$index, $q) {

        $index->conditional("READ", true);
        $answers = getanswer($q);
        $index->define(array( "QUESTION" => getquestion($q),
                    "A1" => $answers["1"]["q"],
                    "A2" => $answers["2"]["q"],
                    "A3" => $answers["3"]["q"],
                    "A4" => $answers["4"]["q"],
                    "A5" => $answers["5"]["q"],
                    "A1ID" => $answers["1"]["id"],
                    "A2ID" => $answers["2"]["id"],
                    "A3ID" => $answers["3"]["id"],
                    "A4ID" => $answers["4"]["id"],
                    "A5ID" => $answers["5"]["id"],
                   "QID" => $q));
    } // asnwer_question;


/* -=-=-=-=-=-=- browse messages -=-=-=-=-=-=- */
    function browsem(&$index) {
        global $clr_nanswered;
        global $clr_answered;
        global $clr_author;

        $type   = get_var("type", "quest");
        $time   = get_var("time", "always");
        $counter= get_var("count", "50");
        $offset = get_var("offset", "0");
        $sorter = get_var("sort", "date");

        //searching among questions
        if ($type == "quest" ) {

        $index->conditional("BROWSE1", true);

     	/* create query upon recieve params :
        	sort -> date,rate,notan, an ,x
        	count -> 10,20,50,inf*/
        $query = "select kquest.*, (SELECT AVG(rating) FROM votes WHERE kquest.qid=votes.questid) as avg from kquest ";

        switch ($time) {
			case "1day": $query .= " where data > (now() - interval 24 hour) ";break;
			case "3day": $query .= " where data > (now() - interval 72 hour) ";break;
			case "week": $query .= " where data > (now() - interval 7 day) ";  break;
        }

        if ($sorter=="date") {
            $query .= " order by data desc ";
        } else if ($sorter=="rate") { 
        	$query .= " order by rating desc "; 
        } else if ($sorter=="shows") { 
        	$query .= " order by shows desc "; 
        }

        if ($counter != "inf") {
			$offset = (preg_match("/([0-9]+)/", $offset)) ? $offset : "0";
			$showing =  $counter;
        	
			if (preg_match("/(10|20|50)/", $showing)) {
            	$query .= " limit ".$offset.",".$showing;
        	}
        } //count ne inf

		$res = my_mysql_query($query) or die(mysql_error());
		while ($rw = mysql_fetch_array($res, MYSQL_ASSOC)) 
		{
			$nanswers = q_answers($rw["qid"]) ;
			//if showing answered/not answered
			if ((($sorter == "notan") and ($nanswers == "0") )
			    or (($sorter == "an") and ($nanswers != "0"))
			    or (!preg_match("/(notan|an)/",$sorter))) 
			{
				$clr = $clr_nanswered;
				if ($nanswers > 0 )  $clr = $clr_answered;
				else $clr = $clr_nanswered;
				if ($rw["userid"] == $_SESSION["user"]["id"]) {
					$clr = $clr_author;
				}
				
				//rating
				$totans = q_totalanswers($rw["qid"]);
				$isVoted = did_vote_q($rw["qid"]);
				$index->dynamic("BQ",
				      array("QUESTION" => $rw["question"],
				        "DATA" => $rw["data"],
				        "QID" => $rw["qid"],
				        "QANS" => $nanswers,
				        "TIMESVIEWED" => $rw["shows"],
				        "COLOR" => $clr,
				        "QVOTECLR" => $isVoted ? $clr_answered : $clr_nanswered,
				        "ISVOTED" => $isVoted ? 'voted="true"' : 'voted="false"',
				        "RATING" => $rw["rating"],
				        "AVGRATING" => number_format((float)$rw["avg"], 1),
				        "QANSALL" => $totans ));
			}
        }

        //browsing by answers
    } else if ($type == "answe") {

        $index->conditional("BROWSE2", true);
        $query = "SELECT kanswer.*,  (SELECT AVG(rating) FROM votes WHERE kanswer.aid=votes.answerid) as avg FROM kanswer ";

        switch ($time) {
			case "1day": $query .= " where data > now() - interval 24 hour ";break;
			case "3day": $query .= " where data > now() - interval 72 hour ";break;
			case "week": $query .= " where data > now() - interval 7 day ";break;
        }

         if ($sorter=="rate") { $query .= " order by rating desc "; }
         else { $query .= " order by data desc "; }

        if ($counter != "inf") {
			$offset = (preg_match("/([0-9]+)/", $offset)) ?        $offset : "0";
			$showing =  $counter;
			if (preg_match("/(10|20|50)/", $showing)) {
			    $query .= " limit ".$offset.",".$showing;
			}
		} //count ne inf

  		$res = my_mysql_query($query) ;

  		while ($rw = mysql_fetch_array($res, MYSQL_ASSOC)) {
  			
			//if showing answered/not answered
			
			if ($rw["userid"] == $_SESSION["user"]["id"]) { $clr = $clr_author; }
			else {$clr = $clr_nanswered;}
			//rating
			$isVoted = did_vote_a($rw["aid"]);
			$totans =  q_totalanswers($rw["aquestion"]);
			$index->dynamic("BBQ",
			      array("QUESTION" => getquestion($rw["aquestion"]),
			        "DATA"    => $rw["data"],
			        "ANSWER"  => $rw["answer"],
			        "AID"     => $rw["aid"],
			        "QID"     => $rw["aquestion"],
			        "COLOR"   => $clr,
			        "RATING"  => $rw["rating"],
			        "ISVOTED" => $isVoted ? 'voted="true"' : 'voted="false"',
			        "AVOTECLR"=> $isVoted ? $clr_answered : $clr_nanswered,
			        "AVGRATING" => number_format((float)$rw["avg"], 1),
			        "QANSALL" => $totans
			        ));
        }//while

    }

        //putting all necessary links
        if ($counter != "inf") {
            $curlink = $offset."-".$counter;
            if ($offset-$counter >= 0) {
              $prevlink = "<a href=kontra.php?action=browse&type=$type&sort=$sorter&time=$time&count=$counter&offset=".
                              ($offset-$counter)."> ".($offset-$counter)."-".$offset."</a>";
            } else {$prevlink="...";}
            $nextlink = "<a href=kontra.php?action=browse&type=$type&sort=$sorter&time=$time&count=$counter&offset=".
                            ($offset+$counter)."> ".($offset+$counter)."-...</a>";

            $index->define( array(
               "PREVLINK" => $prevlink,
               "NEXTLINK" => $nextlink,
               "CURRENTLINK"=> $curlink
            ));
        }
    }

/* -=-=-=-=-=-=-= printing the questions & answers -=-=-=-=- */
   function read_part(&$index, $qid) {
    //increment the number of views of this question
        global $clr_nanswered;
        global $clr_answered;
        global $clr_author;

    my_mysql_query("update kquest set shows=shows+1 where qid='$qid'");

    $index->conditional("READ", true);
    $question = getquestion($qid);
    $isVoted = did_vote_q($qid);
    $index->define(array("READNEXT" => $qid+1, "READPREV" => $qid-1,
                    "QUESTION" => $question,
                    "WHOASK" => whoose($qid),
                    "QID" => $qid,
                    "TIMESVIEWED" => timesviewed($qid),
                    "QVOTECLR"=> $isVoted ? $clr_answered : $clr_nanswered,
                    "ISVOTED" => $isVoted ? 'voted="true"' : 'voted="false"',
                    "QRATING" => qrating($qid),
                    "QAVGRATING" => number_format((float)qAvgRating($qid), 1),
		    ));

    $answers = getanswers($qid);
    
    foreach ($answers as $one) {
    	$aVoted = did_vote_a($one["id"]);
		$clr = $aVoted ? $clr_answered : $clr_nanswered;

		$index->dynamic("RD", array(
			"ANSWER" => $one["q"],
			"QUESTION" => $question,
			"RATING" => answer_rating($one["id"]),
			"AVGRATING" => number_format((float)answerAvgRating($one["id"]), 1),
			"ISVOTED" => $aVoted ? 'voted="true"' : 'voted="false"',
			"RNDI" => mt_rand(0,14) + 1,
			"USER" => $one["user"],
			"AVOTECLR"=> $clr,
			"AID" => $one["id"]
		));
	}
   } // end to the function

/* -=-=-=-=- return the name of the author -=-=-=- */
  function whoose($qid) {
    $res = my_mysql_query("select nick from kusers, kquest where
            kusers.id=kquest.userid and qid='$qid'");
    if ($f = mysql_fetch_array($res, MYSQL_ASSOC)) {
             $nick = $f["nick"];
    } else {
              $nick = "нихто";
    }

    $acts = array("интересуется", "хочет знать", "всё же спрашивает",
            "желал бы ответа", "спрашивает", "пишет", "вот захотел спросить",
            "приспичило задать вопрос", "задаёт примерно такой вопрос",
            "думает что всех удивит таким вот вопросом",
            "выкапал(а) где-то такое вот", "пьяный! Не слушайте его!",
            "задолбал уже вопросом этим", "радует всех вопросом",
            "чирикнул что-то", "по пъяни написал вот что",
            "в сердцах бросил", "выронил из души", "мучается по поводу",
            "умрёт если не узнает ответа");
    $who = "<span>".$nick."</span> ".$acts[mt_rand(0, count($acts)-1)];

    return $who;
  }

  function whoose_id($qid) {
    $res = my_mysql_query("select userid from kquest where qid='$qid'");
    if ($f = mysql_fetch_array($res, MYSQL_ASSOC)) {
             $id = $f["userid"];
    } else {
              $id = "0";
    }
    return $id;
  }

  function whoose_a_id($aid) {
    $res = my_mysql_query("select userid from kanswer where aid='$aid'");
    if ($f = mysql_fetch_array($res, MYSQL_ASSOC)) {
             $id = $f["userid"];
    } else {
              $id = "0";
    }
    return $id;
  }


/* -=-=-=-=- rating of the question -=-=-=- */
  function qrating($qid) {
    $res = my_mysql_query("select rating from kquest where qid='$qid'");
    return mysql_result($res, 0, 0);
  }
  
  function qAvgRating($qid) {
    $res = my_mysql_query("select AVG(rating) from votes where questid='$qid'");
    $avg = mysql_result($res, 0, 0);
    return ($avg) ? $avg : '0'; 
  }

/* -=-=-=-=- rating of the question -=-=-=- */
  function answer_rating($aid) {
    $res = my_mysql_query("select rating from kanswer where aid='$aid'");
    return mysql_result($res, 0, 0);
  }
  
  function answerAvgRating($aid) {
    $res = my_mysql_query("select AVG(rating) from votes where answerid='$aid'");
   $avg = mysql_result($res, 0, 0);
    return ($avg) ? $avg : '0';
  }

/* -=-=-=-=- voting stuff -=-=-=-=-=-=-= */
   function vote_for_it() {
           /* get all id's for voting */
           $avotes = array();
           $qvotes = array();
        foreach ($_POST as $key => $value){
         if (preg_match("/^a([0-9]+)$/",$key, $ans) &&
          ( ($value >= '0') && ($value <= '5')) ) {$avotes[$ans[1]]=$value;}
          else
         if (preg_match("/^q([0-9]+)$/",$key, $ans) &&
          ( ($value >= '0') && ($value <= '5')) ) {$qvotes[$ans[1]]=$value;}
        }

        vote_for_questions($qvotes);

        //answers ratings
        vote_for_answers($avotes);

   } //end function

        /* -=-=-=-=-=-=- voting just for answers -=-=-=-=-=-=- */
        function vote_for_answers($avotes) {
        //adding them......
         foreach ($avotes as $aid => $rate) {
               if ($rate == '0') continue; //skipping zeros

        if ((whoose_a_id($aid) != $_SESSION["user"]["id"])
                && !did_vote_a($aid)){
         //user didn't vote for it yet, count his vote
                 @my_mysql_query("insert into votes (uid,answerid,rating) values
                 ('".$_SESSION["user"]["id"]."', '$aid', '$rate') " ) or die(mysql_error());
                //update the answers record
                @my_mysql_query("update kanswer set rating=rating+$rate where aid='$aid'") or die(mysql_error());
             } //added question rating

                 }//foreach
         }//function vote_for_ansers()

        /* -=-=-=-=-=-=- voting just for questions -=-=-=-=-=-=- */
        function vote_for_questions($qvotes) {
        //adding them......
         foreach ($qvotes as $qid => $rate) {
               if ($rate == '0') continue; //skipping zeros

        if ((whoose_id($qid) != $_SESSION["user"]["id"])
                && !did_vote_q($qid)) {
                 //user didn't vote for it yet, count his vote
                 @my_mysql_query("insert into votes (uid,questid,rating) values
                 ('".$_SESSION["user"]["id"]."', '$qid', '$rate') " )
                         or die(mysql_error());
                //update the question record
                @my_mysql_query("update kquest set rating=rating+$rate where qid='$qid'")
                        or die(mysql_error());
           } //added question rating
         }//foreach
         }//function vote_for_questions()

         /* =-=-=- - =-= -= - - - =-= -= */
         function did_vote_q($qid) {
                 $res = true;
                     //checking is user didn't vote yet
           $res1 = @my_mysql_query("select * from votes where questid='$qid'
                   and uid='".$_SESSION["user"]["id"]."'");
            if (@mysql_num_rows($res1) == 0) $res = false;
              return $res;
         }

         /* =-=-=- - =-= -= - - - =-= -= */
         function did_vote_a($aid) {
                 $res = true;
                     //checking is user didn't vote yet
           $res2 = @my_mysql_query("select * from votes where answerid='$aid'
                    and uid='".$_SESSION["user"]["id"]."'");
            if (@mysql_num_rows($res2) == 0) $res = false;
              return $res;
         }

/* -=-=-=-=-=- answers part -=-=-=- */
  function answers_part(&$index, $qid) {
  	$redirect = false;
 	/* modifiying existing answers, deleting, adding new */
    for ($i=1; $i<=5; $i++) {
        $a = $_POST["answ$i"];  $aid = $_POST["a$i"];
        $_delname = "a".$i."d";
        if (isset($_POST[$_delname])) {$adel = $_POST[$_delname]; }
        if (isset($adel) && ($adel == $aid)) {
           del_answer($aid, $_SESSION["user"]["id"]);
        } else {    
			if ($a != "") {
         		add_answer($a, $aid, $_POST["qid"], $_SESSION["user"]["id"]); 
         		$redirect = true;
         	}
        }
    }

     //real number of answer of the user in the database
     $res = my_mysql_query("select COUNT(aid) as 'cnt' from kanswer where userid='". $_SESSION["user"]["id"]."'");
     $tquest = mysql_result($res, 0, 0);
     my_mysql_query("update kusers set answers=$tquest where id='".$_SESSION["user"]["id"]."'");
     update_user_stats();
     read_part($index, $qid);
  }// answers_part();


  /* -=-=-=-=-=-=- top 5 part -=-=-=-=-=-=- */
    function top5_part(&$index) {
		$index->conditional("TOP5", true);
		$query = "select aquestion, answer, rating from kanswer ".
			"where data > (now() - interval 7 day) ".
			"order by data desc limit 20";
		$res = my_mysql_query($query) ;
		
		while ($rw = mysql_fetch_array($res, MYSQL_ASSOC)) {
			$index->dynamic("TOPFIVE", array(
				"QUESTION" => getquestion($rw["aquestion"]),
				"QID" => $rw["aquestion"],
				"ANSWER" => $rw["answer"],
				"RATING" => $rw["rating"]
			));
    	}
    }
    
    function generator(&$index) {	
    	if (!isset($_SESSION['generator_count'])) $_SESSION['generator_count'] = 1;
    	
    	if ($_SESSION['generator_count'] <= 3) {
	    	// generator
	    	$res = my_mysql_query("SELECT qid, question, RAND() as rnd FROM kquest ORDER BY rnd LIMIT 1");
	    	$qRow = mysql_fetch_assoc($res);
	    	$res = my_mysql_query("SELECT aid, answer, RAND() as rnd FROM kanswer ORDER BY rnd LIMIT 1");
	    	$aRow = mysql_fetch_assoc($res);	    	
	    	$index->define(array(
	    		'RANDOMQUESTION' => $qRow['question'], 
	    		'RANDOMQUESTIONID' => $qRow['qid'],
	    		'RANDOMANSWER' => $aRow['answer'],
	    		'RANDOMANSWERID' => $aRow['aid']
			));
			$_SESSION['generator_count']++;
		} else {
			$index->define(array(
	    		'RANDOMQUESTION' => 'Задолбался за вас шутки писать ...', 
	    		'RANDOMQUESTIONID' => 0,
	    		'RANDOMANSWER' => 'Придумывайте сами или марш работать!!!',
	    		'RANDOMANSWERID' => 0
			));
		}		
    } 


    /*-=-=-=-=-=-=updating questions.html =-=*/
    function update_questions($kk)
    {
        global $clr_nanswered;
        global $clr_answered;
        global $clr_author;

		/* caching or not */

		//$fileMTime = date ("Y-m-d H:i:s.", filemtime("questions.js"));
		
		$r1 = my_mysql_query("SELECT COUNT(*) FROM kanswer");
		$answersCount = mysql_result($r1, 0, 0);
		
		$r2 = my_mysql_query("SELECT COUNT(*) FROM kquest");
		$questionsCount = mysql_result($r2, 0, 0);
			
		$fileName = 'cache/' . strtolower($_SESSION['user']['nick']) . '_' . $questionsCount . 'x' . $answersCount . '.js';
		
		/* no file in cache, create one */
		if (!file_exists($fileName))
		{
	        $questions = new MyTemplate("questions.html", "templates/");
			/* -- filling last 30 questions -- */
			$query = "select * from kquest ";
	
			if (preg_match("#^([0-9]+)$#", $kk)) {
				$query .= "where userid='$kk'";
			}
			$query .= "order by qid desc limit 50";
	
			$res = my_mysql_query($query);
			while ($rw = mysql_fetch_assoc($res)) {
				$answers = q_answers($rw["qid"]);
				
	  			//background color for the question
				if ($answers > 0 ) { $clr = $clr_answered;}
					else  {$clr = $clr_nanswered;}
				if ($rw["userid"] == $_SESSION["user"]["id"]) {
					$clr = $clr_author;
				}
	
				//rating
				$qc = preg_replace("/[\r\n]+/", "<br/>", $rw["question"]);    
				$questions->dynamic("Q", array(
					"QUESTION" => $qc, 
					"QID" => $rw["qid"],
					"QCOLOR" => $clr,
					"TOTANS" => q_totalanswers($rw["qid"]),
					"QAVGRATE" => q_avgrating($rw["qid"]),
					"RATING" => $rw["rating"],
					"USER" => id_nick($rw["userid"])
				));
	        }//while
	
			$questions->parse();
			$file = $questions->template();
			
			cleanUpCache($_SESSION['user']['nick']);	//removing user js's
			write_file($fileName, $file);
		}

		return '<script type="text/javascript" src="'.$fileName.'"></script>';
	}

    /* -=-=-=-=- user setup's page -=-=-=- */
	function setup(&$index) 
    {
		$index->conditional("SETUP", true);
		
		if (get_var("action2", "") == "userpic") {
			if (isset($_FILES['userpicFile'])) {
				$f = $_FILES['userpicFile'];
				if (is_uploaded_file($f['tmp_name']) && preg_match('/image/i', $f['type'])) {
					$fsmall = 'up/' . $_SESSION["user"]["nick"] . '_sm.jpg';
					$fbig = 'up/' . $_SESSION["user"]["nick"] . '.jpg';
					
					createThumbnail($f['tmp_name'], $fsmall, 100, 100, false);
					createThumbnail($f['tmp_name'], $fbig, 450, 450, false);
					
					redirect('kontra.php?action=setup');
				}
			}
		}

		if ($_SESSION["user"]["nick"] == "LoTaS") {
			$index->conditional("ADMIN", true);			

			$act2 =	 get_var("action2", "none");
			$usid =  get_var("uid", "none");
			$sqlquery = stripslashes(get_var("sqlquery", ""));
	

			if (($act2 == "sql") && (strlen($sqlquery) > 5)) {
				$res = my_mysql_query("$sqlquery") or die(mysql_error());
				$output = "Rows affected: ".@mysql_affected_rows($res)."\nNum rows:".@mysql_num_rows($res)."\n";
				
				while ($ar = mysql_fetch_row($res)) {
					$output .= implode("\t", $ar)."\n";
				}
				
				$index->define(array("QUERYSTATUS"=>$sqlquery, "QUERYOUTPUT" => $output));
			}
	  
		  if ($act2 == "backup") {
		       backup("kquest");
		   }
	
		} else { //non admin setup			
			
		} // non admin setup

    }

    /*-=-==- more statistics =-=-=-=- */
    function statistics(&$index) {
            $index->conditional("STATISTICS", true);

            $userlist = array();
            //getting all users
            $r = my_mysql_query("select id, nick from kusers");
            while ($m = mysql_fetch_array($r, MYSQL_ASSOC)) {
                $userlist[$m["id"]] = $m["nick"];
            }

            $newlist = "";
            $r = my_mysql_query("select userid, count(userid) as stat from kquest group by userid order by stat desc")
                     or die(mysql_error());
            while ($m = mysql_fetch_array($r, MYSQL_ASSOC)) {
              $newlist .= " \n". $userlist[$m["userid"]]. " :  ". $m["stat"]. "<br>";
            }

             $index->dynamic("STATISTIC", array("STYPE" => "Вопросы", "SDATA"=>$newlist));


            $newlist = "";
            $r = my_mysql_query("select userid, count(userid) as stat from kanswer group by userid order by stat desc")
                     or die(mysql_error());
            while ($m = mysql_fetch_array($r, MYSQL_ASSOC)) {
              $newlist .= " \n". $userlist[$m["userid"]]. " :  ". $m["stat"]. "<br>";
            }

             $index->dynamic("STATISTIC", array("STYPE" => "Ответы", "SDATA"=>$newlist));

        $query1 = "select userid, (sum(votes.rating)) as r from votes, kquest
                   where votes.questid = qid
                   group by userid
                   order by r desc";
        $query2 = "select userid, (sum(votes.rating)) as r from votes, kanswer
                   where votes.answerid = aid
                   group by userid
                   order by r desc";

            $newlist = "";
        $r = my_mysql_query($query1);
            while ($m = mysql_fetch_array($r, MYSQL_ASSOC)) {
              $newlist .= " \n ". $userlist[$m["userid"]]. " :  ". $m["r"]. "<br>";
            }
             $index->dynamic("STATISTIC", array("STYPE" => "Баллов за вопросы", "SDATA"=>$newlist));

            $newlist = "";
        $r = my_mysql_query($query2);
            while ($m = mysql_fetch_array($r, MYSQL_ASSOC)) {
              $newlist .= " \n ". $userlist[$m["userid"]]. " :  ". $m["r"]. "<br>";
            }
             $index->dynamic("STATISTIC", array("STYPE" => "Баллов за ответы", "SDATA"=>$newlist));

            $newlist = "";
        $r = my_mysql_query("select uid, count(questid) as r from votes group by uid order by r desc") or die(mysql_error());
            while ($m = mysql_fetch_array($r, MYSQL_ASSOC)) {
              $newlist .= " \n ". $userlist[$m["uid"]]. " :  ". $m["r"]."<br>";
            }
             $index->dynamic("STATISTIC", array("STYPE" => "Количество голосований за вопросы", "SDATA"=>$newlist));

            $newlist = "";
        $r = my_mysql_query("select uid, count(answerid) as r from votes group by uid order by r desc") or die(mysql_error());
            while ($m = mysql_fetch_array($r, MYSQL_ASSOC)) {
              $newlist .= " \n ". $userlist[$m["uid"]]. " :  ". $m["r"]. "<br>";
            }
             $index->dynamic("STATISTIC", array("STYPE" => "Количество голосований за ответы", "SDATA"=>$newlist));

            $newlist = "";
        $r = my_mysql_query("select uid, sum(rating) as r from votes where answerid is null group by uid order by r desc") or die(mysql_error());
            while ($m = mysql_fetch_array($r, MYSQL_ASSOC)) {
              $newlist .= " \n ". $userlist[$m["uid"]]. " :  ". $m["r"]."<br>";
            }
             $index->dynamic("STATISTIC", array("STYPE" => "Сумма голосов за вопросы", "SDATA"=>$newlist));

            $newlist = "";
        $r = my_mysql_query("select uid, sum(rating) as r from votes where questid is null group by uid order by r desc") or die(mysql_error());
            while ($m = mysql_fetch_array($r, MYSQL_ASSOC)) {
              $newlist .= " \n ". $userlist[$m["uid"]]. " :  ". $m["r"]."<br>";
            }
             $index->dynamic("STATISTIC", array("STYPE" => "Сумма голосов за ответы", "SDATA"=>$newlist));


               return true;
    }



	/* backing up anything/everything .... */
	function backup($TABLE) {
	    my_mysql_query("select * INTO OUTFILE './$TABLE.select.txt' from $TABLE ")
		or die(mysql_error());
	print "executed it"  ;
	   return true;
	}

       /* !!!!!!!!!! newsletter !!!!!!!!!!! */
	function newsletter (&$index) {

	$freq = 60*60*24 * 2;  //3 days * 24 hours * 60 mins * 60 sec 
     
    $nwl = file_get_contents("nwl.dat");
    $out = preg_split("/,/", $nwl);
    $numb = $out[0];
    $data = $out[1];
    $qqid = $out[2];
    $aaid = $out[3];

  //then it is time to send letter
   if (strtotime(date("Y-m-d H:i:s")) > strtotime($data) + $freq ) {

    $new_numb = $numb + 1;
    $new_data = date("Y-m-d H:i:s");
    $new_qqid = getlastid("kquest", "qid");
    $new_aaid = getlastid("kanswer", "aid");

	$letter = new MyTemplate("email.tpl", "templates/");
	

	$r1 = my_mysql_query("select * from kquest where qid > $qqid");
	while ($qu = mysql_fetch_array($r1, MYSQL_ASSOC)) {
	   $letter->dynamic("QQ", array(
		   "QID" => $qu["qid"],
		   "QUESTION" => $qu["question"],
		   "QRATE" => $qu["rating"],
		   "QSHOWS" => $qu["shows"],
		   "QWHO" => id_nick($qu["userid"]),
		   "QDATE" => $qu["data"]
		));
	}
	$totq = mysql_num_rows($r1);
	   mysql_free_result($r1);

	$r2 = my_mysql_query("select * from kanswer where aid > $aaid");
	while ($au = mysql_fetch_array($r2, MYSQL_ASSOC)) {
	   $letter->dynamic("AA", array(
		   "AID" => $au["aid"],
		   "QUESTION" => getquestion($au["aquestion"]),
		   "ANSWER" => $au["answer"],
		   "ARATE" => $au["rating"],
		   "AWHO" => id_nick($au["userid"]),
		   "QWHO" => quest_id_nick($au["aquestion"]),
		   "ADATE" => $au["data"]
		));
	}
 	$tota = mysql_num_rows($r2);
	   mysql_free_result($r2);
		
	$letter->define(array("NUMB" => $new_numb, "DATA" => $new_data,
		"QTOTAL" => $totq, "ATOTAL" => $tota));


	$letter->parse();
	$letter_txt = $letter->template();

//putting to arhiv	
	$fh = fopen(ROOT."templates/arhiv/$new_numb.txt", "w+b");
	fwrite ($fh, $letter_txt);
	fclose ($fh);


	$r3 = my_mysql_query("select email from kusers");
	while ($a = mysql_fetch_array($r3, MYSQL_ASSOC)) {	
		$email = $a["email"];
		mail($email, "kontra.php Digest №$new_numb",  $letter_txt,
		  	"From: mf@yaraslav.com\r\nX-Mailer: kontra.php");
	}

#		or die("kontra.php emailing problems........");
// 	}//maling

	$news = "$new_numb,$new_data,$new_qqid,$new_aaid";

	
	$fl = fopen("nwl.dat", "w+b");
	if (flock($fl, LOCK_EX)) {
	  fseek($fl, 0); ftruncate($fl, 0);
	  fwrite($fl, $news);
	  fflush($fl);  flock($fl, LOCK_UN);
	  fclose($fl);
        }
    

  } //if there's need to send

	   
	} //function newsletter

  function quest_id_nick($qid) {
    $res = my_mysql_query("select nick from kusers,kquest where qid='$qid' and kquest.userid=kusers.id");
    return mysql_result($res, 0, 0);
  }

  function id_nick($id) {
	global $_users;
    if (!isset($_users[$id])) {
    $res = my_mysql_query("select id,nick from kusers");
          while ($uu = mysql_fetch_array($res, MYSQL_ASSOC)) {
	     $_users[$uu["id"]] = $uu["nick"];
	  }
        } //if !set
    return $_users[$id];
  }

  function getlastid($table, $field) {
    $res = my_mysql_query("select max($field) from $table");
    return mysql_result($res, 0, 0);
  }
  
  function get_rand_appeal() {
	$arr = array("Задать всем джазу!", "Показать кузькину мать", "Удивить всех вот тем что ща напишу", 
		"Порвать в клочья жюри", "Одумайтесь, понимаете-ли прежде чем писать",
		"Ну напиши же ченить остроумное и интересное, блин!", "Строчить сюда", "А ты! Задал сегодня вопрос?",
		"Засtань врасплох всех вопросом", "Отморозь че-нить плиз", "Шутка шутить будем или нет? я кого спрашиваю?",
		"Насяльника, я кого спрашиваю?", "Ты - Пушкин. Пиши! Твою няню!",
		"Задай свой вопрос звезде!", "3 вопроса в день смогут улучишть вашу жизнь на 25%",
		"Минздрав рекомендует, а ты пиши давай вопрос свой болтовый уже...",
		"Все могут отвечать, но не каждый вопрос задать", "Отправить на всеобщий суд ",
		"Хороший вопрос - половина шутки", "Послать вникуда свой вопрос", 
		"Если вы читаете это сообщение значит пора придумать вопрос",
		"Писать жалобы сюда"
	  );
	  return $arr[array_rand($arr)];
  }


/************* RSS ******************/

	function getLatestQuestions($limit=40) {
		$result = array();
		$res = my_mysql_query("SELECT kquest.*, kusers.nick AS 'nick' FROM kquest LEFT JOIN kusers ON kusers.id=kquest.userid ORDER BY data DESC LIMIT ".$limit);
		while ($row = mysql_fetch_assoc($res)) {
			$result[] = $row;
		}
		return $result;
	}
	
/************* USER PROFILE ************/

	function show_profile(&$tpl, $nick) {
		$res = my_mysql_query("SELECT * FROM kusers WHERE nick='".$nick."'");
		if (!$row = mysql_fetch_assoc($res)) return;
		$userId = $row['id'];
		$tpl->define(array(
			"PROFILEUSER_NAME" => $row['fullname'],
			"PROFILEUSER_LASTLOGIN" => $row['last_login_date'],
			"PROFILEUSER_SEX" => $row['sex'],
			"PROFILEUSER_QUESTIONS" => $row['questions'],
			"PROFILEUSER_ANSWERS" => $row['answers'],
			"PROFILEUSER_RATING" => $row['rating']
		));
		
		// calc votes for questions
		$res = my_mysql_query("SELECT ua.nick, SUM(v.rating) AS 'cnt'
				FROM kusers u
				LEFT JOIN votes v ON v.uid = u.id
				LEFT JOIN kquest kq ON kq.qid = v.questid
				LEFT JOIN kusers ua ON kq.userid = ua.id
				WHERE v.answerid IS NULL AND ua.nick IS NOT NULL
				AND u.id = '".$userId."'
				GROUP BY ua.nick				
				ORDER BY cnt DESC");
						
		while ($row = mysql_fetch_assoc($res)) {
			$tpl->dynamic("UP_QV", $row);
		}
		
		// calc votes for answers
		$res = my_mysql_query("SELECT ua.nick, SUM(v.rating) AS 'cnt'
				FROM kusers u
				LEFT JOIN votes v ON v.uid = u.id
				LEFT JOIN kanswer ka ON ka.aid = v.answerid
				LEFT JOIN kusers ua ON ka.userid = ua.id
				WHERE v.questid IS NULL AND ua.nick IS NOT NULL
				AND u.id = '".$userId."'
				GROUP BY ua.nick				
				ORDER BY cnt DESC") or die(mysql_error());
						
		while ($row = mysql_fetch_assoc($res)) {
			$tpl->dynamic("UP_AV", $row);
		}
		
	}
	
	/*** bugs ***/
	
	function add_bug() {
		$userid = $_SESSION['user']['id'];
		$url = mysql_real_escape_string($_SERVER['REQUEST_URI']);
		$date = date("Y-m-d H:i:s");
		$status = 'new';
		$text = mysql_real_escape_string(get_var("text"));
		
		my_mysql_query("INSERT INTO bugs (uid, url, `date`, status, `text`) VALUES ('$userid', '$url', '$date', '$status', '$text') ");
	}
	
	function view_bugs(&$tpl) {
		$res = my_mysql_query("SELECT b.*, u.nick FROM bugs b LEFT JOIN kusers u ON u.id=b.uid ORDER BY date DESC") or die(mysql_error());
		$tpl->conditional("VIEWBUGS", true);
		while($row = mysql_fetch_assoc($res)) {
			$row['text'] = str_replace('\r\n', "<br/>", $row['text']);
			$tpl->dynamic("BUGS", $row);
		}
	}
	
	/** ajax voting **/
	function ajaxQuestionVote($qid, $rate) {
		if ($rate >= 0 && $rate <= 5) {
			if ((whoose_id($qid) != $_SESSION["user"]["id"]) && !did_vote_q($qid)) {
				my_mysql_query("UPDATE kquest SET rating=rating+'$rate' WHERE qid='$qid'") or die(mysql_error());
				my_mysql_query("insert into votes (uid,questid,rating) values
             		('".$_SESSION["user"]["id"]."', '$qid', '$rate') " ) or die(mysql_error());
         	}
		}
		return getQuestionRating($qid);
	}
	
	function ajaxAnswerVote($aid, $rate) {
		if ($rate >= 0 && $rate <= 5) {			
			if ((whoose_a_id($aid) != $_SESSION["user"]["id"]) && !did_vote_a($aid)) {
				my_mysql_query("UPDATE kanswer SET rating=rating+'$rate' WHERE aid='$aid'") or die(mysql_error());
				my_mysql_query("insert into votes (uid,answerid,rating) values
             		('".$_SESSION["user"]["id"]."', '$aid', '$rate') " ) or die(mysql_error());
         	}
		}
		return getAnswerRating($aid);
	}

?>