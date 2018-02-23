<?php header("Content-Type: text/html; charset=utf-8");
include 'classes.php';
$videoId      = isset($_REQUEST['v'            ]) ?      $_REQUEST['v'            ] : '';   // Youtube video id
$mediaFormats = isset($_REQUEST['media_formats']) ?      $_REQUEST['media_formats'] : '';   // HMS MediaFormatsPriority string: 
$maxHeight    = isset($_REQUEST['max_height'   ]) ? (int)$_REQUEST['max_height'   ] : 1080; // Maximum height for selecting stream
$linkOnly     = isset($_REQUEST['link_only'    ]) ? (int)$_REQUEST['link_only'    ] : 0;    // Return only url for selected stream as plain text
$adaptive     = isset($_REQUEST['adaptive'     ]) ? (int)$_REQUEST['adaptive'     ] : 0;    // No select stream from adaptive_fmts field
$allLinks     = isset($_REQUEST['all_links'    ]) ? (int)$_REQUEST['all_links'    ] : 0;    // Return all streams
$humanReadable= isset($_REQUEST['hr'           ]) ? (int)$_REQUEST['hr'           ] : 0;    // Answer as human readable json (pretty view)
$auth         = isset($_REQUEST['auth'         ]) ?      $_REQUEST['auth'         ] : '';   // Access token
$time         = isset($_REQUEST['t'            ]) ?      $_REQUEST['t'            ] : '';   // Time begin of video
$notDE        = isset($_REQUEST['notde'        ]) ? (int)$_REQUEST['notde'        ] : 0;    // Do not try to get the video from Germany
$ip           = isset($_REQUEST['ip'           ]) ?      $_REQUEST['ip'           ] : '';   // Real IP
$ip           = isset($_REQUEST['usemyip'      ]) ?      $_SERVER['REMOTE_ADDR']    : $ip;  // Flag - set user`s real ip
$checkRestrict= isset($_REQUEST['checkrestrict']) ? (int)$_REQUEST['checkrestrict'] : 0;    // Check region restriction
$headers      = isset($_REQUEST['headers'      ]) ?      $_REQUEST['headers'      ] : '';   // Additional heades for youtube page request. Delimiter is '|'.
$redirect     = isset($_REQUEST['redirect'     ]); // redirect to gotten link

if (!$videoId) die(StatusError(1, "No video id in parameters"));

// Load the youtube video page
$options  = array(
  'http'=>array(
    'method'=>"GET",
    'header'=>"Accept-language: ru-ru,ru;q=0.8,en-us;q=0.5,en;q=0.3\r\n" .
              "User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/43.0.2357.124 Safari/537.36\r\n"
  )
);
if ($ip  ) $options['http']['header'] .= "X-Forwarded-For: ".$ip."\r\n" ;
if ($auth) $options['http']['header'] .= "Authorization: Bearer ".$auth."\r\n" ;
if ($headers) {
	$hdrs = explode('|', $headers);
	foreach ($hdrs as $h) $options['http']['header'] .= $h."\r\n" ;
}
if ($checkRestrict) echo "Headers: \r\n" . $options['http']['header'];
$context  = stream_context_create($options);
$VideoUrl = 'http://www.youtube.com/watch?v='.$videoId.'&hl=ru&persist_hl=1&has_verified=1&bpctr='.(time() + (2.5 * 60 * 60));
if ($time) $VideoUrl .= '&t='.$time;
$pageHtml = file_get_contents($VideoUrl, false, $context);

// Search ytPlayer.Config json in video page
if (!preg_match('/player.config\s*?=\s*?({.*?});/', $pageHtml, $matches)) {
	if ($checkRestrict) die(StatusError(6, "Video unavaliable"));
	echo file_get_contents('http://rus.lostcut.net/youtube/g.php?'.http_build_query($_REQUEST));
	exit;
}


if (!preg_match('/player.config\s*?=\s*?({.*?});/', $pageHtml, $matches)) {
	$msg = preg_match('/<h[^>]+unavailable-message.*?<\/h\d>/s', $pageHtml, $matches) ? $matches[0] : '';
	if ($msg) $msg = trim(strip_tags($msg));
	if ($msg) die(StatusError(2, $msg));
	else die(StatusError(3, "Video page do not contains player.config json object"));
}

$PlayerConfig = new ArrayPath($matches[1]);
$Algorithms   = new IniDatabase('algorithms.ini');

$hlsUrl      = ExpandUrl($PlayerConfig['args\\hlsvp']);
$ttsUrl      = ExpandUrl($PlayerConfig['args\\ttsurl']);
$flp         = ExpandUrl($PlayerConfig['url']);
$jsUrl       = ExpandUrl($PlayerConfig['assets\\js']);
$is3D        = false;
$isLive      = (bool)($hlsUrl!='');
$dashMpdLink = $PlayerConfig['args\\dashmpd'];
$streamMap   = $PlayerConfig['args\\url_encoded_fmt_stream_map'];

