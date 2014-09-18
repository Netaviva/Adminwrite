<?php

class Linko_Flash
{
	public static function add($sMsg)
	{
		setcookie('linkodev_flash_message', $sMsg, time() + 260); 
	}
	
	public static function get()
	{
		return isset($_COOKIE['linkodev_flash_message']) ? $_COOKIE['linkodev_flash_message'] : null;
	}
	
	public static function remove()
	{
		setcookie('linkodev_flash_message', '', -1);
	}
}

class Linko_Text
{
	public function clean($var)
	{
		if(is_array($var))
		{
			return array_map('self::clean', $var);	
		}
		
		return htmlspecialchars(strip_tags(stripslashes(trim($var))));
	}	
}

class Linko_Request
{
	public static function redirect($sLoc, $sMsg = null)
	{
		if($sMsg)
		{
			Linko_Flash::add($sMsg);	
		}
		
		header("Location: " . $sLoc);
		
		exit;
	}
}

class Linko_Util
{
	// http://regex.info/listing.cgi?ed=3&p=474
	public function pregValidate($pattern)
	{
   		if ($old_track = ini_get("track_errors"))
   		{
       		$old_message = isset($php_errormsg) ? $php_errormsg : false;
   		}
   		else
   		{
       		ini_set('track_errors', 1);
   		}
		
   		unset($php_errormsg);
   		@preg_match($pattern, "");
   		$return_value = isset($php_errormsg) ? $php_errormsg : false;

   		if ($old_track)
   		{
       		$php_errormsg = isset($old_message) ? $old_message : false;
   		}
   		else
   		{
       		ini_set('track_errors', 0);
   		}

   		return $return_value;			
	}
}

class Linko_Admin
{
	private static $_oRewrite;
	
	private static $_oCache;
	
	public static function createHash($sUser, $sPass)
	{
		return md5($sUser . base64_encode(md5($sPass)));
	}
	
	public static function set()
	{
		global $oRewrite;
		self::$_oRewrite = $oRewrite;
		self::$_oCache = new Linko_Cache;
	}
	
	public static function addRule($sExpr, $sRedirect, $sFlag)
	{
		$sRule = "\r\nRewrite " . $sExpr . " " . $sRedirect . " " . $sFlag;
		
		$rFp = fopen(self::$_oRewrite->getRulesFile(), 'a');
		fwrite($rFp, $sRule, strlen($sRule));
		fclose($rFp);
		self::$_oCache->delete('rules');
		return true;
	}
	
	public static function editRule($iLine, $sExpr, $sRedirect, $sFlag)
	{
		$aLines = self::$_oRewrite->read(self::$_oRewrite->getRulesFile());
		
		$aLines[$iLine] = "Rewrite " . $sExpr . " " . $sRedirect . " " . $sFlag;
		
		$sContent = implode("\r\n", $aLines);

		$rFp = fopen(self::$_oRewrite->getRulesFile(), 'w');
		fwrite($rFp, $sContent);
		fclose($rFp);
		
		self::$_oCache->delete('rules');
		return true;		
	}
	
	public function removeRule($iLine)
	{
		$aLines = self::$_oRewrite->read(self::$_oRewrite->getRulesFile());
		unset($aLines[$iLine]);
		$sContent = implode("\r\n", $aLines);
		$rFp = fopen(self::$_oRewrite->getRulesFile(), 'w');
		fwrite($rFp, $sContent);
		fclose($rFp);
		self::$_oCache->delete('rules'); 
		return true;			
	}
	
	public function changeBase($sBase)
	{
		// read
		$aLines = self::$_oRewrite->read(self::$_oRewrite->getRulesFile());
		
		foreach($aLines as $iLine => $sLine)
		{
			if(preg_match('/^BasePath/i', $sLine))
			{
				$aLines[$iLine] = "BasePath " . $sBase;
			}			
		}
		
		$sContent = implode("\r\n", $aLines);
		$rFp = fopen(self::$_oRewrite->getRulesFile(), 'w');
		fwrite($rFp, $sContent);
		fclose($rFp);
		self::$_oCache->delete('rules');
		return true;		
	}
	
	public function title()
	{
		return 'LinkoDev AdminBased Rewrite Simulator V1.1';	
	}
}

?>