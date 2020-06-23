<?php
function phone_letter_to_number($tmp) {
	$tmp = strtolower($tmp);
	if ($tmp == "a" | $tmp == "b" | $tmp == "c") { return 2; }
	if ($tmp == "d" | $tmp == "e" | $tmp == "f") { return 3; }
	if ($tmp == "g" | $tmp == "h" | $tmp == "i") { return 4; }
	if ($tmp == "j" | $tmp == "k" | $tmp == "l") { return 5; }
	if ($tmp == "m" | $tmp == "n" | $tmp == "o") { return 6; }
	if ($tmp == "p" | $tmp == "q" | $tmp == "r" | $tmp == "s") { return 7; }
	if ($tmp == "t" | $tmp == "u" | $tmp == "v") { return 8; }
	if ($tmp == "w" | $tmp == "x" | $tmp == "y" | $tmp == "z") { return 9; }
}

function download($path,$file) {
		//download the file
		if (file_exists($path."/".$file)) {
			//content-range
			//if (isset($_SERVER['HTTP_RANGE']))  {
			//	range_download($record_file);
			//}
			ob_clean();
			$fd = fopen($path."/".$file, "rb");
			if ($_GET['t'] == "bin") {
				header("Content-Type: application/force-download");
				header("Content-Type: application/octet-stream");
				header("Content-Type: application/download");
				header("Content-Description: File Transfer");
			}
			else {
				$file_ext = substr($file, -3);
				if ($file_ext == "wav") {
					header("Content-Type: audio/x-wav");
				}
				if ($file_ext == "mp3") {
					header("Content-Type: audio/mpeg");
				}
				if ($file_ext == "ogg") {
					header("Content-Type: audio/ogg");
				}
			}
			header('Content-Disposition: attachment; filename="'.$file.'"');
			header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
			header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
			// header("Content-Length: " . filesize($record_file));
			ob_clean();
			fpassthru($fd);
		}
} 

function check_str($string, $trim = true) {
	global $db_type, $db;
	//when code in db is urlencoded the ' does not need to be modified
	if ($db_type == "sqlite") {
		if (function_exists('sqlite_escape_string')) {
			$string = sqlite_escape_string($string);
		}
		else {
			$string = str_replace("'","''",$string);
		}
	}
	if ($db_type == "pgsql") {
		$string = pg_escape_string($string);
	}
	if ($db_type == "mysql") {
		if(function_exists('mysql_real_escape_string')){
			$tmp_str = mysql_real_escape_string($string);
		}
		else{
			$tmp_str = mysqli_real_escape_string($db, $string);
		}
		if (strlen($tmp_str)) {
			$string = $tmp_str;
		}
		else {
			$search = array("\x00", "\n", "\r", "\\", "'", "\"", "\x1a");
			$replace = array("\\x00", "\\n", "\\r", "\\\\" ,"\'", "\\\"", "\\\x1a");
			$string = str_replace($search, $replace, $string);
		}
	}
	$string = ($trim) ? trim($string) : $string;
	return $string;
}

//check_cidr($cidr, $_SERVER['REMOTE_ADDR'])
function check_cidr ($cidr,$ip_address) {
	list ($subnet, $mask) = explode ('/', $cidr);
	return ( ip2long ($ip_address) & ~((1 << (32 - $mask)) - 1) ) == ip2long ($subnet);
}

