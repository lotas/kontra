<?
 /*
     MyTemplates.inc.php

     Version: 1.5
     (
      with dynamic blocks 22.08.2003
      with conditional blocks 23.08.2003

      with sessions ID fix (add PHPSESSID to URLs on pages that don't support cookies)

      with direct file inclusion directive <!--INCFILE "..."//--> 
     
     )
     
     26.09.2003 (c) Lotas

     A simple, robust class for HTML (not only) templates

  */


/*
   todo:
 [done] dynamic pages (rows in tables.. something else parsed independantly)
 [done] conditional blocks in page

 [no bugs detected]  - fix bugs :-)
*/

  class MyTemplate {

       var $DIR;		//were templates reside
       var $FILES = array();    //array in form 'templname'=>'filename'
       var $VARS = array();	//array of substitutes
       var $CONTENTS;		//big string representing whole page
//     var $PARSED;		//big string = parsed page
       var $IS_PARSED = false;
       var $DYNAMIC = array();  // holds the dynamic blocks
       var $patt = array();	// array of replace patterns for includefiles
       var $cont = array();	// array of files to be included
       var $CONDITIONALS = array();	// conditional blocks
       var $ADDSID = false;	//add or not session id's

       
       // creating with main template and basedir if any
       function MyTemplate($main = "", $basedir=".") {
       		$this->setdir($basedir);
       		$this->FILES["main"] = $this->DIR.$main;

	  //if user doesn't want cookies than we add PHPSESSID to links
	//  if (!isset($_COOKIE["help"])) {$this->addsid(true); }


         //reading file
                $this->CONTENTS = file_get_contents($this->FILES["main"]);
                if ((!$this->CONTENTS) or (empty($this->CONTENTS))) {
                 	//echo "parse error\n";
                 	return false;
                }

       }

       function setdir($dir = "") {

		if (!preg_match("#/$#",$dir)) {
		 	$dir .= '/';
		}
		$this->DIR = $dir;
       }

       // assigning template filenames
       function assign($files) {
                while(list($tname, $tfile) = each($files)) {
                 	$this->FILES["$tname"] = $this->DIR.$tfile;
                }

                return true;
       }

       // defining placeholder values
       function define($vars) {
        	while (list($vname, $vval) = each($vars)) {
        		$this->VARS["$vname"] = $vval;
        	}
        	return true;
       }

       // reading files, including files, replacing placeholders

       function parse($targets="") {
       
        //searching for includes
        	$INCEXP = "#(<!--\\s*INCLUDE\\s*{([A-Z0-9_]+)}\\s*//-->)#ims";
        	if (preg_match_all($INCEXP,$this->CONTENTS, $includes)) {
        	  //print_r($includes);
        	 //including whole file
        	   for ($i=0; $i < count($includes[1]); $i++) {
        	   // substitute patterns and files to change
        	       $this->patt[$i] = "#(".$includes[1][$i].")#ims";

        	       if (!empty($this->FILES[$includes[2][$i]])) {
        	        $this->cont[$i] = file_get_contents($this->FILES[$includes[2][$i]]);
        	       } else {
        	        $this->cont[$i] = "";
        	       }
        	   }

        	   $this->CONTENTS = preg_replace($this->patt, 
		      	   $this->cont, $this->CONTENTS);
		
        	}

        	//including files ommiting MyTemplate->assign
        	$INC2EXP = "#(<!--\\s*INCFILE\\s*\"([/A-Z0-9_\.]+)\"\\s*//-->)#imse";

        	$this->CONTENTS = preg_replace($INC2EXP, 
       		"file_get_contents('$this->DIR\\2')", $this->CONTENTS); 

       		//removing all unused dynamic blocks
$DYNEXP =  "#<!--\\s*DYNAMIC BEGIN ([A-Za-z0-9]+)\\s*//-->(.+)<!--\\s*DYNAMIC END \\1\\s*//-->#ms";
		$this->CONTENTS = preg_replace($DYNEXP, "", $this->CONTENTS);


        	//removing/adding conditional blocks
 	        $this->conditional_real();
                //parsing {VARS}
                $this->CONTENTS = @preg_replace("/{([A-Z0-9_]+)}/imse", "\$this->VARS['\\1']", $this->CONTENTS);

                //session is fu..ed up?
                if ($this->ADDSID) {
                   $this->addsid_real();
                }

                $this->IS_PARSED = true;
                return true;
       }


//     $a->dynamic("INFO", array(ID => $row["id"], NAME => $row["name"]));
/*
    starting a dynamic block named $macro and replacing $vars
*/
       function dynamic($macro, $vars) {
       //if not defined yet, reading from file and doing something
$DYNEXP = 
 "#<!--\\s*DYNAMIC BEGIN $macro\\s*//-->(.+)<!--\\s*DYNAMIC END $macro\\s*//-->#ms";

                if (!isset($this->DYNAMIC[$macro])) {
                   preg_match($DYNEXP, $this->CONTENTS, $dynblock);

                   $this->DYNAMIC[$macro] = $dynblock[1];
                // $dynblock[1] - whole dynamic block
                // $dynblock[2] - the part to be repeated all over again

                // replacing in contents this whole part with special macro {macro}
                // so it will be parsed with parse() correctly and inplace
                   $sp = "{".$macro."}";
                   $this->CONTENTS = preg_replace($DYNEXP, $sp, $this->CONTENTS);
                }

                if (!isset($this->VARS["$macro"])) {$this->VARS["$macro"]="";}

                $this->VARS["$macro"] .=
                   preg_replace("/{([A-Z0-9_]+)}/imse", "\$vars['\\1']", $this->DYNAMIC[$macro]);

       }


       // if $macro defined as true, than including block
       // rejecting it otherwise
       function conditional($macro, $state) {
        	$this->CONDITIONALS[$macro] = $state;
       }

       function conditional_real() {

	while (list($macro, $state) = each($this->CONDITIONALS)) {

  	$CEXP = 
"#<!--\\s*IF\\s*$macro\\s*BEGIN\\s*//-->(.+)<!--\\s*IF\\s*$macro\\s*END\\s*//-->#ms";
		$repl = ($state) ? "\\1" : "\n";
		$this->CONTENTS = @preg_replace($CEXP, $repl, $this->CONTENTS);
	}

	//dropping all unmentioned blocks
	
	$this->CONTENTS = preg_replace(
"#<!--\\s*IF\\s*([^>]+)\\s*BEGIN\\s*//-->(.+)<!--\\s*IF\\s*(\\1)\\s*END\\s*//-->#ms", 
		"\n", 
		$this->CONTENTS);

	return true;
       }


       //outputting page to the browser (whatever)
       function dump() {
       		print $this->CONTENTS;
       }

       //returning parsed(if so) template string
       function template() {
        	return $this->CONTENTS;
       }


	// adding $sid to links
       function addsid($state) {
       		$this->ADDSID = true; 	
       }

       function addsid_real() {
       		$mySID = session_name().'='.session_id();
$search = array(
//   "'(<a[^>]*href=\"(?!http://|https://|ftp://|mailto:)[^?\">]*\\?[^\">]*)\"'iU",
//   "'(<a[^>]*href=\"(?!http://|https://|ftp://|mailto:)[^?\">]*)\"'iU",
   "'(<form[^>]*action=\"(?!http://|https://|ftp://|mailto:)[^?\">]*\\?[^\">]*)\"'iU",
   "'(<form[^>]*action=\"(?!http://|https://|ftp://|mailto:)[^?\">]*)\"'iU"
   );
$replace = array(
//   '\\1&'.$mySID.'"',
//   '\\1?'.$mySID.'"',
   '\\1&'.$mySID.'"',
   '\\1?'.$mySID.'"');
		$this->CONTENTS = preg_replace($search, $replace, $this->CONTENTS);

       		return true;
       }


       function debug() {
       		echo "<pre style='color:gray'>----Start Debug Info---\n";
        	echo "\$this->DIR = ".$this->DIR."\n";
/*        	echo "\$this->CONTENTS = ".$this->CONTENTS."\n";
          	echo "Files:\n";
        	while (list($t, $l) = each($this->FILES)) {
        	 	echo "\$this->FILES['$t'] = $l\n";
        	}
 */
        	echo "Vars:\n";
        	while (list($t, $l) = each($this->VARS)) {
        	 	echo "\$this->VARS['$t'] = $l\n";
        	}

        	echo "Dynamic blocks:\n";
        	while (list($t, $l) = each($this->DYNAMIC)) {
        	 	echo "\$this->DYNAMIC['$t'] = $l\n";
        	}

        	echo "Conditionals:\n";
//        	while (list($t, $l) = each($this->CONDITIONALS)){
//			echo "\$this->CONDITIONALS['$t'] = $l\n";
//        	}
		print_r($this->CONDITIONALS);
		
		echo "POST/GET/SESSION vars:\n";
		print_r($_GET);
		print_r($_POST);
		print_r($_SESSION);

        	echo "-----End Debug Info----\n</pre>";
        	return true;
       }


  }



/*
   How to use:
   	
   	$templ = new MyTemplate($main, $basedir);
   	$templ->assign( array( "MENU" => "dd.html"...));
   	$templ->define( array("VAR1" => "value1", "VAR2" => "value2"));
   [	
   	$templ->dynamic("DYNAMICBLOCK", array(var1=>value1, var2=>value2));
   ]     
   [
    	$templ->conditional("CONDITBLOCK", true|false);
   ]
        $templ->parse();

        $templ->dump();	//printing out

*/




?>


