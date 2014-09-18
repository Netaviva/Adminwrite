<?php

/*
	Package: LinkoCache
	Author: Morrison Laju (linkodev team)		
*/

class Linko_Cache
{
	private $_sId;
	
	private $_sCacheDir;
	
	public function __construct()
	{
		$this->setCacheDir(LINKO_BASE . 'cache' . DIRECTORY_SEPARATOR);	
	}
	
	public function setCacheDir($sDir)
	{
		$this->_sCacheDir = $sDir;
	}
	
	public function read($sId, $iExpire = 0)
	{
		if(!$this->_isCached($sId, $iExpire))
		{
			return false;	
		}
		
		require($this->_getFile($sId));
		
		if(!isset($aCacheData))
		{
			return false;	
		}
		
		if(!is_array($aCacheData) && empty($aCacheData))
		{
			return true;	
		}
		
		if(is_array($aCacheData) && !count($aCacheData))
		{
			return true;	
		}
		
		return $aCacheData;		
	}
	
	public function write($sId, $sData)
	{
		$sData = '<?php $aCacheData = ' . var_export($sData, true) . '; ?>';
		
		if($rFp = fopen($this->_getFile($sId), 'w+'))
		{
			fwrite($rFp, $sData);
			fclose($rFp);	
		}		
	}
	
	public function delete($sId)
	{
		$sFile = $this->_getFile($sId);
		
		if(file_exists($sFile))
		{
			unlink($sFile);	
		}
		
		return true;		
	}
	
	private function _isCached($sId, $iExpire)
	{
		if(file_exists($this->_getFile($sId)))
		{
			if($iExpire && ((time() - $iExpire) > filemtime($sId)))
			{
				return false;
			}
			
			return true;
		}
		
		return false;			
	}
	
	private function _getFile($sId)
	{
		return $this->_sCacheDir . $sId . '.cache.php';	
	}
}

?>