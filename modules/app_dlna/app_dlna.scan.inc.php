<?php
require_once(DIR_MODULES.$this->name.'/upnp/vendor/autoload.php');
use jalder\Upnp\Upnp;

$res = Scan();
if (count($res)) {
    foreach ($res as $obj) {
        // some action for every record if required
		$rec=SQLSelectOne("SELECT * FROM dlna_dev WHERE UUID='".$obj['UUID']."'");
		$rec['UUID']=$obj['UUID'];
		$rec['TITLE']=$obj['TITLE'];
		$rec['LOGO']=$obj['LOGO'];
		$rec['JSON_DATA']=json_encode($obj['JDATA']);
		if(!$rec['ID']) {
			SQLInsert('dlna_dev', $rec);
		} else {
			SQLUpdate('dlna_dev', $rec);
		}
    }
	require_once(DIR_MODULES.$this->name.'/ownlibs/castinphp/Chromecast.php');
	$cc=Chromecast::scan();
	foreach ($cc as $obj) {
		$rec=SQLSelectOne("SELECT * FROM dlna_dev WHERE UUID='".$obj['UUID']."'");
		$rec['UUID']=$obj['target'];
		$rec['TITLE']=$obj['friendlyname'];
		$rec['LOGO']='/templates/app_dlna/img/chromecast.png';
		$rec['JSON_DATA']=json_encode($obj);
		if(!$rec['ID']) {
			SQLInsert('dlna_dev', $rec);
		} else {
			SQLUpdate('dlna_dev', $rec);
		}
	}
	$this->redirect("?");
}
function Scan()
{
    $upnp = new Upnp();
    $everything = $upnp->discover();
    $result = [];
    foreach ($everything as $device) {
        if (!array_search_result($result, 'UUID', $device['description']['device']["UDN"])) {
            $result[] = [
                "TITLE" => $device['description']['device']["friendlyName"],
                "UUID" => $device['description']['device']["UDN"],
                "LOGO" => getDefImg($device),
				"JDATA"=>$device
            ];
        }
    }
    return $result;
}
function array_search_result($array, $key, $value)
{
    foreach ($array as $k => $v) {
        if (array_key_exists($key, $v) && ($v[$key] == $value)) {
            return true;
        }
    }
}
function getIp($dev)
{
    $result = explode(":", $dev['description']['device']["presentationURL"])[1];
    return str_replace("//", "", $result);
}
function getDefImg($dev)
{
	/*if($dev['description']['device']["presentationURL"] && $dev['description']['device']["iconList"]["icon"]["0"]["url"]) {
		$img_url = substr($dev['description']['device']["presentationURL"], 0, -1). $dev['description']['device']["iconList"]["icon"]["0"]["url"];
	} else*/
	if ($dev['description']['device']["iconList"]["icon"]["0"]["url"]) {
		$img_url = str_replace('\\','', $dev["location"]);
		$parsed_url = parse_url($img_url);
		$img_url = $parsed_url['scheme'].'://'.$parsed_url['host'].':'.$parsed_url['port'].$dev['description']['device']["iconList"]["icon"]["0"]["url"];
	} elseif ($dev['description']['device']["manufacturer"] == "Google Inc." && $dev['description']['device']["modelName"] == "Eureka Dongle") {
        $img_url = "/templates/app_dlna/img/chromecast.png";
    } elseif (($dev['description']['device']["manufacturer"] == "LG Electronics." || $dev['description']['device']["manufacturer"] == "LG Electronics") && ($dev['description']['device']["modelName"] == "LG TV" || $dev['description']['device']["modelName"] == "LG Smart TV")) {
        $img_url = "/templates/app_dlna/img/tv.png";
    } elseif ($dev['description']['device']["manufacturer"] == "Synology" || $dev['description']['device']["manufacturer"] == "Synology Inc") {
        $img_url = "/templates/app_dlna/img/synology.png";
    } elseif ($dev['description']['device']["manufacturer"] == "Emby" && $dev['description']['device']["modelName"] == "Emby") {
        $img_url = $dev["presentationURL"] . $dev["iconList"]["icon"]["4"]["url"];
    } elseif ($dev['description']['device']["manufacturer"] == "Linksys" || $dev['description']['device']["manufacturer"] == "Cisco") {
        $img_url = "/templates/app_dlna/img/router.png";
    } elseif ($dev['description']['device']["manufacturer"] == "XBMC Foundation") {
        $img_url = "/templates/app_dlna/img/kodi.png";
    }elseif ($dev['description']['device']["manufacturer"] == "Bubblesoft") {
        $img_url = "/templates/app_dlna/img/bubleupnp.png";
    }elseif ($dev['description']['device']["manufacturer"] == "BlackBerry") {
        $img_url = "/templates/app_dlna/img/blackberry.jpg";
    }elseif ($dev['description']['device']["manufacturer"] == "ASUSTeK Corporation" || $dev['description']['device']["manufacturer"] == "ASUSTeK Computer Inc.") {
        $img_url = "/templates/app_dlna/img/ASUSRouter.png";
    }elseif ($dev['description']['device']["manufacturer"] == "HIKVISION") {
        $img_url = "/templates/app_dlna/img/cam.png";
    }elseif ($dev['description']['device']["manufacturer"] == "Samsung Electronics") {
        $img_url = "/templates/app_dlna/img/samsung.png";
    }  else  {
		$img_url = "/templates/app_dlna/img/unk.png";
    }
	return $img_url;
}





/*$timeout=2;
$msg  = 'M-SEARCH * HTTP/1.1' . "\r\n";
$msg .= 'HOST: 239.255.255.250:1900' . "\r\n";
$msg .= 'MAN: "ssdp:discover"' . "\r\n";
$msg .= "MX: 3\r\n";
$msg .= "ST: upnp:rootdevice\r\n";
$msg .= '' . "\r\n";

$socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
socket_set_option($socket, SOL_SOCKET, SO_BROADCAST, 1);
socket_sendto($socket, $msg, strlen($msg), 0, '239.255.255.250', 1900);

socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => $timeout, 'usec' => 0));

$response = array();
do {
	$buf = null;
	@socket_recvfrom($socket, $buf, 1024, MSG_WAITALL, $from, $port);

	if (!is_null($buf)) {
		$response[] = discoveryReponse2Array($buf);
	}
} while (!is_null($buf));
socket_close($socket);

foreach ($response as $obj)
{
	$rec['UUID']=$obj[USN];
	$xml_file=file_get_contents($obj[LOCATION]);
	$xml = new DOMDocument();
	$xml->preserveWhiteSpace = false;  
	$xml->loadXML($xml_file);
	$books = $xml->getElementsByTagName('friendlyName');
	foreach ($books as $book) {
		$rec['TITLE']=$book->nodeValue; 
	}
	//$json[]=
	SQLInsert('dlna_dev', $rec);
}

function discoveryReponse2Array($res)
    {
        $result = array();
        $lines  = explode("\n", trim($res));

        if (trim($lines[0]) == 'HTTP/1.1 200 OK') {
            array_shift($lines);
        }

        foreach ($lines as $line) {
            $tmp = explode(':', trim($line));
            $key   = strtoupper(array_shift($tmp));
            $value = (count($tmp) > 0 ? trim(join(':', $tmp)) : null);
            $result[$key] = $value;
        }
         return $result;
    }*/
?>