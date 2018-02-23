<?php 
/**
 * Class for array object with path support (readonly)
 */
class ArrayPath implements ArrayAccess
{
	public $Array = array();
 	function __construct($json, $isParameters=false)
 	{
		if ($isParameters) parse_str  ($json, $this->Array);
		else $this->Array =  json_decode($json, true);
 	}

	public function offsetSet($offset, $value) {}
	
	public function offsetExists($offset) {}
	
	public function offsetUnset($offset) {}
	
	public function offsetGet($path) {
 		$result = '';
 		$keys   = explode('\\', $path);
 		$curObject = @$this->Array;
 		for ($i=0; $i < count($keys); $i++) { 
 			$key = $keys[$i];
 			if (isset($curObject[$key])) {
 				if ($i == (count($keys)-1)) $result = $curObject[$key];
 				else $curObject = $curObject[$key];
 			} else break;
 		}
 		return $result;
	}

}

/**
* Class for gets and store values in the ini file (as database)
*/
class IniDatabase implements ArrayAccess
{
	private $file = "database.ini";
	
	function __construct($file='')
	{
		if ($file) $this->file = $file;
	}

	public function offsetSet($offset, $value) {
		$arrAlg = array();
		if (file_exists($this->file))
			$arrAlg = parse_ini_file($this->file);
		$arrAlg[$offset] = $value;
		$this->WriteIni($arrAlg);
	}
	
	public function offsetExists($offset) {
		$arrAlg = array();
		if (file_exists($this->file))
			$arrAlg = parse_ini_file($this->file);
		return isset($arrAlg[$offset]);
	}
	
	public function offsetUnset($offset) {
		$arrAlg = array();
		if (file_exists($this->file))
			$arrAlg = parse_ini_file($this->file);
		unset($arrAlg[$offset]);
		WriteIni($arrAlg);
	}
	
	public function offsetGet($offset) {
		$result = "";
		$arrAlg = array();
		if (file_exists($this->file))
			$arrAlg = parse_ini_file($this->file);
		if (isset($arrAlg[$offset])) $result = $arrAlg[$offset];
		return $result;
	}

	private function WriteIni($array)
	{
		$res = array();
		foreach($array as $key => $val) {
			
			if(is_array($val)) {
				$res[] = "[$key]";
				foreach($val as $skey => $sval) $res[] = $skey.' = '.(is_numeric($sval) ? $sval : '"'.$sval.'"');
			
			} else {
				$res[] = $key.' = '.(is_numeric($val) ? $val : '"'.$val.'"');
			
			}
			$this->SafeFileRewrite($this->file, implode("\r\n", $res));
		}
	}

	private function SafeFileRewrite($fileName, $dataToSave)
	{
		if ($fp = fopen($fileName, 'w')) {
			$startTime = microtime(TRUE);
			do {
				$canWrite = flock($fp, LOCK_EX);
				if (!$canWrite) usleep(round(rand(0, 100)*1000));
			} while ((!$canWrite) && ((microtime(TRUE)-$startTime) < 5));

	        if ($canWrite) { fwrite($fp, $dataToSave); flock($fp, LOCK_UN); }

	        fclose($fp);
	    }
	}
}
