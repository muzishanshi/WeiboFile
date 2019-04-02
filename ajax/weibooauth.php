<?php
date_default_timezone_set('Asia/Shanghai');
include '../../../../config.inc.php';
include_once dirname(__FILE__) .'/../include/saetv2.ex.class.php';

$db = Typecho_Db::get();
$prefix = $db->getPrefix();
$options = Typecho_Widget::widget('Widget_Options');
$option=$options->plugin('WeiboFile');
$plug_url = $options->pluginUrl;

$action = isset($_POST['action']) ? addslashes($_POST['action']) : '';
if($action=='weibooauth'){
	$weiboappkey = isset($_POST['weiboappkey']) ? addslashes($_POST['weiboappkey']) : '';
	$weiboappsecret = isset($_POST['weiboappsecret']) ? addslashes($_POST['weiboappsecret']) : '';
	$weibocallback=$plug_url."/WeiboFile/weibocallback.php";
	
	file_put_contents(dirname(__FILE__).'/../config/config_weibooauth.php','<?php die; ?>'.serialize(array(
		'weiboappkey'=>$weiboappkey,
		'weiboappsecret'=>$weiboappsecret,
		'weibocallback'=>$weibocallback
	)));
	
	$sinav2_o = new SaeTOAuthV2( $weiboappkey , $weiboappsecret );
	$sinav2_aurl = $sinav2_o->getAuthorizeURL($weibocallback);
	
	$json=json_encode(array("status"=>"ok","msg"=>"授权成功","sinav2_aurl"=>$sinav2_aurl));
	echo $json;
	exit;
}else if($action=="weiboreoauth"){
	if (file_exists(dirname(__FILE__).'/../config/config_weibotoken.php')) {
		if (!unlink(dirname(__FILE__).'/../config/config_weibotoken.php')) {
			$json=json_encode(array("status"=>"fail","msg"=>"操作失败，请确保插件目录(/usr/plugins/WeiboFile/)可写"));
		}else{
			$json=json_encode(array("status"=>"ok","msg"=>"操作成功"));
		}
	}
	echo $json;
	exit;
}
?>