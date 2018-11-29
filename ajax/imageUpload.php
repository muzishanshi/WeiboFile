<?php
date_default_timezone_set('Asia/Shanghai');
include '../../../../config.inc.php';
require __DIR__ . '/../include/Sinaupload.php';

$db = Typecho_Db::get();
$prefix = $db->getPrefix();
$options = Typecho_Widget::widget('Widget_Options');
$option=$options->plugin('WeiboFile');
$plug_url = $options->pluginUrl;

$action = isset($_POST['action']) ? addslashes($_POST['action']) : '';
if($action=='imageUpload'){
	if($option->weibouser==""||$option->weibopass==""){
		$json=json_encode(array("status"=>"noset","msg"=>"站长暂无配置好此图床"));echo $json;exit;
	}
	if($option->webimgupload!="y"){
		$json=json_encode(array("status"=>"disable","msg"=>"站长已禁用此图床"));echo $json;exit;
	}
	$urls="";
	$hrefs="";
	$codes="";
	for($i=0,$j=count($_FILES["webimgupload"]["name"]);$i<$j;$i++){
		$Sinaupload=new Sinaupload('');
		$cookie=$Sinaupload->login($option->weibouser,$option->weibopass);
		$result=$Sinaupload->upload($_FILES['webimgupload']['tmp_name'][$i]);
		$arr = json_decode($result,true);
		$urls.="https://ws3.sinaimg.cn/large/".$arr['data']['pics']['pic_1']['pid'].".jpg<br />";
		$hrefs.="<a style='text-decoration:none;' href='https://ws3.sinaimg.cn/large/".$arr['data']['pics']['pic_1']['pid'].".jpg' target='_blank' title='".$_FILES['webimgupload']['name'][$i]."'>https://ws3.sinaimg.cn/large/".$arr['data']['pics']['pic_1']['pid'].".jpg</a><br />";
		$codes.="<a href='https://ws3.sinaimg.cn/large/".$arr['data']['pics']['pic_1']['pid'].".jpg' target='_blank' title='".$_FILES['webimgupload']['name'][$i]."'><img src='https://ws3.sinaimg.cn/large/".$arr['data']['pics']['pic_1']['pid'].".jpg' alt='".$_FILES['webimgupload']['name'][$i]."' /></a>\r\n";
	}
	$json=json_encode(array("status"=>"ok","msg"=>"上传结果","urls"=>$urls,"hrefs"=>$hrefs,"codes"=>$codes));
	echo $json;
	exit;
}
?>