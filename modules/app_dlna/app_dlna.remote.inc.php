<?php
require_once(DIR_MODULES.$this->name.'/upnp/vendor/autoload.php');
use jalder\Upnp\Renderer;

$renderer=json_decode($properties[$i]['JSON_DATA'], true);

    if($property=='cmd') {
		$cmd = $value;
		if($renderer) {
		 $remote = new Renderer\Remote($renderer);
		 if($cmd=='pause') {
			$result = $remote->pause();
		 } elseif ($cmd=='stop'){
			$result = $remote->stop();
		 } elseif($cmd=='unpause'){
			$result = $remote->unpause();
		 }
		 debmes($result);
		}		
	} elseif ($property=='playUrl') {
		$url = $value;
		if($renderer) {
		 $remote = new Renderer\Remote($renderer);
		 $result = $remote->play($url);
		 debmes($result);
		}		 
	}
?>