if ($adaptive && $PlayerConfig['args\\adaptive_fmts']) 
  $streamMap = $PlayerConfig['args\\adaptive_fmts'];

if (!$streamMap && !$isLive) die(StatusError(4, "Can not found stream map in player config"));

$playerId    = preg_match('/player-([\w_-]+)/', $jsUrl, $matches) ? $matches[1] : '';
$algorithm   = $playerId ? $Algorithms[$playerId] : '';
$selectedUrl = '';
$height      = 0;
$minPriority = 9;
$selHeight   = 0;
$selAudio    = '';

if (!$algorithm && !$isLive) {
	// If no algorithm in our database - load javascript, search and store algo
	$algorithm = GetAlgorithm($jsUrl);
	if ($algorithm) $Algorithms[$playerId] = $algorithm;
	else die(StatusError(5, "Can not find algorithm in player javascript"));
}

$allUrls      = array();
$resultObject = array();
$resultObject['status'] = 'ok';
$resultObject['ttsUrl'] = $ttsUrl;
$resultObject['Live'  ] = $isLive;

if ($isLive) {
	$data = file_get_contents($hlsUrl, false, $context);

	preg_match_all('/RESOLUTION=\d+x(\d+).*?\n(.*?)(\n|$)/s', $data, $matches);

	if (isset($matches[1])) {
		for ($i=0; $i < count($matches[1]); $i++) { 
			$arr = array();
			$height = $matches[1][$i];
			$url    = $matches[2][$i];
			$arr['height'] = $height;
			$arr['url'   ] = $url;
			$allUrls[] = $arr;

			if ($mediaFormats) {
				$priority = MediaFormatPriority($height, $mediaFormats);
				if (($priority>=0) && ($priority<$minPriority)) {
					$selectedUrl = $url; $minPriority = $priority; $selHeight = $height;
				}
			} elseif (($height>$selHeight) && ($height<=$maxHeight)) {
				$selectedUrl = $url; $selHeight = $height;
			}

		}
	} else {
		$selectedUrl = preg_match('(http.*?)(\n|$)/s', $data, $matches) ? $matches[1] : '';
	}

} else {
	$maps = explode(',', $streamMap);
	for ($i=0; $i < count($maps); $i++) { 
		$map = new ArrayPath($maps[$i], true);

		$type = $map['type'];
		$url  = $map['url' ];
		$itag = $map['itag'];
		$sig  = $map['sig' ];
		$s    = $map['s'   ];
		$is3D = $map['stereo3d']==1;

		if (!$url) continue;
		if (!$allLinks && (!strpos($type, 'flv') && !strpos($type, 'mp4'))) continue;
		$height = Itag2Height($itag);

		if (strpos($url, 'signature=')===false) {
			if (!$sig) $sig = YoutubeDecrypt($s, $algorithm);
			$url .= '&signature=' . $sig;
		}
		$map->Array['height'] = $height;
		$map->Array['3D'    ] = $is3D;
		$map->Array['url'   ] = $url;

		if (strpos($type, 'audio')!==false) { $selAudio = $url; continue; }

		$allUrls[] = $map->Array;

		if ($mediaFormats) {
			$priority = MediaFormatPriority($height, $mediaFormats);
			if (($priority>=0) && ($priority<$minPriority)) {
				$selectedUrl = $url; $minPriority = $priority; $selHeight = $height;
			}
		} elseif (($height>$selHeight) && ($height<=$maxHeight)) {
			$selectedUrl = $url; $selHeight = $height;
			
		} elseif (($height>=$selHeight) && ($height<=$maxHeight) && (in_array($itag, [18,22,37,38,82,83,84,85]))) {
			$selectedUrl = $url; $selHeight = $height; // MP4 format prioritet
		}
	}
}

if ($allLinks) {
	if ($isLive) $allUrls[] = $hlsUrl;
	$resultObject['urls'  ] = $allUrls;
} else {
	if ($isLive && !$selectedUrl) $selectedUrl = $hlsUrl;
	if ($selAudio) $resultObject['audio'] = $selAudio;
	$resultObject['height'] = $selHeight;
	$resultObject['3D'    ] = $is3D;
	$resultObject['url'   ] = $selectedUrl;
}

// The End with output results
if ($redirect) {
	header('Location: '.$selectedUrl);
	die();
}

if ($linkOnly)      die($selectedUrl);
if ($humanReadable) die(json_encode($resultObject, JSON_PRETTY_PRINT));
else                die(json_encode($resultObject));

///////////////////////////////////////////////////////////////////////////////

