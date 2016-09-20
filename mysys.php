<?
	date_default_timezone_set('Europe/Minsk');
	function myescape($string) {
		//checking if magic_quotes_gpc... is on
		if (!(bool) ini_get('magic_quotes_gpc')) {
		   $string = mysql_escape_string($string);
		}
		$string = htmlspecialchars($string);
		return $string;
	}
	function setContentType($type = 'text/html', $charset = 'utf8') {
		if (headers_sent() == false) {
			header("Content-type: ".$type.'; charset='.$charset);
		}
	}
	function write_file($fileName, $contents) {
		$fh = fopen($fileName, 'w');
		if (flock($fh, LOCK_EX)) {
			fwrite($fh, $contents);
			flock($fh, LOCK_UN);
		}
		fclose($fh);
	}
	function cleanUpCache($user) {
		foreach (glob('cache/*.js') as $filename) {
			if (strpos($filename, strtolower($user)) !== FALSE)
				unlink($filename);
		}
	}
	function myhtmlescape($arra) {
		$newarra=array();
		while (list($n, $v) = each($arra)) {
			if ((bool) ini_get('magic_quotes_gpc')) {
				$n = stripslashes($n); $v=stripslashes($v);
			}
			$newarra[mysql_escape_string(htmlspecialchars($n))] =
			mysql_escape_string(htmlspecialchars($v));
		}
		return $newarra;
	}
	function generate($len) {
	    $chars = "0123456789abcdefghijklmnopqrstuvwxyz-!@#$%^&*()ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	    srand((double)microtime()*1000000);
		for ($i = $len; $i>=0; $i--) {
			$pasw .= substr($chars, mt_rand(0,strlen($chars)), 1);
		}
		return substr(sha1($pasw), 0, $len);
	}
	function valid_email($email) {
	 	return (eregi( "^" .
           "[a-z0-9]+([_\\.-][a-z0-9]+)*" .    //user
           "@" .
           "([a-z0-9]+([\.-][a-z0-9]+)*)+" .   //domain
           "\\.[a-z]{2,}" .                    //sld, tld
           "$", $email));
	}
	function get_var($varname, $defvalue) {
	   return (isset($_POST[$varname]) ? $_POST[$varname] :
	   	  (isset($_GET[$varname]) ? $_GET[$varname]  : $defvalue));
	}
	function createThumbnail($src, $dst, $mw, $mh, $strictDimensions = false)
	{
		$size = @getImageSize($src);
		if (!$size || !($size[2]==1 || $size[2]==2 || $size[2]==3)) return false;
		switch ($size[2])
		{
			case 1: $type = 'gif'; break;
			case 2: $type = 'jpeg'; break;
			case 3: $type = 'png'; break;
		}
		if (isset($type))
		{
			switch ($type)
			{
				case 'jpeg': $sim = imageCreateFromJPEG($src); break;
				case 'gif' : $sim = imageCreateFromGIF($src); break;
				case 'png' : $sim = imageCreateFromPNG($src); break;
			}
			if ($size[0] <= $mw && $size[1] <= $mh)
			{
				$thumb = $sim;
			}
			else if ($size[0] > $mw || $size[1] > $mh)
			{
				$tw = $th = $dstX = $dstY = $srcX = $srcY = 0;
				$ratioX = ($size[0] / $mw);
				$ratioY = ($size[1] / $mh);
				$ratio = ($size[0] > $size[1]) ? $ratioX : $ratioY;
				if ($strictDimensions) {
					$tw = $mw;
					$th = $mh;
					if ($ratioY > $ratioX) {
						$srcY = intval(($size[1] - $mh * $ratioX) / 2);
					} else {
						$srcX = intval(($size[0] - $mw * $ratioY) / 2);
					}
				} else {
					$tw = $size[0] / $ratio;
					$th = $size[1] / $ratio;
				}
				$thumb = imageCreateTrueColor($tw, $th);
				imageCopyResampled($thumb, $sim, $dstX, $dstY, $srcX, $srcY, $tw, $th, $size[0] - $srcX*2, $size[1] - $srcY*2);
			}
			else $thumb = $sim;
			imageJPEG($thumb, $dst);
			return true;
		}
		return false;
	}
?>
