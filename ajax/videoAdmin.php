<?php
date_default_timezone_set('Asia/Shanghai');
include '../../../../config.inc.php';
$db = Typecho_Db::get();
$prefix = $db->getPrefix();

$action = isset($_POST['action']) ? addslashes($_POST['action']) : '';
if($action=='videoShow'){
	$id = isset($_POST['id']) ? addslashes($_POST['id']) : 0;
	$data=array(
		"action"=>"showMovie",
		"id"=>$id
	);
	$url = 'https://me.tongleer.com/mob/app/wap/json/blogjson.php';
	$client = Typecho_Http_Client::get();
	if ($client) {
		$str = "";
		foreach ( $data as $key => $value ) { 
			$str.= "$key=" . urlencode( $value ). "&" ;
		}
		$data = substr($str,0,-1);
		$client->setData($data)
			->setTimeout(30)
			->send($url);
		$status = $client->getResponseStatus();
		$rs = $client->getResponseBody();
		$arr=json_decode($rs,true);
		if($arr['code']==100){
			$update = $db->update('table.weibofile_videoupload')->rows(array('videouppic'=>$arr['thumbnail']))->where('videoupid=?',$id);
			$updateRows= $db->query($update);
			echo 1;exit;
		}else if($arr['code']==101){
			echo 2;exit;
		}else{
			echo -1;exit;
		}
	}
}else if($action=='videoDel'){
	$id = isset($_POST['id']) ? addslashes($_POST['id']) : 0;
	$delete = $db->delete('table.weibofile_videoupload')->where('videoupid = ?', $id);
	$deletedRows = $db->query($delete);
}
?>