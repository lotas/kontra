<? 
/*************************************** 
** Title.........: PHP4 HTTP Compression Speeds up the Web 
** Version.......: 1.10 
** Author........: catoc <catoc@163.net> 
** Filename......: gzdoc.php 
** Last changed..: 25/08/2000 
** Requirments...: PHP4 >= 4.0.1 
**                 PHP was configured with --with-zlib[=DIR] 
** Notes.........: Dynamic Content Acceleration compresses 
**                 the data transmission data on the fly 
**                 code by sun jin hu (catoc) <catoc@163.net> 
**                 Most newer browsers since 1998/1999 have 
**                 been equipped to support the HTTP 1.1 
**                 standard known as "content-encoding." 
**                 Essentially the browser indicates to the 
**                 server that it can accept "content encoding" 
**                 and if the server is capable it will then 
**                 compress the data and transmit it. The 
**                 browser decompresses it and then renders 
**                 the page. 
** Useage........: 
**                 No space before the beginning of the first '<?' tag. 
**                 ------------Start of file---------- 
**                 |<? 
**                 | include('gzdoc.php'); 
**                 | print "Start output !!"; 
**                 |?> 
**                 |<HTML> 
**                 |... the page ... 
**                 |</HTML> 
**                 |<? 
**                 | gzdocout(); 
**                 |?> 
**                 -------------End of file----------- 
***************************************/ 
ob_start(); 
ob_implicit_flush(0); 
function GetHeader(){ 
    $headers = getallheaders(); 
    while (list($header, $value) = each($headers)) { 
        $Message .= "$header: $value<br>\n"; 
    } 
    return $Message; 
} 
function CheckCanGzip(){ 
    global $HTTP_ACCEPT_ENCODING, $PHP_SELF, $Wget, $REMOTE_ADDR, $S_UserName; 
//    if (connection_timeout() || connection_aborted()){ 
//        return 0; 
//    } 
    if ((strpos('catoc'.$HTTP_ACCEPT_ENCODING, 'gzip')) || $Wget == 'Y'){ 
        if (strpos('catoc'.$HTTP_ACCEPT_ENCODING, 'x-gzip')){ 
            $ENCODING = "x-gzip"; 
            $Error_Msg = str_replace('<br>','',GetHeader()); 
            $Error_Msg .= "Time: ".date("Y-m-d H:i:s")."\n"; 
            $Error_Msg .= "Remote-Address: ".$REMOTE_ADDR."\n"; 
            //mail('your@none.net', "User have x-gzip output in file $PHP_SELF!!!", $Error_Msg); 
        }else{ 
            $ENCODING = "gzip"; 
        } 
        return $ENCODING; 
    }else{ 
        return 0; 
    } 
} 
function GzDocOut(){ 
    global $PHP_SELF, $CatocGz, $REMOTE_ADDR, $S_UserName; 
    $ENCODING = CheckCanGzip(); 
    if ($ENCODING){ 
        print "\n<!-- Use compress $ENCODING -->\n"; 
        $Contents = ob_get_contents(); 
        ob_end_clean(); 
        if ($CatocGz == 'Y'){ 
            print "Not compress lenth: ".strlen($Contents)."<BR>"; 
            print "Compressed lenth: ".strlen(gzcompress($Contents))."<BR>"; 
            exit; 
        }else{ 
            header("Content-Encoding: $ENCODING"); 
        } 
        print pack('cccccccc',0x1f,0x8b,0x08,0x00,0x00,0x00,0x00,0x00); 
        $Size = strlen($Contents); 
        $Crc = crc32($Contents); 
        $Contents = gzcompress($Contents); 
        $Contents = substr($Contents, 0, strlen($Contents) - 4); 
        print $Contents; 
        print pack('V',$Crc); 
        print pack('V',$Size); 
        exit; 
    }else{ 
        ob_end_flush(); 
        $Error_Msg = str_replace('<br>','',GetHeader()); 
        $Error_Msg .= "Time: ".date("Y-m-d H:i:s")."\n"; 
        $Error_Msg .= "Remote-Address: ".$REMOTE_ADDR."\n"; 
        //mail('your@none.net', "User can not use gzip output in file $PHP_SELF!!!", $Error_Msg); 
        exit; 
    } 
} 
?> 