function GetAlgorithm($jsUrl) {
	if      (substr($jsUrl, 0, 2)=="//") $jsUrl = "https:".$jsUrl;
	else if (substr($jsUrl, 0, 1)=="/" ) $jsUrl = "https://www.youtube.com".$jsUrl;
	$algo = "";
	$data = file_get_contents($jsUrl);
	$fns  = preg_match('/a=a\.split\(""\);(.*?)return/s', $data, $m) ? $m[1] : '';
	$arr  = explode(';', $fns);
	// Iterate all operations in algorithm
	foreach ($arr as $func) {
		$textFunc = $func;
		// if called function of object - search the object and its function
		if (preg_match('/([\$\w]+)\.(\w+)\(/s', $textFunc, $m)) {
			$obj = $m[1];
			$fun = $m[2];
			if (($obj!='a') && preg_match('/var '.$obj.'=\{.*?('.$fun.':function|function '.$fun.'\()(.*?})/s', $data, $m))
				$textFunc = $m[2];
			else if (($obj!='a') && preg_match('/var \\'.$obj.'=\{.*?('.$fun.':function|function '.$fun.'\()(.*?})/s', $data, $m))
				$textFunc = $m[2];
		}
		// if called named function - search text of this function
		if (preg_match('/a=(\w+)\(/s', $textFunc, $m)) {
			$fun = $m[1];
			if (preg_match('/var '.$obj.'=\{.*?('.$fun.':function|function '.$fun.'\())(.*?})/s', $data, $m))
				$textFunc = $m[2];
			else if (preg_match('/var \\'.$obj.'=\{.*?('.$fun.':function|function '.$fun.'\())(.*?})/s', $data, $m))
				$textFunc = $m[2];
		}
		// get the value of parameter
		$numb = preg_match('/\(.*?(\d+)/s', $func, $m) ? $m[1] : '';
		// determine the type of the function
		$type = 'w';
		if     (preg_match('/revers/'        , $textFunc, $m)) $type = 'r';
		elseif (preg_match('/(splice|slice)/', $textFunc, $m)) $type = 's';
		if (($type!='r') && ($numb==='')) continue; // it's no cypher function
		$algo .= ($type=='r') ? $type.' ' : $type.$numb.' ';
	}
	return trim($algo);
}

// ----------------------------------------------------------------------------
function YoutubeDecrypt($sig, $algorithm) {
	$method = explode(" ", $algorithm);
	foreach($method as $m)
	{	//  r - revers,  s - clone,  w - swap
		if           ($m     =='r') $sig = strrev($sig);
		elseif(substr($m,0,1)=='s') $sig = substr($sig, (int)substr($m, 1));
		elseif(substr($m,0,1)=='w') $sig =   swap($sig, (int)substr($m, 1));
	}
	return $sig;
}

// ----------------------------------------------------------------------------
function swap($a, $b) {
	$c = $a[0]; $a[0] = $a[$b]; $a[$b] = $c;
	return $a;
}

// ----------------------------------------------------------------------------
function StatusError($errorCode, $msg) {
	$result = array('status' => 'error', 'error' => $errorCode, 'reason' => $msg);
	return json_encode($result, JSON_UNESCAPED_UNICODE);
}

// ----------------------------------------------------------------------------
function ExpandUrl($url) {
	if (substr($url, 0, 2)=='//') $url = 'http:' . trim($url);
	return $url;
}

// ----------------------------------------------------------------------------
function MediaFormatPriority($height, $mediaFormats) {
	$formats = explode(',', $mediaFormats);
	for ($i=0; $i < count($formats); $i++) { 
		$m = explode('-', $formats[$i]);
		$min = isset($m[0]) ? intval($m[0]) : 0;
		$max = isset($m[1]) ? intval($m[1]) : 0;
		if (($min>0) && ($height>=$min) && ($max>0) && ($height<=$max)) return $i;
	}
	return -1;
}

// ----------------------------------------------------------------------------
function Itag2Height($itag) {
	if     (in_array($itag, array(13,17,160,36           ))) return 144;
	elseif (in_array($itag, array(5,83,133,242           ))) return 240;
	elseif (in_array($itag, array(6                      ))) return 270;
	elseif (in_array($itag, array(18,34,43,82,100,134,243))) return 360;
	elseif (in_array($itag, array(35,44,101,135,244,43   ))) return 480;
	elseif (in_array($itag, array(22,45,84,102,136,247   ))) return 720;
	elseif (in_array($itag, array(37,46,137,248          ))) return 1080;
	elseif (in_array($itag, array(264,271                ))) return 1440;
	elseif (in_array($itag, array(266                    ))) return 2160;
	elseif (in_array($itag, array(138,272                ))) return 2304;
	elseif (in_array($itag, array(38                     ))) return 3072;
	return 0;
}
// ----------------------------------------------------------------------------
