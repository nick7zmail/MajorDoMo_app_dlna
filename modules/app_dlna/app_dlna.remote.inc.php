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
		 } elseif($cmd=='next'){
			$result = $remote->next();
		 } elseif($cmd=='prev'){
			$result = $remote->previous();
		 }
		}		
	} elseif ($property=='playUrl') {
		$url = $value;
		if (strpos($url,'youtube')>1) {
			$res1=parse_url($url, PHP_URL_QUERY);
			$res2=parse_str($res1,$res); 
			$res=$res['v'];
			$newurl='https://hms.lostcut.net/youtube/g.php?v='.$res.'&link_only=1'; 
			$url=file_get_contents($newurl);		
			$url=$renderer['location'];
			//$host=str_replace('/','',explode(":",$url)[1]);
			$host="192.168.1.82";
			//$port=str_replace('/','',explode(":",$url)[2]);
			$port="7676";
			$controlURL=gg('9732d18b-48f1-7f50-2b02-463b0f37e9a7.controlURL');
			echo 'host:'.$host."<br>";
			echo 'port:'.$port."<br>";
			echo 'controlURL:'.$controlURL."<br>";


			//command:
			//SetAVTransportURI
			//Stop
			//Play

			//AVTransport
			///AVTransport/9732d18b-48f1-7f50-2b02-463b0f37e9a7/control.xml
			echo 'Stop<br>';
			//Stop
			$xml = '<?xml version="1.0" encoding="utf-8" standalone="yes"?><s:Envelope s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" xmlns:s="http://schemas.xmlsoap.org/soap/envelope/"><s:Body><u:Stop xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"><InstanceID>0</InstanceID></u:Stop></s:Body></s:Envelope>
			';
			$asnw=sendpacket($host,$port,$controlURL,'Stop', $xml);
			echo $asnw;
			echo 'SetAVTransportURI<br>' ;
			sleep(1);
			//SetAVTransportURI
			$xml = '<?xml version="1.0" encoding="utf-8" standalone="yes"?><s:Envelope s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" xmlns:s="http://schemas.xmlsoap.org/soap/envelope/"><s:Body><u:SetAVTransportURI xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"><InstanceID>0</InstanceID><CurrentURI>http://192.168.1.31:32469/object/df4b8e517919aa1f643c/file.mkv</CurrentURI><CurrentURIMetaData>&lt;DIDL-Lite xmlns="urn:schemas-upnp-org:metadata-1-0/DIDL-Lite/" xmlns:upnp="urn:schemas-upnp-org:metadata-1-0/upnp/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dlna="urn:schemas-dlna-org:metadata-1-0/" xmlns:sec="http://www.sec.co.kr/" xmlns:pv="http://www.pv.com/pvns/"&gt;&lt;item id="df4b8e517919aa1f643c" parentID="c104054e5a4c8c3c046e" restricted="1"&gt;&lt;upnp:class&gt;object.item.videoItem&lt;/upnp:class&gt;&lt;dc:title&gt;Aritmija Web Dl&lt;/dc:title&gt;&lt;dc:creator&gt;Unknown&lt;/dc:creator&gt;&lt;upnp:artist&gt;Unknown&lt;/upnp:artist&gt;&lt;upnp:albumArtURI&gt;http://192.168.1.31:32469/proxy/c9ccea0d9cf75f43c34b/albumart.jpg&lt;/upnp:albumArtURI&gt;&lt;upnp:genre&gt;Unknown&lt;/upnp:genre&gt;&lt;res protocolInfo="http-get:*:video/x-matroska:DLNA.ORG_OP=01;DLNA.ORG_CI=0;DLNA.ORG_FLAGS=01500000000000000000000000000000" bitrate="692000" nrAudioChannels="6" size="4810724267" resolution="1920x1038" duration="1:55:52.000"&gt;'.$fn.'&lt;/res&gt;&lt;/item&gt;&lt;/DIDL-Lite&gt;</CurrentURIMetaData></u:SetAVTransportURI></s:Body></s:Envelope>
			';
			$asnw=sendpacket($host,$port,$controlURL,'SetAVTransportURI', $xml);
			echo $asnw;
			echo 'Play<br>';
			sleep(1);
			//Play
			$xml= '<?xml version="1.0" encoding="utf-8" standalone="yes"?><s:Envelope s:encodingStyle="http://schemas.xmlsoap.org/soap/encoding/" xmlns:s="http://schemas.xmlsoap.org/soap/envelope/"><s:Body><u:Play xmlns:u="urn:schemas-upnp-org:service:AVTransport:1"><InstanceID>0</InstanceID><Speed>1</Speed></u:Play></s:Body></s:Envelope>
			';
			$asnw=sendpacket($host,$port,$controlURL,'Play', $xml);
			echo $asnw;




			function sendpacket($host,$port,$AVTransport, $command, $xml){
			///play
			$body=$xml;
			$headers = 'POST '.$AVTransport.' HTTP/1.1
			Soapaction: "urn:schemas-upnp-org:service:AVTransport:1#'.$command.'"
			CONTENT-TYPE: text/xml; charset="utf-8"
			HOST: '.$host.':'.$port;
			$content=$headers . '
			Content-Length: '. strlen($body) .'

			'. $body;
			echo $host.":". $port;
			$fp = fsockopen($host, $port , $errno, $errstr, 10);
					if (!$fp)
					{echo "Error opening socket: ".$errstr." (".$errno.")<br>";} else 
					{            
			$ret = "";
			$buffer = "";
					 

			fwrite($fp, $content);
				fclose($fp);
					}

			fclose($fp); 
			 return $ret;
			 }		
		} else {
			if($renderer) {
			 $remote = new Renderer\Remote($renderer);
			 $result = $remote->play($url);
			 debmes($result);
			}		 
		}
	}
?>