function uuid() {
	//uuid version 4
	return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			// 32 bits for "time_low"
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
			
			// 16 bits for "time_mid"
			mt_rand( 0, 0xffff ),
			
			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			mt_rand( 0, 0x0fff ) | 0x4000,
			
			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			mt_rand( 0, 0x3fff ) | 0x8000,
			
			// 48 bits for "node"
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
			);
}

	function is_uuid($uuid) {
		$regex = '/^[0-9A-F]{8}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{4}-[0-9A-F]{12}$/i';
		return preg_match($regex, $uuid);
	}
	
	if (!function_exists('recursive_copy')) {
		if (file_exists('/bin/cp')) {
			function recursive_copy($src, $dst, $options = '') {
				if (strtoupper(substr(PHP_OS, 0, 3)) === 'SUN') {
					//copy -R recursive, preserve attributes for SUN
					$cmd = 'cp -Rp '.$src.'/* '.$dst;
				} else {
					//copy -R recursive, -L follow symbolic links, -p preserve attributes for other Posix systemss
					$cmd = 'cp -RLp '.$options.' '.$src.'/* '.$dst;
				}
				//$this->write_debug($cmd);
				exec ($cmd);
			}
		} elseif(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			function recursive_copy($src, $dst, $options = '') {
				$src = normalize_path_to_os($src);
				$dst = normalize_path_to_os($dst);
				exec("xcopy /E /Y \"$src\" \"$dst\"");
			}
		} else {
			function recursive_copy($src, $dst, $options = '') {
				$dir = opendir($src);
				if (!$dir) {
					throw new Exception("recursive_copy() source directory '".$src."' does not exist.");
				}
				if (!is_dir($dst)) {
					if (!mkdir($dst,02770,true)) {
						throw new Exception("recursive_copy() failed to create destination directory '".$dst."'");
					}
				}
				while(false !== ( $file = readdir($dir)) ) {
					if (( $file != '.' ) && ( $file != '..' )) {
						if ( is_dir($src . '/' . $file) ) {
							recursive_copy($src . '/' . $file,$dst . '/' . $file);
						}
						else {
							copy($src . '/' . $file,$dst . '/' . $file);
						}
					}
				}
				closedir($dir);
			}
		}
	}
	
	if (!function_exists('recursive_delete')) {
		if (file_exists('/bin/rm')) {
			function recursive_delete($dir) {
				//$this->write_debug('rm -Rf '.$dir.'/*');
				exec ('rm -Rf '.$dir.'/*');
				clearstatcache();
			}
		}elseif(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'){
			function recursive_delete($dir) {
				$dst = normalize_path_to_os($dst);
				//$this->write_debug("del /S /F /Q \"$dir\"");
				exec("del /S /F /Q \"$dir\"");
				clearstatcache();
			}
		}else{
			function recursive_delete($dir) {
				foreach (glob($dir) as $file) {
					if (is_dir($file)) {
						//$this->write_debug("rm dir: ".$file);
						recursive_delete("$file/*");
						rmdir($file);
					} else {
						//$this->write_debug("delete file: ".$file);
						unlink($file);
					}
				}
				clearstatcache();
			}
		}
	}
	
	if ( !function_exists('normalize_path')) {
		//don't use DIRECTORY_SEPARATOR as it will change on a per platform basis and we need consistency
		function normalize_path($path) {
			return str_replace(array('/','\\'), '/', $path);
		}
	}
	
	if ( !function_exists('normalize_path_to_os')) {
		function normalize_path_to_os($path) {
			return str_replace(array('/','\\'), DIRECTORY_SEPARATOR, $path);
		}
	}
	
	//generate a random password with upper, lowercase and symbols
	function generate_password($length = 0, $strength = 0) {
		$password = '';
		$charset = '';
		if ($length === 0 && $strength === 0) { //set length and strenth if specified in default settings and strength isn't numeric-only
			$length = (is_numeric($_SESSION["security"]["password_length"]["numeric"])) ? $_SESSION["security"]["password_length"]["numeric"] : 10;
			$strength = (is_numeric($_SESSION["security"]["password_strength"]["numeric"])) ? $_SESSION["security"]["password_strength"]["numeric"] : 4;
		}
		if ($strength >= 1) { $charset .= "0123456789"; }
		if ($strength >= 2) { $charset .= "abcdefghijkmnopqrstuvwxyz";	}
		if ($strength >= 3) { $charset .= "ABCDEFGHIJKLMNPQRSTUVWXYZ";	}
		if ($strength >= 4) { $charset .= "!!!!!^$%*?....."; }
		srand((double)microtime() * rand(1000000, 9999999));
		while ($length > 0) {
			$password .= $charset[rand(0, strlen($charset)-1)];
			$length--;
		}
		return $password;
	}
	
	// validate email address syntax
	if(!function_exists('valid_email')) {
		function valid_email($email) {
			$regex = '/^[A-z0-9][\w.-]*@[A-z0-9][\w\-\.]+(\.[A-z0-9]{2,6})?$/';
			if ($email != "" && preg_match($regex, $email) == 1) {
				return true; // email address has valid syntax
			}
			else {
				return false; // email address does not have valid syntax
			}
		}
	}
	
	// ellipsis nicely truncate long text
	if(!function_exists('ellipsis')) {
		function ellipsis($string, $max_characters, $preserve_word = true) {
			if ($max_characters >= strlen($string)) { return $string; }
			if ($preserve_word) {
				for ($x = 0; $x < strlen($string); $x++) {
					if ($string{$max_characters+$x} == " ") {
						return substr($string,0,$max_characters+$x)." ...";
					}
					else { continue; }
				}
			}
			else {
				return substr($string,0,$max_characters)." ...";
			}
		}
	}
	
	//function to convert hexidecimal color value to rgb string/array value
	if (!function_exists('hex_to_rgb')) {
		function hex_to_rgb($hex, $delim = '') {
			$hex = str_replace("#", "", $hex);
			
			if (strlen($hex) == 3) {
				$r = hexdec(substr($hex,0,1).substr($hex,0,1));
				$g = hexdec(substr($hex,1,1).substr($hex,1,1));
				$b = hexdec(substr($hex,2,1).substr($hex,2,1));
			}
			else {
				$r = hexdec(substr($hex,0,2));
				$g = hexdec(substr($hex,2,2));
				$b = hexdec(substr($hex,4,2));
			}
			$rgb = array($r, $g, $b);
			
			if ($delim != '') {
				return implode($delim, $rgb); // return rgb delimited string
			}
			else {
				return $rgb; // return array of rgb values
			}
		}
	}
	
	//function to get a color's luminence level -- dependencies: rgb_to_hsl()
	if (!function_exists('get_color_luminence')) {
		function get_color_luminence($color) {
			//convert hex to rgb
			if (substr_count($color, ',') == 0) {
				$color = str_replace(' ', '', $color);
				$color = str_replace('#', '', $color);
				if (strlen($color) == 3) {
					$r = hexdec(substr($color,0,1).substr($color,0,1));
					$g = hexdec(substr($color,1,1).substr($color,1,1));
					$b = hexdec(substr($color,2,1).substr($color,2,1));
				}
				else {
					$r = hexdec(substr($color,0,2));
					$g = hexdec(substr($color,2,2));
					$b = hexdec(substr($color,4,2));
				}
				$color = $r.','.$g.','.$b;
			}
			
			//color to array, pop alpha
			if (substr_count($color, ',') > 0) {
				$color = str_replace(' ', '', $color);
				$color = str_replace('rgb', '', $color);
				$color = str_replace('a(', '', $color);
				$color = str_replace(')', '', $color);
				$color = explode(',', $color);
				$hsl = rgb_to_hsl($color[0], $color[1], $color[2]);
			}
			
			//return luminence value
			return (is_array($hsl) && is_numeric($hsl[2])) ? $hsl[2] : null;
		}
	}
	
	//function to lighten or darken a hexidecimal, rgb, or rgba color value by a percentage -- dependencies: rgb_to_hsl(), hsl_to_rgb()
	if (!function_exists('color_adjust')) {
		function color_adjust($color, $percent) {
			/*
			 USAGE
			 20% Lighter
			 color_adjust('#3f4265', 0.2);
			 color_adjust('234,120,6,0.3', 0.2);
			 20% Darker
			 color_adjust('#3f4265', -0.2); //
			 color_adjust('rgba(234,120,6,0.3)', -0.2);
			 RETURNS
			 Same color format provided (hex in = hex out, rgb(a) in = rgb(a) out)
			 */
			
			//convert hex to rgb
			if (substr_count($color, ',') == 0) {
				$color = str_replace(' ', '', $color);
				if (substr_count($color, '#') > 0) {
					$color = str_replace('#', '', $color);
					$hash = '#';
				}
				if (strlen($color) == 3) {
					$r = hexdec(substr($color,0,1).substr($color,0,1));
					$g = hexdec(substr($color,1,1).substr($color,1,1));
					$b = hexdec(substr($color,2,1).substr($color,2,1));
				}
				else {
					$r = hexdec(substr($color,0,2));
					$g = hexdec(substr($color,2,2));
					$b = hexdec(substr($color,4,2));
				}
				$color = $r.','.$g.','.$b;
			}
			
			//color to array, pop alpha
			if (substr_count($color, ',') > 0) {
				$color = str_replace(' ', '', $color);
				$wrapper = false;
				if (substr_count($color, 'rgb') != 0) {
					$color = str_replace('rgb', '', $color);
					$color = str_replace('a(', '', $color);
					$color = str_replace(')', '', $color);
					$wrapper = true;
				}
				$colors = explode(',', $color);
				$alpha = (sizeof($colors) == 4) ? array_pop($colors) : null;
				$color = $colors;
				unset($colors);
				
				//adjust color using rgb > hsl > rgb conversion
				$hsl = rgb_to_hsl($color[0], $color[1], $color[2]);
				$hsl[2] = $hsl[2] + $percent;
				$color = hsl_to_rgb($hsl[0], $hsl[1], $hsl[2]);
				
				//return adjusted color in format received
				if ($hash == '#') { //hex
					for ($i = 0; $i <= 2; $i++) {
						$hex_color = dechex($color[$i]);
						if (strlen($hex_color) == 1) { $hex_color = '0'.$hex_color; }
						$hex .= $hex_color;
					}
					return $hash.$hex;
				}
				else { //rgb(a)
					$rgb = implode(',', $color);
					if ($alpha != '') { $rgb .= ','.$alpha; $a = 'a'; }
					if ($wrapper) { $rgb = 'rgb'.$a.'('.$rgb.')'; }
					return $rgb;
				}
			}
			
			return $color;
		}
	}
	
	//function to convert an rgb color array to an hsl color array
	if (!function_exists('rgb_to_hsl')) {
		function rgb_to_hsl($r, $g, $b) {
			$r /= 255;
			$g /= 255;
			$b /= 255;
			
			$max = max($r, $g, $b);
			$min = min($r, $g, $b);
			
			$l = ($max + $min) / 2;
			$d = $max - $min;
			
			if ($d == 0) {
				$h = $s = 0; // achromatic
			}else {
				$s = $d / (1 - abs((2 * $l) - 1));
				switch($max){
					case $r:
						$h = 60 * fmod((($g - $b) / $d), 6);
						if ($b > $g) { $h += 360; }
						break;
					case $g:
						$h = 60 * (($b - $r) / $d + 2);
						break;
					case $b:
						$h = 60 * (($r - $g) / $d + 4);
						break;
				}
			}
			
			return array(round($h, 2), round($s, 2), round($l, 2));
		}
	}
	
	//function to convert an hsl color array to an rgb color array
	if (!function_exists('hsl_to_rgb')) {
		function hsl_to_rgb($h, $s, $l){
			$c = (1 - abs((2 * $l) - 1)) * $s;
			$x = $c * (1 - abs(fmod(($h / 60), 2) - 1));
			$m = $l - ($c / 2);
			
			if ($h < 60) {
				$r = $c;
				$g = $x;
				$b = 0;
			}
			else if ($h < 120) {
				$r = $x;
				$g = $c;
				$b = 0;
			}
			else if ($h < 180) {
				$r = 0;
				$g = $c;
				$b = $x;
			}
			else if ($h < 240) {
				$r = 0;
				$g = $x;
				$b = $c;
			}
			else if ($h < 300) {
				$r = $x;
				$g = 0;
				$b = $c;
			}
			else {
				$r = $c;
				$g = 0;
				$b = $x;
			}
			
			$r = ($r + $m) * 255;
			$g = ($g + $m) * 255;
			$b = ($b + $m) * 255;
			
			if ($r > 255) { $r = 255; }
			if ($g > 255) { $g = 255; }
			if ($b > 255) { $b = 255; }
			
			if ($r < 0) { $r = 0; }
			if ($g < 0) { $g = 0; }
			if ($b < 0) { $b = 0; }
			
			return array(floor($r), floor($g), floor($b));
		}
	}
	
	//encrypt a string
	if (!function_exists('encrypt')) {
		function encrypt($key, $str_to_enc) {
			return base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($key), $str_to_enc, MCRYPT_MODE_CBC, md5(md5($key))));
		}
	}
	
	//decrypt a string
	if (!function_exists('decrypt')) {
		function decrypt($key, $str_to_dec) {
			return rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($key), base64_decode($str_to_dec), MCRYPT_MODE_CBC, md5(md5($key))), "\0");
		}
	}
	
	//json detection
	if (!function_exists('is_json')) {
		function is_json($str) {
			return (is_string($str) && is_object(json_decode($str))) ? true : false;
		}
	}
	
	//mac detection
	if (!function_exists('is_mac')) {
		function is_mac($str) {
			return (preg_match('/([a-fA-F0-9]{2}[:|\-]?){6}/', $str) == 1) ? true : false;
		}
	}
	
	//format mac address
	if (!function_exists('format_mac')) {
		function format_mac($str, $delim = '-', $case = 'lower') {
			if (is_mac($str)) {
				$str = join($delim, str_split($str, 2));
				$str = ($case == 'upper') ? strtoupper($str) : strtolower($str);
			}
			return $str;
		}
	}
	
	//transparent gif
	if (!function_exists('img_spacer')) {
		function img_spacer($width = '1px', $height = '1px', $custom = null) {
			return "<img src='data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7' style='width: ".$width."; height: ".$height."; ".$custom."'>";
		}
	}
	
	//lower case
	function lower_case($string) {
		if (function_exists('mb_strtolower')) {
			return mb_strtolower($string, 'UTF-8');
		}
		else {
			return strtolower($string);
		}
	}
	
	//upper case
	function upper_case($string) {
		if (function_exists('mb_strtoupper')) {
			return mb_strtoupper($string, 'UTF-8');
		}
		else {
			return strtoupper($string);
		}
	}
	
	//email validate
	if (!function_exists('email_validate')) {
		function email_validate($strEmail){
			$validRegExp =  '/^[a-zA-Z0-9\._-]+@[a-zA-Z0-9\._-]+\.[a-zA-Z]{2,3}$/';
			// search email text for regular exp matches
			preg_match($validRegExp, $strEmail, $matches, PREG_OFFSET_CAPTURE);
			if (count($matches) == 0) {
				return 0;
			}
			else {
				return 1;
			}
		}
	}
	
	//write javascript function that detects select key combinations to perform designated actions
	if (!function_exists('key_press')) {
		function key_press($key, $direction = 'up', $subject = 'document', $exceptions = array(), $prompt = null, $action = null, $script_wrapper = true) {
			//determine key code
			switch (strtolower($key)) {
				case 'escape':
					$key_code = '(e.which == 27)';
					break;
				case 'delete':
					$key_code = '(e.which == 46)';
					break;
				case 'enter':
					$key_code = '(e.which == 13)';
					break;
				case 'backspace':
					$key_code = '(e.which == 8)';
					break;
				case 'ctrl+s':
					$key_code = '(((e.which == 115 || e.which == 83) && (e.ctrlKey || e.metaKey)) || (e.which == 19))';
					break;
				case 'ctrl+q':
					$key_code = '(((e.which == 113 || e.which == 81) && (e.ctrlKey || e.metaKey)) || (e.which == 19))';
					break;
				case 'ctrl+a':
					$key_code = '(((e.which == 97 || e.which == 65) && (e.ctrlKey || e.metaKey)) || (e.which == 19))';
					break;
				case 'ctrl+enter':
					$key_code = '(((e.which == 13 || e.which == 10) && (e.ctrlKey || e.metaKey)) || (e.which == 19))';
					break;
				default:
					return;
			}
			//check for element exceptions
			if (sizeof($exceptions) > 0) {
				$exceptions = "!$(e.target).is('".implode(',', $exceptions)."') && ";
			}
			//quote if selector is id or class
			$subject = ($subject != 'window' && $subject != 'document') ? "'".$subject."'" : $subject;
			//output script
			echo "\n\n\n";
			if ($script_wrapper) {
				echo "<script language='JavaScript' type='text/javascript'>\n";
			}
			echo "	$(".$subject.").key".$direction."(function(e) {\n";
			echo "		if (".$exceptions.$key_code.") {\n";
			if ($prompt != '') {
				$action = ($action != '') ? $action : "alert('".$key."');";
				echo "			if (confirm('".$prompt."')) {\n";
				echo "				e.preventDefault();\n";
				echo "				".$action."\n";
				echo "			}\n";
			}
			else {
				echo "			e.preventDefault();\n";
				echo "			".$action."\n";
			}
			echo "		}\n";
			echo "	});\n";
			if ($script_wrapper) {
				echo "</script>\n";
			}
			echo "\n\n\n";
		}
	}
	
	//converts a string to a regular expression
	if (!function_exists('string_to_regex')) {
		function string_to_regex($string) {
			//escape the plus
			if (substr($string, 0, 1) == "+") {
				$string = "^\\+(".substr($string, 1).")$";
			}
			//convert N,X,Z syntax to regex
			$string = str_ireplace("N", "[2-9]", $string);
			$string = str_ireplace("X", "[0-9]", $string);
			$string = str_ireplace("Z", "[1-9]", $string);
			//add ^ to the start of the string if missing
			if (substr($string, 0, 1) != "^") {
				$string = "^".$string;
			}
			//add $ to the end of the string if missing
			if (substr($string, -1) != "$") {
				$string = $string."$";
			}
			//add the round brackgets ( and )
			if (!strstr($string, '(')) {
				if (strstr($string, '^')) {
					$string = str_replace("^", "^(", $string);
				}
				else {
					$string = '^('.$string;
				}
			}
			if (!strstr($string, ')')) {
				if (strstr($string, '$')) {
					$string = str_replace("$", ")$", $string);
				}
				else {
					$string = $string.')$';
				}
			}
			//return the result
			return $string;
		}
		//$string = "+12089068227"; echo $string." ".string_to_regex($string)."\n";
		//$string = "12089068227"; echo $string." ".string_to_regex($string)."\n";
		//$string = "2089068227"; echo $string." ".string_to_regex($string)."\n";
		//$string = "^(20890682[0-9][0-9])$"; echo $string." ".string_to_regex($string)."\n";
		//$string = "1208906xxxx"; echo $string." ".string_to_regex($string)."\n";
		//$string = "nxxnxxxxxxx"; echo $string." ".string_to_regex($string)."\n";
		//$string = "208906xxxx"; echo $string." ".string_to_regex($string)."\n";
		//$string = "^(2089068227"; echo $string." ".string_to_regex($string)."\n";
		//$string = "^2089068227)"; echo $string." ".string_to_regex($string)."\n";
		//$string = "2089068227$"; echo $string." ".string_to_regex($string)."\n";
		//$string = "2089068227)$"; echo $string." ".string_to_regex($string)."\n";
	}
	