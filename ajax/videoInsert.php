<?php
date_default_timezone_set('Asia/Shanghai');
include '../../../../config.inc.php';
ini_set('max_input_time', '600');
ini_set('max_execution_time', '600');
ini_set('memory_limit', '128M');
ini_set('upload_max_filesize','1024M');
ini_set('post_max_size','1024M');
set_time_limit(0);

$time=time();
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
	curl_setopt($ch, CURLOPT_URL, 'https://me.tongleer.com/mob/app/wap/json/blogjson.php');
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
			'videoupurl'     =>  'https://player.youku.com/embed/'.$arr['video_id'],
			'videouppic'     =>  '',
			'videoupinstime'     =>  date('Y-m-d H:i:s',$time),
			'videouptype'     =>  'youku'
		);
		$insert = $db->insert('table.weibofile_videoupload')->rows($insertData);
		$insertId = $db->query($insert);
	}
	
	echo $json;
}else if($action=='uploadImg'){
	$uid = isset($_GET['uid']) ? addslashes($_GET['uid']) : '';
	$cid = isset($_POST['cid']) ? addslashes($_POST['cid']) : '';
	$file=$_FILES['imgFile'];
	$db = Typecho_Db::get();
	$options = Typecho_Widget::widget('Widget_Options');
	$option=$options->plugin('WeiboFile');
	$plug_url = $options->pluginUrl;
	
	$key=0;
	$imgs=array();
	for($i=0,$j=count($file["name"]);$i<$j;$i++){
		$name=$file['name'][$i];
        if (empty($name)) $code=-1;
        $part = explode('.', $name);
        $ext = (($length = count($part)) > 1) ? strtolower($part[$length-1]) : '';
        if (!Widget_Upload::checkFileType($ext)) $code=-2;// 上传文件
        $filename = $file['tmp_name'][$i];
        if (!isset($filename)) $code=-3;
		if(!in_array($ext,array('gif','jpg','jpeg','png','bmp'))){
			$code=-4;
		}
		if($option->albumtype=="weibo"){
			if($option->issavealbum=="n"){
				if(!class_exists('Sinaupload')){
					require_once dirname(__FILE__) . '/../include/Sinaupload.php';
				}
				$Sinaupload=new Sinaupload('');
				$cookie=$Sinaupload->login($option->weibouser,$option->weibopass);
				$result=$Sinaupload->upload($file['tmp_name'][$i]);
				$arr = json_decode($result,true);
				$picname=$arr['data']['pics']['pic_1']['pid'];
				$url=$option->weiboprefix . $picname . '.jpg';
				
				if(isset($picname)){
					$text=array(
						'name'  =>  $name,
						'path'  =>  $url,
						'size'  =>  $file['size'][$i],
						'type'  =>  $ext,
						'mime'  =>  "image/jpeg"
					);
					$insertData = array(
						'title'   =>  $name,
						'slug'   =>  $name,
						'parent'   =>  $cid,
						'created'     =>  $time,
						'modified'     =>  $time,
						'text'     =>  serialize($text),
						'authorId'     =>  $uid,
						'type'     =>  'attachment',
						'status'     =>  'publish',
						'allowComment'     =>  '1',
						'allowFeed'     =>  '1'
					);
					$insert = $db->insert('table.contents')->rows($insertData);
					$insertId = $db->query($insert);
					$code=100;
				}else{
					$code=-5;
					$url="";
				}
			}else{
				if(!class_exists('SaeTOAuthV2')&&!class_exists('SaeTClientV2')){
					include_once dirname(__FILE__) .'/../include/saetv2.ex.class.php';
				}
				$config_weibooauth=@unserialize(ltrim(file_get_contents(dirname(__FILE__).'/../../../plugins/WeiboFile/config/config_weibooauth.php'),'<?php die; ?>'));
				$config_weibotoken=@unserialize(ltrim(file_get_contents(dirname(__FILE__).'/../../../plugins/WeiboFile/config/config_weibotoken.php'),'<?php die; ?>'));
				
				$utfname=$time."_".$name;
				$gbkname = iconv("utf-8", "gbk", $utfname);
				move_uploaded_file($filename, dirname(__FILE__).'/../uploadfile/'.$gbkname);
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
					$url=$arr['original_pic'];
					$text=array(
						'name'  =>  $name,
						'path'  =>  $url,
						'size'  =>  $file['size'][$i],
						'type'  =>  $ext,
						'mime'  =>  '@'.dirname(__FILE__).'/../uploadfile/'.$utfname
					);
					$insertData = array(
						'title'   =>  $name,
						'slug'   =>  $name,
						'parent'   =>  $cid,
						'created'     =>  $time,
						'modified'     =>  $time,
						'text'     =>  serialize($text),
						'authorId'     =>  $uid,
						'type'     =>  'attachment',
						'status'     =>  'publish',
						'allowComment'     =>  '1',
						'allowFeed'     =>  '1'
					);
					$insert = $db->insert('table.contents')->rows($insertData);
					$insertId = $db->query($insert);
					$code=100;
				}else{
					$code=-5;
					$url="";
				}
			}
		}else if($option->albumtype=="ali"){
			if(@$file['size'][$i]<=1024*1024*3){
				$tempfilename = iconv("utf-8", "gbk", @$file['name'][$i]);
				move_uploaded_file(@$file['tmp_name'][$i], dirname(__FILE__).'/../uploadfile/'.$tempfilename);
				$ch = curl_init();
				$filePath = dirname(__FILE__).'/../uploadfile/'.$tempfilename;
				$data = array('file' => "multipart", 'Filedata' => '@' . $filePath);
				if (class_exists('\CURLFile')) {
					$data['Filedata'] = new \CURLFile(realpath($filePath));
				} else {
					if (defined('CURLOPT_SAFE_UPLOAD')) {
						curl_setopt($ch, CURLOPT_SAFE_UPLOAD, FALSE);
					}
				}
				curl_setopt($ch, CURLOPT_URL, 'https://api.uomg.com/api/image.ali');
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				$json=curl_exec($ch);
				curl_close($ch);
				@unlink(dirname(__FILE__).'/../uploadfile/'.$tempfilename);
				$arr=json_decode($json,true);
				$picname=$arr['data']['fs_url'];
				$url=$option->aliprefix . $picname;
				
				if(isset($picname)){
					$text=array(
						'name'  =>  $name,
						'path'  =>  $url,
						'size'  =>  $file['size'][$i],
						'type'  =>  $ext,
						'mime'  =>  "image/jpeg"
					);
					$insertData = array(
						'title'   =>  $name,
						'slug'   =>  $name,
						'parent'   =>  $cid,
						'created'     =>  $time,
						'modified'     =>  $time,
						'text'     =>  serialize($text),
						'authorId'     =>  $uid,
						'type'     =>  'attachment',
						'status'     =>  'publish',
						'allowComment'     =>  '1',
						'allowFeed'     =>  '1'
					);
					$insert = $db->insert('table.contents')->rows($insertData);
					$insertId = $db->query($insert);
					$code=100;
				}else{
					$code=-5;
					$url="";
				}
			}
		}else if($option->albumtype=="qihu"){
			if(@$file['size'][$i]<=1024*1024*3){
				$tempfilename = iconv("utf-8", "gbk", @$file['name'][$i]);
				move_uploaded_file(@$file['tmp_name'][$i], dirname(__FILE__).'/../uploadfile/'.$tempfilename);
				$ch = curl_init();
				$filePath = dirname(__FILE__).'/../uploadfile/'.$tempfilename;
				$data = array('file' => "multipart", 'Filedata' => '@' . $filePath);
				if (class_exists('\CURLFile')) {
					$data['Filedata'] = new \CURLFile(realpath($filePath));
				} else {
					if (defined('CURLOPT_SAFE_UPLOAD')) {
						curl_setopt($ch, CURLOPT_SAFE_UPLOAD, FALSE);
					}
				}
				curl_setopt($ch, CURLOPT_URL, 'https://api.uomg.com/api/image.360');
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				$json=curl_exec($ch);
				curl_close($ch);
				@unlink(dirname(__FILE__).'/../uploadfile/'.$tempfilename);
				$arr=json_decode($json,true);
				if(isset($arr['imgurl'])){
					$imgurls=explode("/",$arr['imgurl']);
					$url=$option->qihuprefix.$imgurls[count($imgurls)-1];
					$text=array(
						'name'  =>  $name,
						'path'  =>  $url,
						'size'  =>  $file['size'][$i],
						'type'  =>  $ext,
						'mime'  =>  "image/jpeg"
					);
					$insertData = array(
						'title'   =>  $name,
						'slug'   =>  $name,
						'parent'   =>  $cid,
						'created'     =>  $time,
						'modified'     =>  $time,
						'text'     =>  serialize($text),
						'authorId'     =>  $uid,
						'type'     =>  'attachment',
						'status'     =>  'publish',
						'allowComment'     =>  '1',
						'allowFeed'     =>  '1'
					);
					$insert = $db->insert('table.contents')->rows($insertData);
					$insertId = $db->query($insert);
					$code=100;
				}else{
					$code=-5;
					$url="";
				}
			}
		}else if($option->albumtype=="jd"){
			if(@$file['size'][$i]<=1024*1024*3){
				$tempfilename = iconv("utf-8", "gbk", @$file['name'][$i]);
				move_uploaded_file(@$file['tmp_name'][$i], dirname(__FILE__).'/../uploadfile/'.$tempfilename);
				$ch = curl_init();
				$filePath = dirname(__FILE__).'/../uploadfile/'.$tempfilename;
				$data = array('file' => "multipart", 'Filedata' => '@' . $filePath);
				if (class_exists('\CURLFile')) {
					$data['Filedata'] = new \CURLFile(realpath($filePath));
				} else {
					if (defined('CURLOPT_SAFE_UPLOAD')) {
						curl_setopt($ch, CURLOPT_SAFE_UPLOAD, FALSE);
					}
				}
				curl_setopt($ch, CURLOPT_URL, 'https://api.uomg.com/api/image.jd');
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				$json=curl_exec($ch);
				curl_close($ch);
				@unlink(dirname(__FILE__).'/../uploadfile/'.$tempfilename);
				$arr=json_decode($json,true);
				if(isset($arr['imgurl'])){
					$imgurls=explode("/",$arr['imgurl']);
					$url=$option->jdprefix.$imgurls[6]."/".$imgurls[7]."/".$imgurls[8]."/".$imgurls[9]."/".$imgurls[10]."/".$imgurls[11];
					$text=array(
						'name'  =>  $name,
						'path'  =>  $url,
						'size'  =>  $file['size'][$i],
						'type'  =>  $ext,
						'mime'  =>  "image/jpeg"
					);
					$insertData = array(
						'title'   =>  $name,
						'slug'   =>  $name,
						'parent'   =>  $cid,
						'created'     =>  $time,
						'modified'     =>  $time,
						'text'     =>  serialize($text),
						'authorId'     =>  $uid,
						'type'     =>  'attachment',
						'status'     =>  'publish',
						'allowComment'     =>  '1',
						'allowFeed'     =>  '1'
					);
					$insert = $db->insert('table.contents')->rows($insertData);
					$insertId = $db->query($insert);
					$code=100;
				}else{
					$code=-5;
					$url="";
				}
			}
		}
		$imgs[$key]["code"]=$code;
		$imgs[$key]["name"]=$name;
		$imgs[$key]["url"]=$url;
		$key++;
		if($option->issavealbum=="y"){
			break;
		}
	}
	foreach($imgs as $k=>$v){
		foreach ( $imgs[$k] as $key => $value ) {
			$imgs[$k][$key] = urlencode ( $value );  
		} 
	}
	$json=urldecode(json_encode($imgs));
	echo $json;
	exit;
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