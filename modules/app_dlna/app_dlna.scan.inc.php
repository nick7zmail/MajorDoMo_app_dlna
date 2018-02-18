<?php
require_once(DIR_MODULES.$this->name.'/upnp/vendor/autoload.php');
use jalder\Upnp\Upnp;

$res = Scan();
if ($res[0]['ID']) {
    //paging($res, 100, $out); // search result paging
    $total = count($res);
    foreach ($res as $obj) {
        // some action for every record if required
		$rec['UUID']=$obj['UUID'];
		$rec['TITLE']=$obj['TITLE'];
		$rec['JSON_DATA']=json_encode($obj['JDATA']);
		$rec_dub=SQLSelect("SELECT * FROM dlna_dev WHERE UUID='".$obj['UUID']."'");
		$total_dub=count($rec_dub);
		if(!$total_dub) {
			SQLInsert('dlna_dev', $rec);
		}
    }
	$this->redirect("?");
    //$out['RESULT'] = $res;
}
function Scan()
{
    $upnp = new Upnp();
    print('searching...' . PHP_EOL);
    $everything = $upnp->discover();
    $result = [];
    foreach ($everything as $device) {
        $info = $device['description']['device'];
        if (!array_search_result($result, 'UUID', $info["UDN"])) {
            $result[] = [
                "ID" => $info["UDN"],
                "TITLE" => $info["friendlyName"],
                "ADDRESS" => $info["presentationURL"],
                "UUID" => $info["UDN"],
                "DESCRIPTION" => is_array($info["modelDescription"]) ? implode(',', $info["modelDescription"]) : $info["modelDescription"],
                "TYPE" => explode(":", $info["deviceType"])[3],
                "LOGO" => getDefImg($info),
                "SERIAL" => $info["serialNumber"],
                "MANUFACTURERURL" => $info["manufacturerURL"],
                "UPDATED" => '',
                "MODEL" => $info["modelName"],
                "MANUFACTURER" => $info["manufacturer"],
                "IP" => getIp($info),
				"JDATA"=>$device
            ];
        }
    }
    /*
    print("<pre>");
    print_r($result);
     print("</pre>");
    */
    return $result;
}
function array_search_result($array, $key, $value)
{
    //  global $result;
    foreach ($array as $k => $v) {
        if (array_key_exists($key, $v) && ($v[$key] == $value)) {
            return true;
        }
    }
    // return $result;;
}
function getIp($dev)
{
    $result = explode(":", $dev["presentationURL"])[1];
    return str_replace("//", "", $result);
}
function getDefImg($dev)
{
//print ("<pre>DIR: " .DIR_MODULES.$this->name );
    if ($dev["manufacturer"] == "Google Inc." && $dev["modelName"] == "Eureka Dongle") {
        return "/templates/SSDPFinder/img/chromecast.png";
    } elseif (($dev["manufacturer"] == "LG Electronics." || $dev["manufacturer"] == "LG Electronics") && ($dev["modelName"] == "LG TV" || $dev["modelName"] == "LG Smart TV")) {
        return "/templates/SSDPFinder/img/tv.png";
    } elseif ($dev["manufacturer"] == "Synology" || $dev["manufacturer"] == "Synology Inc") {
        return "/templates/SSDPFinder/img/synology.png";
    } elseif ($dev["manufacturer"] == "Emby" && $dev["modelName"] == "Emby") {
        return $dev["presentationURL"] . $dev["iconList"]["icon"]["4"]["url"];
    } elseif ($dev["manufacturer"] == "Linksys" || $dev["manufacturer"] == "Cisco") {
        return "/templates/SSDPFinder/img/router.png";
    } elseif ($dev["manufacturer"] == "XBMC Foundation") {
        return "/templates/SSDPFinder/img/kodi.png";
    }elseif ($dev["manufacturer"] == "Bubblesoft") {
        return "/templates/SSDPFinder/img/bubleupnp.png";
    }elseif ($dev["manufacturer"] == "BlackBerry") {
        return "/templates/SSDPFinder/img/blackberry.jpg";
    }elseif ($dev["manufacturer"] == "ASUSTeK Corporation" || $dev["manufacturer"] == "ASUSTeK Computer Inc.") {
        return "/templates/SSDPFinder/img/ASUSRouter.png";
    }elseif ($dev["manufacturer"] == "HIKVISION") {
        return "/templates/SSDPFinder/img/hikvision.jpg";
    }elseif ($dev["manufacturer"] == "Samsung Electronics") {
        return "/templates/SSDPFinder/img/samsung_printer.png";
    }
    else  {
    // return $dev["presentationURL"] . $dev["iconList"]["icon"]["0"]["url"];
    return "/templates/SSDPFinder/img/dlna.png";
    }
    //
    //  return $result;
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