<?php
	error_reporting(E_ALL ^ E_DEPRECATED ^ E_NOTICE);
	session_start();
		
	// Start the output buffer
	require_once("Timer.php");
	$time = new Timer();
	
	// include ("gzdoc.php");	

	include("initdb.php");
    

	include("funcs.php");
	include_once "mytempl.php";
	include_once "mysys.php";

    $clr_answered = "#DDDDDD";
    $clr_nanswered = "#FFFFFF";
    $clr_author = "#ddffdd";

	$_users = array(); //array ID=>NICK

	srand((double)microtime()*1000000);


	//$vote_ans = myhtmlescape($_POST["questions"]);
  	$_POST = myhtmlescape($_POST);

	$isBossMode = isset($_GET['boss']);	

	/* login in? */
  	if (isset($_POST["login"]) && ($_POST["login"] == "in") ) { lohin(); }
	
  	/* login out? */
 	if (isset($_GET["logout"]))   { lohout($index);  }

	// if logged show him to the main page
  	if (!empty($_SESSION["user"]) and ($_SESSION["status"] == "loggedin")) {
        $main_page = "main.html";
		add_onliner($_SESSION["user"]["id"], $_SESSION["user"]["nick"]);
  	} else {
        $main_page = "nl.html";
  	}

	$index = new MyTemplate($main_page, "templates/");

 	if (isset($_SESSION["status"]) && ($_SESSION["status"] == "loggedin")) 
 	{
 		$isBossMode = (strtolower($_SESSION['user']['nick']) == 'lotas');
 		
		$act2 = get_var("action2", "none");
		$action = get_var("action", "none");
		$qid  = get_var("qid", "none");

    	switch ($act2) 
    	{

     		case "vote": 
     				vote_for_it();
                    $index->define(array("ANYSTATUS" => "Спасибо за голосование."));
                    break;
     		case "rm": 
     				if (isset($qid)) {
                    	$qd = del_question($qid, $_SESSION["user"]["id"]);
                    	$index->define(array("ANYSTATUS" => "Successfully deleted."));
                    }
                    break;
    	}

  		switch ($action)
  		{
  			case "bug":
  					add_bug($index);
  					redirect('kontra.php?action=bugadded');
  					break;
  			
  			case "viewbugs":
  					view_bugs($index);  					
  					break;
  					
  			case "bugadded":
  					$index->define(array("ANYSTATUS" => "Спасибо за ваше сообщение, рассмотриться как-нибудь..."));
  					break;
  			
  			case "kontra":
  					$aid = get_var("aid", 0);
					add_kontra_answer($qid, $aid);
  					redirect('kontra.php?action=read&qid='.$qid);
  					break;

			case "answers":  
		   			answers_part($index, $qid);
                     break;

                    /* now answering follows reading */
			case "read" : 
					read_part($index, $qid);
                    answer_question($index, $qid);                    
                    break;

			case "vote" : vote_for_it();
			                $index->define(array("HEADTITLE" => "Голосование"));
			                break;
			
			case "ans" :  answer_question($index, $qid);
			                break;
			
			case "ask" : $q = get_var("q", ".");
			            if (($q != ".") /* and (q_asked() < 5)*/) {
			                  ask_question($index, $q);
			              }
			              break;
			
			case "browse" : browsem($index);
			                $index->define(array("HEADTITLE" => "Просмотр"));
			               break;
			
			case "setup" : setup($index);
			                $index->define(array("HEADTITLE" => "Настройки"));
			                break;
			
			case "stats" : statistics($index);
			                $index->define(array("HEADTITLE" => "Статистика"));
			                break;
			
			case "chat" :  chat($index);
			                 break;
			                 
			case "profile":
							$userNick = get_var('user');
				        $index->conditional("SHOWPROFILE", true);
			
							$index->define(array("HEADTITLE" => "Просмотр профиля: ".$userNick, 
								"PROFILEUSER" => $userNick));
							show_profile($index, $userNick);
							break;
			
			case "top5" :
			    default:  top5_part($index);
			              break;
			
	   	}//switch action
	
		generator($index);
		update_user_stats();
	
		$index->define(array("QUESTIONSALL" => update_questions("") ));
		
		$index->define(array("APPEAL" => get_rand_appeal()));
	
	
		/* right top corner stats */
	    login_off_box($index);
	    
	} // end if logged in part

  	$index->parse();
  	$index->dump();
  	
  	if ($isBossMode) {
  		echo '<pre style="display:none">';	
  		//echo '<pre>';
  		print_r($_POST);
		echo 'Total time: ' . $time->split() . " msec\n";
		print_r($log);
		echo '</pre>';
	}	
  
  	/* !!!!!!!!!!!!! email newsletter part !!!!!!!!! */
	// newsletter($index);

    global $con;
  	mysql_close($con);

    // GzDocOut();   	
?>