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
	if($option->albumtype=="weibo"){
		if($option->issavealbum=="n"){
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
				$urls.=$option->weiboprefix.$arr['data']['pics']['pic_1']['pid'].".jpg<br />";
				$hrefs.="<a style='text-decoration:none;' href='".$option->weiboprefix.$arr['data']['pics']['pic_1']['pid'].".jpg' target='_blank' title='".$_FILES['webimgupload']['name'][$i]."'>".$option->weiboprefix.$arr['data']['pics']['pic_1']['pid'].".jpg</a><br />";
				$codes.="<a href='".$option->weiboprefix.$arr['data']['pics']['pic_1']['pid'].".jpg' target='_blank' title='".$_FILES['webimgupload']['name'][$i]."'><img src='".$option->weiboprefix.$arr['data']['pics']['pic_1']['pid'].".jpg' alt='".$_FILES['webimgupload']['name'][$i]."' /></a>\r\n";
			}
			$json=json_encode(array("status"=>"ok","msg"=>"上传结果","urls"=>$urls,"hrefs"=>$hrefs,"codes"=>$codes));
			echo $json;
		}else{
			if(!class_exists('SaeTOAuthV2')&&!class_exists('SaeTClientV2')){
				include_once dirname(__FILE__) .'/../include/saetv2.ex.class.php';
			}
			$config_weibooauth=@unserialize(ltrim(file_get_contents(dirname(__FILE__).'/../../../plugins/WeiboFile/config/config_weibooauth.php'),'<?php die; ?>'));
			$config_weibotoken=@unserialize(ltrim(file_get_contents(dirname(__FILE__).'/../../../plugins/WeiboFile/config/config_weibotoken.php'),'<?php die; ?>'));
			
			$time=time();
			$utfname=$time."_".$_FILES["webimgupload"]["name"][0];
			$gbkname = iconv("utf-8", "gbk", $utfname);
			move_uploaded_file($_FILES["webimgupload"]["tmp_name"][0], dirname(__FILE__).'/../uploadfile/'.$gbkname);
			$img=$plug_url."/WeiboFile/uploadfile/".$utfname;
			
			/* 修改了下风格，并添加文章关键词作为微博话题，提高与其他相关微博的关联率 */
			$string1 = '【'.$options->title.'】';
			$string2 = '来源：'.$options->siteUrl;
			/* 微博字数控制，避免超标同步失败 */
			$postData = $string1.mb_strimwidth("贴图",0, 140,'...').$string2;
			
			$c = new SaeTClientV2( $config_weibooauth["weiboappkey"] , $config_weibooauth["weiboappsecret"] , $config_weibotoken["access_token"] );
			$arr=$c->share($postData,$img);
			unlink(dirname(__FILE__).'/../uploadfile/'.$gbkname);
			
			if(isset($arr['original_pic'])){
				$urls=$arr["original_pic"];
				$hrefs="<a style='text-decoration:none;' href='".$urls."' target='_blank' title='".$_FILES['webimgupload']['name'][0]."'>".$urls."</a>";
				$codes="<a href='".$urls."' target='_blank' title='".$_FILES['webimgupload']['name'][0]."'><img src='".$urls."' alt='".$_FILES['webimgupload']['name'][0]."' /></a>";
				$json=json_encode(array("status"=>"ok","msg"=>"上传结果","urls"=>$urls,"hrefs"=>$hrefs,"codes"=>$codes));
				echo $json;
			}
		}
	}else if($option->albumtype=="ali"){
		if(@$_FILES["webimgupload"]['size'][0]<=1024*1024*3){
			$tempfilename = iconv("utf-8", "gbk", @$_FILES["webimgupload"]['name'][0]);
			move_uploaded_file(@$_FILES["webimgupload"]['tmp_name'][0], dirname(__FILE__).'/../uploadfile/'.$tempfilename);
			$ch = curl_init();
			$filePath = dirname(__FILE__).'/../uploadfile/'.$tempfilename;
			$data = array('file' => '@' . $filePath);
			if (class_exists('\CURLFile')) {
				$data['file'] = new \CURLFile(realpath($filePath));
			} else {
				if (defined('CURLOPT_SAFE_UPLOAD')) {
					curl_setopt($ch, CURLOPT_SAFE_UPLOAD, FALSE);
				}
			}
			curl_setopt($ch, CURLOPT_URL, 'https://www.tongleer.com/api/web/?action=weiboimg&type=ali');
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$json=curl_exec($ch);
			curl_close($ch);
			@unlink(dirname(__FILE__).'/../uploadfile/'.$tempfilename);
			$arr=json_decode($json,true);
			$imgurls=explode("/",$arr['data']["src"]);
			$urls=$option->aliprefix.$imgurls[count($imgurls)-1];
			$hrefs="<a style='text-decoration:none;' href='".$urls."' target='_blank' title='".$_FILES['webimgupload']['name'][0]."'>".$urls."</a>";
			$codes="<a href='".$urls."' target='_blank' title='".$_FILES['webimgupload']['name'][0]."'><img src='".$urls."' alt='".$_FILES['webimgupload']['name'][0]."' /></a>";
			$json=json_encode(array("status"=>"ok","msg"=>"上传结果","urls"=>$urls,"hrefs"=>$hrefs,"codes"=>$codes));
			echo $json;
		}
	}else if($option->albumtype=="qihu"){
		if(@$_FILES["webimgupload"]['size'][0]<=1024*1024*3){
			$tempfilename = iconv("utf-8", "gbk", @$_FILES["webimgupload"]['name'][0]);
			move_uploaded_file(@$_FILES["webimgupload"]['tmp_name'][0], dirname(__FILE__).'/../uploadfile/'.$tempfilename);
			$ch = curl_init();
			$filePath = dirname(__FILE__).'/../uploadfile/'.$tempfilename;
			$data = array('file' => '@' . $filePath);
			if (class_exists('\CURLFile')) {
				$data['file'] = new \CURLFile(realpath($filePath));
			} else {
				if (defined('CURLOPT_SAFE_UPLOAD')) {
					curl_setopt($ch, CURLOPT_SAFE_UPLOAD, FALSE);
				}
			}
			curl_setopt($ch, CURLOPT_URL, 'https://www.tongleer.com/api/web/?action=weiboimg&type=qihu');
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$json=curl_exec($ch);
			curl_close($ch);
			@unlink(dirname(__FILE__).'/../uploadfile/'.$tempfilename);
			$arr=json_decode($json,true);
			$imgurls=explode("/",$arr['data']["src"]);
			$urls=$option->qihuprefix.$imgurls[count($imgurls)-1];
			$hrefs="<a style='text-decoration:none;' href='".$urls."' target='_blank' title='".$_FILES['webimgupload']['name'][0]."'>".$urls."</a>";
			$codes="<a href='".$urls."' target='_blank' title='".$_FILES['webimgupload']['name'][0]."'><img src='".$urls."' alt='".$_FILES['webimgupload']['name'][0]."' /></a>";
			$json=json_encode(array("status"=>"ok","msg"=>"上传结果","urls"=>$urls,"hrefs"=>$hrefs,"codes"=>$codes));
			echo $json;
		}
	}else if($option->albumtype=="jd"){
		if(@$_FILES["webimgupload"]['size'][0]<=1024*1024*3){
			$tempfilename = iconv("utf-8", "gbk", @$_FILES["webimgupload"]['name'][0]);
			move_uploaded_file(@$_FILES["webimgupload"]['tmp_name'][0], dirname(__FILE__).'/../uploadfile/'.$tempfilename);
			$ch = curl_init();
			$filePath = dirname(__FILE__).'/../uploadfile/'.$tempfilename;
			$data = array('file' => '@' . $filePath);
			if (class_exists('\CURLFile')) {
				$data['file'] = new \CURLFile(realpath($filePath));
			} else {
				if (defined('CURLOPT_SAFE_UPLOAD')) {
					curl_setopt($ch, CURLOPT_SAFE_UPLOAD, FALSE);
				}
			}
			curl_setopt($ch, CURLOPT_URL, 'https://www.tongleer.com/api/web/?action=weiboimg&type=jd');
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$json=curl_exec($ch);
			curl_close($ch);
			@unlink(dirname(__FILE__).'/../uploadfile/'.$tempfilename);
			$arr=json_decode($json,true);
			$imgurls=explode("/",$arr['data']["src"]);
			if(strpos($imgurls[4],"ERROR")!==false){
				$urls="上传失败换张图片试试";
			}else{
				$urls=$option->jdprefix.substr($arr['data']["src"],strpos($arr['data']["src"],$imgurls[4]));
			}
			$hrefs="<a style='text-decoration:none;' href='".$urls."' target='_blank' title='".$_FILES['webimgupload']['name'][0]."'>".$urls."</a>";
			$codes="<a href='".$urls."' target='_blank' title='".$_FILES['webimgupload']['name'][0]."'><img src='".$urls."' alt='".$_FILES['webimgupload']['name'][0]."' /></a>";
			$json=json_encode(array("status"=>"ok","msg"=>"上传结果","urls"=>$urls,"hrefs"=>$hrefs,"codes"=>$codes));
			echo $json;
		}
	}
	exit;
}
?>