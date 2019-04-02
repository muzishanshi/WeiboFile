<?php
include_once dirname(__FILE__) .'/include/saetv2.ex.class.php';

$config_weibooauth=@unserialize(ltrim(file_get_contents(dirname(__FILE__).'/../../plugins/WeiboFile/config/config_weibooauth.php'),'<?php die; ?>'));

if (isset($_REQUEST['code'])) {
	$sinav2_o = new SaeTOAuthV2( $config_weibooauth["weiboappkey"] , $config_weibooauth["weiboappsecret"] );
	$sinav2_keys = array();
	$sinav2_keys['code'] = $_REQUEST['code'];
	$sinav2_keys['redirect_uri'] = $config_weibooauth["weibocallback"];
	try {
		$sinav2_last_key = $sinav2_o->getAccessToken( 'code', $sinav2_keys ) ;
		if ($sinav2_last_key) {
			file_put_contents(dirname(__FILE__).'/config/config_weibotoken.php','<?php die; ?>'.serialize(array(
				'access_token'=>$sinav2_last_key['access_token'],
				'remind_in'=>$sinav2_last_key['remind_in'],
				'expires_in'=>$sinav2_last_key['expires_in'],
				'uid'=>$sinav2_last_key['uid']
			)));
			echo('<script>alert("登陆成功，刷新页面即可显示微博账户信息。");window.close();window.opener.location.reload();</script>');
		}else{
			echo('授权失败，请重新授权。');
		}
	} catch (OAuthException $e) {
		echo('发生意外，错误信息：'.$e);
	}
}
?>