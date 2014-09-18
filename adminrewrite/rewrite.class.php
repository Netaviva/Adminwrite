<?php

require_once('cache.class.php');

class Linko_Rewrite
{
	private $_oCache;
	
	private $_sPath;
	
	private $_aRules = array();
	
	private $_sBase = '/';
	
	private $_sRulesFile;

	const REGEX_DELIM = '#';
	const REGEX_STATIC = '%s';
	const REGEX_ANY = "([^/]+)";
	const REGEX_INT = "([0-9]+)";
	const REGEX_ALPHA = "([a-zA-Z_-]+)";
	const REGEX_ALPHANUMERIC = "([0-9a-zA-Z_-]+?)";

	private $_aNamedPatterns = array();
		
	public function __construct()
	{
		$this->_oCache = new Linko_Cache;
		$this->_sRulesFile = LINKO_BASE . 'rules.htc';
		
		$this->_aNamedPatterns = array(
			'int' => self::REGEX_INT,
			'integer' => self::REGEX_INT,
			'alpha' => self::REGEX_ALPHA,
			'alnum' => self::REGEX_ALPHANUMERIC,
			'alphanum' => self::REGEX_ALPHANUMERIC,
			'alphanumeric' => self::REGEX_ALPHANUMERIC,
			'any' => self::REGEX_ANY,
		);
		
		$this->_buildRules();
		$this->_sPath = $this->_getPath();
	}

	public function getBase()
	{
		return $this->_sBase;
	}
	
	public function getRules()
	{
		return $this->_aRules;
	}
	
	public function getRulesFile()
	{
		return $this->_sRulesFile;	
	}
	
	public function rewrite()
	{	
		$bFound = false;	
		$iRules = count($this->_aRules);

		$sRewritePath = preg_replace('/^\.\//', "", str_replace($this->_sBase,"",$this->_sPath));
		
		$sPath =  parse_url($sRewritePath, PHP_URL_PATH);
		$sQuery =  parse_url($sRewritePath, PHP_URL_QUERY);
	
		foreach($this->_aRules as $iKey => $aParam)
		{
			$bCheckQuery = false;
			$sTarget = null;
			$sExpression = $aParam['regex'];
			
			if(isset($aParam['flag']['NC']))
			{
				$sPath = strtolower($sPath);
				$sExpression = strtolower($sExpression);
			}
			
			$sPattern = self::REGEX_DELIM . $sExpression . self::REGEX_DELIM;
			
			if(preg_match($sPattern, $sPath))
			{
				$aLocation = parse_url($aParam['location']);
				$aLocationQuery = array();
				$aQuery = array();
				
				if(isset($aLocation['query']))
				{
					parse_str($aLocation['query'], $aLocationQuery);
				}
				
				// Build queries and populate $_GET and $_REQUEST with them
				$sTmp = preg_replace($sPattern, $aParam['location'], $sPath);
				$sTmpQuery = parse_url($sTmp, PHP_URL_QUERY);				
				if($sTmpQuery)
				{
					parse_str($sTmpQuery, $aTmpQueries);
					foreach($aTmpQueries as $sKey => $sValue)
					{
						$_REQUEST[$sKey] = $sValue;
						$_GET[$sKey] = $sValue;
						$aQuery[$sKey] = $sValue;
					}
				}
				
				if(isset($aParam['flag']['R']))
				{
					if(isset($aLocation['scheme']) && isset($aLocation['host']))
					{
						$sTarget = $aLocation['scheme'] . '://' . $aLocation['host'] . $aLocation['path'] . (count($aQuery) ? '?' . http_build_query($aQuery) : null);
					}
					else
					{
						$sTarget = $this->_sBase . $aLocation['path'] . (count($aQuery) ? '?' . http_build_query($aQuery) : null);;
					}
					
					$bFound = true;
					
					$this->_redirect($sTarget);					
				}
				else
				{
					$sDir = dirname($aLocation['path']);
					$sDirLoc = ($sDir != '.') ? $sDir : '';
					$sTarget = $_SERVER['DOCUMENT_ROOT'] . $this->_sBase . $sDirLoc . $aLocation['path'];
					
					if(file_exists($sTarget))
					{
						$bFound = true;	
					}					
				}
				
				if($bFound)
				{
					if(isset($aParam['flag']['L']))
					{
						break;	
					}
				}
			}	
		}
		
		if($bFound === false || empty($sTarget) || !file_exists($sTarget))
		{
			$sTarget = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'error.php';
		}
		
		return $sTarget;
	}
	
