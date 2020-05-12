<?php
date_default_timezone_set('Asia/Shanghai');
include '../../../../config.inc.php';
require dirname(__FILE__) . '/../include/Sinaupload.php';

$db = Typecho_Db::get();
$prefix = $db->getPrefix();
$options = Typecho_Widget::widget('Widget_Options');
$option=$options->plugin('WeiboFile');
$plug_url = $options->pluginUrl;

$action = isset($_POST['action']) ? addslashes($_POST['action']) : '';
if($action=='updateALTCLinks'){
	/*转换阿里图床链接（未完成）*/
	$postid = isset($_POST['postid']) ? addslashes($_POST['postid']) : '';
	
	$query= $db->select()->from('table.contents')->where('cid = ?', $postid); 
	$row = $db->fetchRow($query);
	$post_content=$row["text"];
	
	$aliprefix=str_replace("/","\/",$option->aliprefix);
	$aliprefix=str_replace(".","\.",$aliprefix);
	
	preg_match_all( "/\!\[.*\]\((?!".$aliprefix.")((.*?)((.gif)|(.jpg)|(.bmp)|(.png)|(.GIF)|(.JPG)|(.PNG)|(.BMP)))\)/", $post_content, $submatches );
	if(isset($submatches[1])){
		foreach($submatches[1] as $url){
			$httpData=array("action"=>"weiboimg","imgurl"=>$url);
			$arr = WeiboFile_Plugin::httpClient("https://www.tongleer.com/api/web/",$httpData);
			if(isset($arr['data']['src'])){
				$imgurl="".$option->weiboprefix.$arr['data']['pics']['pic_1']['pid'].".jpg";
				$post_content=str_replace($url,$imgurl,$post_content);
				
				if(strpos($url,$options->siteUrl)!== false){
					$path=str_replace($options->siteUrl,"",$url);
					$oldpath=$plug_url."/../..".$path;
					@unlink($oldpath);
				}
			}
		}
	}
	
	preg_match_all( "/\[\d\]:\s(?!".$aliprefix.")((.*?)((.gif)|(.jpg)|(.bmp)|(.png)|(.GIF)|(.JPG)|(.PNG)|(.BMP)))\n?/", $post_content, $submatches );
	if(isset($submatches[1])){
		foreach($submatches[1] as $url){
			$httpData=array("action"=>"weiboimg","imgurl"=>$url);
			$arr = WeiboFile_Plugin::httpClient("https://www.tongleer.com/api/web/",$httpData);
			if(isset($arr['data']['src'])){
				$imgurl=$option->aliprefix.basename($arr['data']["src"]);
				$post_content=str_replace($url,$imgurl,$post_content);
				
				if(strpos($url,$options->siteUrl)!== false){
					$path=str_replace($options->siteUrl,"",$url);
					$oldpath=$plug_url."/../..".$path;
					@unlink($oldpath);
				}
			}
		}
	}
	
	preg_match_all( "/<(img|IMG).*?src=[\'|\"](?!".$aliprefix.")(.*?)[\'|\"].*?[\/]?>/", $post_content, $submatches );
	if(isset($submatches[2])){
		foreach($submatches[2] as $url){
			$httpData=array("action"=>"weiboimg","imgurl"=>$url);
			$arr = WeiboFile_Plugin::httpClient("https://www.tongleer.com/api/web/",$httpData);
			if(isset($arr['data']["src"])){
				$imgurl=$option->aliprefix.basename($arr['data']["src"]);
				$post_content=str_replace($url,$imgurl,$post_content);
				
				if(strpos($url,$options->siteUrl)!== false){
					$path=str_replace($options->siteUrl,"",$url);
					$oldpath=$plug_url."/../..".$path;
					@unlink($oldpath);
				}
			}
		}
	}
	
	$updateItem = $db->update('table.contents')->rows(array('text'=>$post_content))->where('cid=?',$postid);
	$updateItemRows= $db->query($updateItem);
	$json=json_encode(array("status"=>"ok","msg"=>"转换成功"));
	echo $json;
}else if($action=='updateWBTCLinks'){
	/*转换微博图床链接*/
	if(!isset($option->weibouser) || !isset($option->weibopass)){
		$json=json_encode(array("status"=>"noneconfig","msg"=>"请先配置微博小号"));
		echo $json;
		exit;
	}
	$postid = isset($_POST['postid']) ? addslashes($_POST['postid']) : '';
	
	$query= $db->select()->from('table.contents')->where('cid = ?', $postid); 
	$row = $db->fetchRow($query);
	$post_content=$row["text"];
	/*
	if(strpos($post_content, '<!--markdown-->')===0){
		$post_content=substr($post_content,15);
	}
	$post_content = Markdown::convert($post_content);
	*/
	$weiboprefix=str_replace("/","\/",$option->weiboprefix);
	$weiboprefix=str_replace(".","\.",$weiboprefix);
	
	$savepath=dirname(__FILE__)."/x.jpg";
	
	preg_match_all( "/<(img|IMG).*?src=[\'|\"](?!".$weiboprefix.")(.*?)[\'|\"].*?[\/]?>/", $post_content, $submatches );
	if(isset($submatches[2])){
		foreach($submatches[2] as $url){
			$html = file_get_contents($url);
			file_put_contents($savepath, $html);
			$Sinaupload=new Sinaupload('');
			$cookie=$Sinaupload->login($option->weibouser,$option->weibopass);
			$result=$Sinaupload->upload($savepath);
			$arr = json_decode($result,true);
			if(isset($arr['data']['pics']['pic_1']['pid'])){
				$imgurl="".$option->weiboprefix.$arr['data']['pics']['pic_1']['pid'].".jpg";
				$post_content=str_replace($url,$imgurl,$post_content);
				
				if(strpos($url,$options ->siteUrl)!== false){
					$path=str_replace($options ->siteUrl,"",$url);
					$oldpath=__TYPECHO_ROOT_DIR__.__TYPECHO_PLUGIN_DIR__."/../../".$path;
					@unlink($oldpath);
				}
			}
		}
	}
	
	preg_match_all( "/\!\[.*\]\((?!".$weiboprefix.")((.*?)((.gif)|(.jpg)|(.bmp)|(.png)|(.GIF)|(.JPG)|(.PNG)|(.BMP)))\)/", $post_content, $submatches );
	if(isset($submatches[1])){
		foreach($submatches[1] as $url){
			$html = file_get_contents($url);
			file_put_contents($savepath, $html);
			$Sinaupload=new Sinaupload('');
			$cookie=$Sinaupload->login($option->weibouser,$option->weibopass);
			$result=$Sinaupload->upload($savepath);
			$arr = json_decode($result,true);
			if(isset($arr['data']['pics']['pic_1']['pid'])){
				$imgurl="".$option->weiboprefix.$arr['data']['pics']['pic_1']['pid'].".jpg";
				$post_content=str_replace($url,$imgurl,$post_content);
				
				if(strpos($url,$options ->siteUrl)!== false){
					$path=str_replace($options ->siteUrl,"",$url);
					$oldpath=__TYPECHO_ROOT_DIR__.__TYPECHO_PLUGIN_DIR__."/../../".$path;
					@unlink($oldpath);
				}
			}
		}
	}
	
	preg_match_all( "/\[\d\]:\s(?!".$weiboprefix.")((.*?)((.gif)|(.jpg)|(.bmp)|(.png)|(.GIF)|(.JPG)|(.PNG)|(.BMP)))\n?/", $post_content, $submatches );
	if(isset($submatches[1])){
		foreach($submatches[1] as $url){
			$html = file_get_contents($url);
			file_put_contents($savepath, $html);
			$Sinaupload=new Sinaupload('');
			$cookie=$Sinaupload->login($option->weibouser,$option->weibopass);
			$result=$Sinaupload->upload($savepath);
			$arr = json_decode($result,true);
			if(isset($arr['data']['pics']['pic_1']['pid'])){
				$imgurl="".$option->weiboprefix.$arr['data']['pics']['pic_1']['pid'].".jpg";
				$post_content=str_replace($url,$imgurl,$post_content);
				
				if(strpos($url,$options ->siteUrl)!== false){
					$path=str_replace($options ->siteUrl,"",$url);
					$oldpath=__TYPECHO_ROOT_DIR__.__TYPECHO_PLUGIN_DIR__."/../../".$path;
					@unlink($oldpath);
				}
			}
		}
	}
	
	$updateItem = $db->update('table.contents')->rows(array('text'=>$post_content))->where('cid=?',$postid);
	$updateItemRows= $db->query($updateItem);
	@unlink($savepath);
	$json=json_encode(array("status"=>"ok","msg"=>"转换成功"));
	echo $json;
}else if($action=="localWBTCLinks"){
	/*本地化微博图床链接*/
	$time=time();
	$uploaddir=date("Y")."/".date("m")."/";
	if(!is_dir(dirname(__FILE__)."/../../../uploads/".$uploaddir)){
		mkdir (dirname(__FILE__)."/../../../uploads/".$uploaddir, 0777, true );
	}
	
	$postid = isset($_POST['postid']) ? addslashes($_POST['postid']) : '';
	
	$query= $db->select()->from('table.contents')->where('cid = ?', $postid); 
	$row = $db->fetchRow($query);
	$post_content=$row["text"];
	
	$siteUrl=str_replace("/","\/",$options ->siteUrl);
	$siteUrl=str_replace(".","\.",$siteUrl);
	
	preg_match_all( "/<(img|IMG).*?src=[\'|\"](?!".$siteUrl.")(.*?)[\'|\"].*?[\/]?>/", $post_content, $localmatches );
	if(isset($localmatches[2])){
		foreach($localmatches[2] as $url){
			$basename=basename($url);
			if(strpos($basename,"?")!== false){
				$basename=explode("?",$basename)[0];
			}
			$uploadfile=$time.$basename.".png";
			$html = file_get_contents($url);
			file_put_contents(dirname(__FILE__)."/../../../uploads/".$uploaddir.$uploadfile, $html);
			$imgurl=$options ->siteUrl."usr/uploads/".$uploaddir.$uploadfile;
			$post_content=str_replace($url,$imgurl,$post_content);
			
			$text=array(
				'name'  =>  $uploadfile,
				'path'  =>  str_replace($options->siteUrl,"",$imgurl),
				'size'  =>  0,
				'type'  =>  "png",
				'mime'  =>  "image/png"
			);
			$insertData = array(
				'title'   =>  $uploadfile,
				'slug'   =>  $uploadfile,
				'parent'   =>  $postid,
				'created'     =>  $time,
				'modified'     =>  $time,
				'text'     =>  serialize($text),
				'authorId'     =>  $row["authorId"],
				'type'     =>  'attachment',
				'status'     =>  'publish',
				'allowComment'     =>  '1',
				'allowFeed'     =>  '1'
			);
			$insert = $db->insert('table.contents')->rows($insertData);
			$insertId = $db->query($insert);
		}
	}
	
	preg_match_all( "/\!\[.*\]\((?!".$siteUrl.")((.*?)((.gif)|(.jpg)|(.bmp)|(.png)|(.GIF)|(.JPG)|(.PNG)|(.BMP)))\)/", $post_content, $localmatches );
	if(isset($localmatches[1])){
		foreach($localmatches[1] as $url){
			$basename=basename($url);
			if(strpos($basename,"?")!== false){
				$basename=explode("?",$basename)[0];
			}
			$uploadfile=$time.$basename.".png";
			$html = file_get_contents($url);
			file_put_contents(dirname(__FILE__)."/../../../uploads/".$uploaddir.$uploadfile, $html);
			$imgurl=$options ->siteUrl."usr/uploads/".$uploaddir.$uploadfile;
			$post_content=str_replace($url,$imgurl,$post_content);
			
			$text=array(
				'name'  =>  $uploadfile,
				'path'  =>  str_replace($options->siteUrl,"",$imgurl),
				'size'  =>  0,
				'type'  =>  "png",
				'mime'  =>  "image/png"
			);
			$insertData = array(
				'title'   =>  $uploadfile,
				'slug'   =>  $uploadfile,
				'parent'   =>  $postid,
				'created'     =>  $time,
				'modified'     =>  $time,
				'text'     =>  serialize($text),
				'authorId'     =>  $row["authorId"],
				'type'     =>  'attachment',
				'status'     =>  'publish',
				'allowComment'     =>  '1',
				'allowFeed'     =>  '1'
			);
			$insert = $db->insert('table.contents')->rows($insertData);
			$insertId = $db->query($insert);
		}
	}
	
	preg_match_all( "/\[\d\]:\s*(?!".$siteUrl.")((.*?)((.gif)|(.jpg)|(.bmp)|(.png)|(.GIF)|(.JPG)|(.PNG)|(.BMP)))\n?/", $post_content, $localmatches );
	if(isset($localmatches[1])){
		foreach($localmatches[1] as $url){
			$basename=basename($url);
			if(strpos($basename,"?")!== false){
				$basename=explode("?",$basename)[0];
			}
			$uploadfile=$time.$basename.".png";
			$html = file_get_contents($url);
			file_put_contents(dirname(__FILE__)."/../../../uploads/".$uploaddir.$uploadfile, $html);
			$imgurl=$options ->siteUrl."usr/uploads/".$uploaddir.$uploadfile;
			$post_content=str_replace($url,$imgurl,$post_content);
			
			$text=array(
				'name'  =>  $uploadfile,
				'path'  =>  str_replace($options->siteUrl,"",$imgurl),
				'size'  =>  0,
				'type'  =>  "png",
				'mime'  =>  "image/png"
			);
			$insertData = array(
				'title'   =>  $uploadfile,
				'slug'   =>  $uploadfile,
				'parent'   =>  $postid,
				'created'     =>  $time,
				'modified'     =>  $time,
				'text'     =>  serialize($text),
				'authorId'     =>  $row["authorId"],
				'type'     =>  'attachment',
				'status'     =>  'publish',
				'allowComment'     =>  '1',
				'allowFeed'     =>  '1'
			);
			$insert = $db->insert('table.contents')->rows($insertData);
			$insertId = $db->query($insert);
		}
	}
	
	$updateItem = $db->update('table.contents')->rows(array('text'=>$post_content))->where('cid=?',$postid);
	$updateItemRows= $db->query($updateItem);
	$json=json_encode(array("status"=>"ok","msg"=>"本地化成功"));
	echo $json;
}
?>