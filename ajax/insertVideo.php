<?php
$action = isset($_POST['action']) ? addslashes($_POST['action']) : '';
if($action=='uploadMovie'){
	$filename = iconv("utf-8", "gbk", $_FILES['movieFile']['name']);
	move_uploaded_file($_FILES['movieFile']['tmp_name'], $filename);
	$ch = curl_init();
	$filePath = dirname(__FILE__).'/'.$filename;
	$data     = array('action' => $action, 'movieFile' => '@' . $filePath);
	if (class_exists('\CURLFile')) {
		$data['movieFile'] = new \CURLFile(realpath($filePath));
	} else {
		if (defined('CURLOPT_SAFE_UPLOAD')) {
			curl_setopt($ch, CURLOPT_SAFE_UPLOAD, FALSE);
		}
	}
	curl_setopt($ch, CURLOPT_URL, 'http://me.tongleer.com/mob/app/wap/json/blogjson.php');
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_exec($ch);
	unlink($filename);
}else if($action=='parseMovie'){
	require dirname(__FILE__).'/../include/urlParse.php';
	$video_url = isset($_POST['video_url']) ? addslashes($_POST['video_url']) : '';
	$urlParse = new UrlParse();
	$result = $urlParse->setUrl($video_url);
	foreach ( $result as $key => $value ) {  
		$result[$key] = urlencode ( $value );  
	}  
	$json=json_encode($result);
	echo urldecode($json);
}
?>