	public function read($sFile)
	{
		if($rFp = fopen($sFile, 'r'))
		{
			$aLines = array();
			
			while(!feof($rFp))
			{
				$aLines[] = trim(fgets($rFp));
			}
			
			fclose($rFp);
			
			return $aLines;
		}
		
		return array();
	}

	// Gets the current url in the address bar
	private function _getPath()
	{
		$sHost = $_SERVER['HTTP_HOST'];
		$sUri = $_SERVER['REQUEST_URI'];
		$sQuery = !empty($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : null;	
		
		return $sUri . ($sQuery ? '?' . $sQuery : null);
	}
			
	private function _buildRules()
	{
		$sId = 'rules';
		$aRules = array();
		
		if(!$aRules = $this->_oCache->read($sId))
		{
			$aLines = $this->read($this->_sRulesFile);	
			
			foreach($aLines as $iKey => $sLine)
			{
				// Get BasePath
				if(preg_match('/^BasePath/i', $sLine))
				{
					$aTmp = preg_split('/(\s)+/', $sLine);
					$aRules['base'] = $aTmp[1];
				}
				
				// 
				if(preg_match('/^Rewrite/i', $sLine))
				{
					$aTmp = preg_split('/(\s)+/', $sLine);
					$aRules['rules'][$iKey] = array(
						'regex' => $this->_parseExpr(trim($aTmp[1])),
						'expression' => trim($aTmp[1]),
						'location' => trim($aTmp[2]),
						'flag_raw' => trim($aTmp[3]),
						'flag' => isset($aTmp[3]) ? $this->_parseFlag(trim($aTmp[3])) : '',
					);	
				}
				$this->_oCache->write($sId, $aRules);
			}
		}
		
		$this->_aRules = $aRules['rules'];
		$this->_sBase = $aRules['base'];
	}
	
	private function _parseExpr($sExpr)
	{
		$sRegex = "^";
		$iCnt = 0;
		$aParts = explode('/', $sExpr);
		$iParts = count($aParts);
		
		foreach($aParts as $sPart)
		{
			$iCnt++;
			$bParse = false;
			
			$sRegex .= (($iCnt != 1) && ($iCnt != $iParts)) ? '/' : '';
			
			if(empty($sPart))
			{
				break;	
			}
			
			if(preg_match(self::REGEX_DELIM . ':(.*):(.*)?' . self::REGEX_DELIM, $sPart, $aMatch))
			{
				$bParse = true;
				$sPattern = $aMatch[1];
				$sParam = $aMatch[2];
			}
			
			if($bParse === false)
			{
				$sRegex .= sprintf(self::REGEX_STATIC, preg_quote($sPart, self::REGEX_DELIM));
				continue;	
			}
			
			if($sPattern == 'regex')
			{
				$sRegex .= $sParam;
				continue;
			}
			
			if(isset($this->_aNamedPatterns[$sPattern]))
			{
				$sRegex .= $this->_aNamedPatterns[$sPattern];	
			}
			else
			{
				$sRegex .= '(' . $sPattern . '+?)';
			}
		}
		
		$sRegex .= "/?$";
		
		return $this->prepareExpression($sRegex);
	}
	
	private function _normalize($sStr)
	{
		return preg_replace("/[^a-zA-Z0-9]/", "", $sStr);		
	}

	public function prepareExpression($sExpr)
	{
		$sExpr = preg_replace('~(?<!\\\)\/~', '\/', $sExpr); 
		
		return $sExpr;
	}
	
	private function _parseFlag($sFlag)
	{
		$aFlag = array_map('trim', explode(',', $sFlag));
		
		foreach($aFlag as $iKey => $sFlag)
		{
			$aFlag[$sFlag] = true;
			if(preg_match('/=/', $sFlag))
			{
				$aTmp = explode('=', $sFlag);
				$aFlag[$aTmp[0]] = $aTmp[1];
				unset($aFlag[$sFlag]);
			}
			unset($aFlag[$iKey]);
		}
		
		return $aFlag;
	}
	
	private function _redirect($sLoc, $sHeaderCode = '301')
	{
		header("Location: " . $sLoc);
		
		exit;	
	}
}

?>