<?php
date_default_timezone_set('Asia/Shanghai');
include '../../../../config.inc.php';

$action = isset($_POST['action']) ? addslashes($_POST['action']) : '';
if($action=='uploadMovie'){
	$uid = isset($_GET['uid']) ? addslashes($_GET['uid']) : '';
	$db = Typecho_Db::get();
	$prefix = $db->getPrefix();
	
	$filename = iconv("utf-8", "gbk", $_FILES['movieFile']['name']);
	move_uploaded_file($_FILES['movieFile']['tmp_name'], dirname(__FILE__).'/'.$filename);
	$ch = curl_init();
	$filePath = dirname(__FILE__).'/'.$filename;
	$data = array('action' => $action, 'movieFile' => '@' . $filePath);
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
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	$json=curl_exec($ch);
	curl_close($ch);
	unlink(dirname(__FILE__).'/'.$filename);
	$arr=json_decode($json,true);
	if($arr['code']==100){
		$insertData = array(
			'videoupid'   =>  $arr['video_id'],
			'videoupuid'   =>  $uid,
			'videoupurl'     =>  'http://player.youku.com/embed/'.$arr['video_id'],
			'videouppic'     =>  '',
			'videoupinstime'     =>  date('Y-m-d H:i:s',Typecho_Date::time()),
			'videouptype'     =>  'youku'
		);
		$insert = $db->insert('table.weibofile_videoupload')->rows($insertData);
		$insertId = $db->query($insert);
	}
	
	echo $json;
}else if($action=='parseMovie'){
	require dirname(__FILE__).'/../include/UrlParse.php';
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