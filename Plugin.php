<?php
/**
 * 1、将 Typecho 的附件上传至新浪微博云存储中，无需申请appid，不占用服务器大小，可永久保存，只需一个不会登录的微博小号即可；
 * 2、在图床的基础上新增上传视频和视频解析的功能；
 * 3、新增前台微博图床上传。
 * @package WeiboFile For Typecho
 * @author 二呆
 * @version 1.0.9
 * @link http://www.tongleer.com/
 * @date 2018-12-30
 */
date_default_timezone_set('Asia/Shanghai');
require __DIR__ . '/include/Sinaupload.php';

class WeiboFile_Plugin implements Typecho_Plugin_Interface{
    // 激活插件
    public static function activate(){
		$db = Typecho_Db::get();
		//图片
        Typecho_Plugin::factory('Widget_Upload')->uploadHandle = array('WeiboFile_Plugin', 'uploadHandle');
        Typecho_Plugin::factory('Widget_Upload')->modifyHandle = array('WeiboFile_Plugin', 'modifyHandle');
        Typecho_Plugin::factory('Widget_Upload')->deleteHandle = array('WeiboFile_Plugin', 'deleteHandle');
        Typecho_Plugin::factory('Widget_Upload')->attachmentHandle = array('WeiboFile_Plugin', 'attachmentHandle');
		//视频
		Typecho_Plugin::factory('admin/write-post.php')->bottom = array('WeiboFile_Plugin', 'videoInsert');
		Typecho_Plugin::factory('admin/write-page.php')->bottom = array('WeiboFile_Plugin', 'videoInsert');
		Typecho_Plugin::factory('admin/write-post.php')->option = array('WeiboFile_Plugin', 'videoList');
		Typecho_Plugin::factory('admin/write-page.php')->option = array('WeiboFile_Plugin', 'videoList');
        return _t('插件已经激活，需先配置微博图床的信息！');
    }

    // 禁用插件
    public static function deactivate(){
		//删除页面模板
		$db = Typecho_Db::get();
		$queryTheme= $db->select('value')->from('table.options')->where('name = ?', 'theme'); 
		$rowTheme = $db->fetchRow($queryTheme);
		@unlink(dirname(__FILE__).'/../../themes/'.$rowTheme['value'].'/page_weibofile_videoupload.php');
		@unlink(dirname(__FILE__).'/../../themes/'.$rowTheme['value'].'/page_weibofile_webimgupload.php');
        return _t('插件已被禁用');
    }
	
	public static function videoList(){
		$options = Helper::options();
        $ajaxurl = Typecho_Common::url('WeiboFile' , $options->pluginUrl);
		$uid=Typecho_Cookie::get('__typecho_uid');
		include dirname(__FILE__).'/templates/videoList.php';
	}

	public static function videoInsert(){
		$options = Helper::options();
        $ajaxurl = Typecho_Common::url('WeiboFile/ajax/videoInsert.php' , $options->pluginUrl);
		$uid=Typecho_Cookie::get('__typecho_uid');
        include dirname(__FILE__).'/templates/videoInsert.php';
	}

    // 插件配置面板
    public static function config(Typecho_Widget_Helper_Form $form){
		//版本检查
		$version=file_get_contents('https://tongleer.com/api/interface/WeiboFile.php?action=update&version=9');
		$headDiv=new Typecho_Widget_Helper_Layout();
		$headDiv->html('<small>版本检查：'.$version.'</small>');
		$headDiv->render();
		
		$db = Typecho_Db::get();
		$prefix = $db->getPrefix();
		$options = Typecho_Widget::widget('Widget_Options');
		$plug_url = $options->pluginUrl;
		
        $weibouser = new Typecho_Widget_Helper_Form_Element_Text('weibouser', null, '', _t('微博小号用户名'), _t('备注：设置后可多尝试多上传几次，上传成功尽量不要将此微博小号登录微博系的网站、软件，可以登录，但不确定会不会上传失败，上传失败了再重新上传2次同样可以正常上传，如果小号等级过低，可尝试微博大号，插件可正常使用，无需担心。'));
        $form->addInput($weibouser->addRule('required', _t('微博小号用户名不能为空！')));

        $weibopass = new Typecho_Widget_Helper_Form_Element_Password('weibopass', null, '', _t('微博小号密码'));
        $form->addInput($weibopass->addRule('required', _t('微博小号密码不能为空！')));
		
		$isuploadlocal = new Typecho_Widget_Helper_Form_Element_Radio('isuploadlocal', array(
            'y'=>_t('是'),
            'n'=>_t('否')
        ), 'y', _t('非图片本地上传'), _t("1、启用后可支持上传其他格式文件到本地服务器。<br />2、使用Typecho自带的附件上传文件时，如果长时间没有弹出网址窗口可尝试刷新一次页面即可。"));
		$form->addInput($isuploadlocal->addRule('enum', _t(''), array('y', 'n')));
		//图片
		$webimgupload = new Typecho_Widget_Helper_Form_Element_Radio('webimgupload', array(
            'y'=>_t('启用'),
            'n'=>_t('禁用')
        ), 'n', _t('前台图床'), _t("启用后开启前台图床上传，如有将图床模块嵌入网页的需求，则可以将代码<font color='blue'>&lt;?php include('page_weibofile_webimgupload.php'); ?></font>放于侧边拦或其他合适代码位置。"));
		$form->addInput($webimgupload->addRule('enum', _t(''), array('y', 'n')));
		$webimgupload = @isset($_POST['webimgupload']) ? addslashes(trim($_POST['webimgupload'])) : '';
		self::moduleImagesUpload($db,$webimgupload);
		
		$webimgheight = new Typecho_Widget_Helper_Form_Element_Text('webimgheight', array('value'), '300', _t('前台图床自定义高度'), _t('设置合适的高度可有利于前台展示。'));
        $form->addInput($webimgheight);
		
		$webimgbg = new Typecho_Widget_Helper_Form_Element_Text('webimgbg', array('value'), 'https://ws3.sinaimg.cn/large/ecabade5ly1fxp3dil4pxj21hc0u0wn1.jpg', _t('前台图床默认背景'), _t('前台图床无上传时的背景。'));
        $form->addInput($webimgbg);
		//视频
		$videoupload = new Typecho_Widget_Helper_Form_Element_Radio('videoupload', array(
            'y'=>_t('启用'),
            'n'=>_t('禁用')
        ), 'n', _t('优酷视频上传'), _t("1、上传的视频需要优酷审核，并且因优酷官方会有需要作者每月手动刷新token才可上传的限制，故<font color='red'>不建议开启</font>。<br />2、使用Typecho自带的附件上传优酷视频时，如果长时间没有弹出网址窗口可尝试刷新一次页面即可。<br />3、为您做到心中有数，启用后会创建".$prefix."weibofile_videoupload数据表、page_weibofile_videoupload.php主题文件、视频上传页面3项，不启用则不会创建，不会添加任何无用项目且与其他插件互不冲突，谢谢支持。"));
        $form->addInput($videoupload->addRule('enum', _t(''), array('y', 'n')));
		$videoupload = @isset($_POST['videoupload']) ? addslashes(trim($_POST['videoupload'])) : '';
		self::moduleVideoUpload($db,$videoupload);
		//视频列表
		try{
			$queryVideoAdmin= $db->select()->from('table.weibofile_videoupload')->order('videoupinstime',Typecho_Db::SORT_DESC);
			$resultVideoAdmin = $db->fetchAll($queryVideoAdmin);
			if(count($resultVideoAdmin)>0){
				$footDiv = new Typecho_Widget_Helper_Layout();
				$divstr1='
					<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/amazeui/2.7.2/css/amazeui.min.css"/>
					<script src="https://apps.bdimg.com/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
					<script src="https://cdnjs.cloudflare.com/ajax/libs/amazeui/2.7.2/js/amazeui.min.js" type="text/javascript"></script>
					<div style="background-color:#fff;overflow:scroll; height:400px; width:100%; border: solid 0px #aaa; margin: 0 auto;">
					  <table class="am-table am-table-bordered am-table-striped am-table-compact">
						<caption><h4>视频管理</h4></caption>
						<thead>
							<tr>
								<th>所有者</th>
								<th>视频</th>
								<th>时间</th>
								<th>操作</th>
							</tr>
						</thead>
						<tbody>
						
				';
				$divstr2='';
				$divstr3='
						</tbody>
					</table>
				</div>
				<script>
					$(".videoShow").each(function(){
						var id=$(this).attr("id");
						$("#"+id).click( function () {
							$.post("'.$plug_url.'/WeiboFile/ajax/videoAdmin.php",{action:"videoShow",id:$(this).attr("data-id")},function(data){
								if(data==1){
									location.href="plugins.php";
								}else if(data==2){
									alert("此视频正在审核中，无法抓取。");
								}else{
									alert("已抓取不到，可以删除此视频。");
								}
							});
						});
					});
					$(".videoDel").each(function(){
						var id=$(this).attr("id");
						$("#"+id).click( function () {
							$.post("'.$plug_url.'/WeiboFile/ajax/videoAdmin.php",{action:"videoDel",id:$(this).attr("data-id")},function(data){
								location.href="plugins.php";
							});
						});
					});
				</script>
				';
				$videotemi=1;
				foreach($resultVideoAdmin as $value){
					$queryUser= $db->select()->from('table.users')->where('uid = ?', $value['videoupuid']); 
					$rowUser = $db->fetchRow($queryUser);
					$name=isset($rowUser['screenName'])?$rowUser['screenName']:$rowUser['name'];
					if($value['videouppic']==''){
						$videoCode='
							<a href="'.$value['videoupurl'].'" target="_blank">'.$value['videoupinstime'].'上传</a>
						';
						$videoShowCode='
							<a class="videoShow" id="videoShow'.$videotemi.'" data-id="'.$value['videoupid'].'" href="javascript:;">抓图</a>
						';
					}else{
						$videoCode='
							<a href="'.$value['videoupurl'].'" target="_blank"><img src="'.$value['videouppic'].'" /></a>
						';
						$videoShowCode='';
					}
					$videoDelCode='
						<a class="videoDel" id="videoDel'.$videotemi.'" data-id="'.$value['videoupid'].'" href="javascript:;">删除</a>
					';
					$divstr2.='
						<tr>
							<td>'.$name.'</td>
							<td>'.$videoCode.'</td>
							<td>'.$value['videoupinstime'].'</td>
							<td>'.$videoDelCode.'&nbsp;&nbsp;'.$videoShowCode.'</td>
						</tr>
					';
					$videotemi++;
				}
				$footDiv->html($divstr1.$divstr2.$divstr3);
				$footDiv->render();
			}
		}catch(Exception $e){}
    }
	
	/*前台图床方法*/
	public static function moduleImagesUpload($db,$webimgupload){
		switch($webimgupload){
			case 'y':
				//判断目录权限，并将插件文件写入主题目录
				self::funWriteThemePage($db,'page_weibofile_webimgupload.php');
				//如果数据表没有添加页面就插入
				self::funWriteDataPage($db,'前台图床','weibofile_webimgupload','page_weibofile_webimgupload.php');
				break;
		}
	}
	
	/*视频上传方法*/
	public static function moduleVideoUpload($db,$videoupload){
		switch($videoupload){
			case 'y':
				//创建视频上传所用数据表
				self::createTableVideoUpload($db);
				//判断目录权限，并将插件文件写入主题目录
				self::funWriteThemePage($db,'page_weibofile_videoupload.php');
				//如果数据表没有添加页面就插入
				self::funWriteDataPage($db,'视频上传','weibofile_videoupload','page_weibofile_videoupload.php');
				break;
		}
	}
	
	/*创建视频上传所用数据表*/
	public static function createTableVideoUpload($db){
		$prefix = $db->getPrefix();
		//$db->query('DROP TABLE IF EXISTS '.$prefix.'weibofile_videoupload');
		$db->query('CREATE TABLE IF NOT EXISTS `'.$prefix.'weibofile_videoupload` (
		  `videoupid` varchar(64) COLLATE utf8_general_ci NOT NULL,
		  `videoupuid` bigint(20) DEFAULT NULL,
		  `videoupurl` varchar(255) COLLATE utf8_general_ci DEFAULT NULL,
		  `videouppic` varchar(255) COLLATE utf8_general_ci DEFAULT NULL,
		  `videoupinstime` datetime DEFAULT NULL,
		  `videouptype` enum("youku") COLLATE utf8_general_ci DEFAULT "youku",
		  PRIMARY KEY (`videoupid`)
		) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;');
	}
	
	/*公共方法：将页面写入数据库*/
	public static function funWriteDataPage($db,$title,$slug,$template){
		$query= $db->select('slug')->from('table.contents')->where('template = ?', $template); 
		$row = $db->fetchRow($query);
		if(count($row)==0){
			$contents = array(
				'title'      =>  $title,
				'slug'      =>  $slug,
				'created'   =>  time(),
				'text'=>  '<!--markdown-->',
				'password'  =>  '',
				'authorId'     =>  Typecho_Cookie::get('__typecho_uid'),
				'template'     =>  $template,
				'type'     =>  'page',
				'status'     =>  'hidden',
			);
			$insert = $db->insert('table.contents')->rows($contents);
			$insertId = $db->query($insert);
			$slug=$contents['slug'];
		}else{
			$slug=$row['slug'];
		}
	}
	
	/*公共方法：将页面写入主题目录*/
	public static function funWriteThemePage($db,$filename){
		$queryTheme= $db->select('value')->from('table.options')->where('name = ?', 'theme'); 
		$rowTheme = $db->fetchRow($queryTheme);
		if(!is_writable(dirname(__FILE__).'/../../themes/'.$rowTheme['value'])){
			Typecho_Widget::widget('Widget_Notice')->set(_t('主题目录不可写，请更改目录权限。'.__TYPECHO_THEME_DIR__.'/'.$rowTheme['value']), 'success');
		}
		if(!file_exists(dirname(__FILE__).'/../../themes/'.$rowTheme['value']."/".$filename)){
			$regfile = fopen(dirname(__FILE__)."/page/".$filename, "r") or die("不能读取".$filename."文件");
			$regtext=fread($regfile,filesize(dirname(__FILE__)."/page/".$filename));
			fclose($regfile);
			$regpage = fopen(dirname(__FILE__).'/../../themes/'.$rowTheme['value']."/".$filename, "w") or die("不能写入".$filename."文件");
			fwrite($regpage, $regtext);
			fclose($regpage);
		}
	}

    // 个人用户配置面板
    public static function personalConfig(Typecho_Widget_Helper_Form $form){
    }

    // 获得插件配置信息
    public static function getConfig(){
        return Typecho_Widget::widget('Widget_Options')->plugin('WeiboFile');
    }

    // 删除文件
    public static function deleteFile($filepath){
		@unlink(dirname(__FILE__).'/../../..'.$filepath);
        return true;
    }

    // 上传文件
    public static function uploadFile($file, $content = null){
        // 获取上传文件
        if (empty($file['name'])) return false;

        // 校验扩展名
        $part = explode('.', $file['name']);
        $ext = (($length = count($part)) > 1) ? strtolower($part[$length-1]) : '';
        if (!Widget_Upload::checkFileType($ext)) return false;

        // 获取插件配置
        $option = self::getConfig();
        $date = new Typecho_Date(Typecho_Widget::widget('Widget_Options')->gmtTime);

        // 上传文件
        $filename = $file['tmp_name'];
        if (!isset($filename)) return false;
		
		$db = Typecho_Db::get();
		$prefix = $db->getPrefix();
		$query= "select * from information_schema.columns WHERE table_name = '".$prefix."weibofile_videoupload'";
		$row = $db->fetchRow($query);
		
		if(in_array($ext,array('gif','jpg','jpeg','png','bmp'))){
			$Sinaupload=new Sinaupload('');
			$cookie=$Sinaupload->login($option->weibouser,$option->weibopass);
			$result=$Sinaupload->upload($filename);
			$arr = json_decode($result,true);
			return array(
				'name'  =>  $file['name'],
				'path'  =>  $arr['data']['pics']['pic_1']['pid'],
				'size'  =>  $file['size'],
				'type'  =>  $ext,
				'mime'  =>  Typecho_Common::mimeContentType($filename)
			);
		}else if(in_array($ext,array('wmv', 'avi', 'dat','asf','rm','rmvb','ram','mpg','mpeg','3gp','mov','mp4','m4v','dvix','dv','vob','mkv','vob','ram','qt','divx','cpk','fli','flc','mod','flv'))){
			if(count($row)>0&&$option->videoupload=='y'){
				$uid=Typecho_Cookie::get('__typecho_uid');
				$db = Typecho_Db::get();
				$prefix = $db->getPrefix();
				$filename = iconv("utf-8", "gbk", $file['name']);
				move_uploaded_file($file['tmp_name'], dirname(__FILE__).'/'.$filename);
				$ch = curl_init();
				$filePath = dirname(__FILE__).'/'.$filename;
				$data = array('action' => 'uploadMovie', 'movieFile' => '@' . $filePath);
				if (class_exists('\CURLFile')) {
					$data['movieFile'] = new \CURLFile(realpath($filePath));
				} else {
					if (defined('CURLOPT_SAFE_UPLOAD')) {
						curl_setopt($ch, CURLOPT_SAFE_UPLOAD, FALSE);
					}
				}
				curl_setopt($ch, CURLOPT_URL, 'https://tongleer.com/me/mob/app/wap/json/blogjson.php');
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
						'videoupinstime'     =>  date('Y-m-d H:i:s',time()),
						'videouptype'     =>  'youku'
					);
					$insert = $db->insert('table.weibofile_videoupload')->rows($insertData);
					$insertId = $db->query($insert);
					return array(
						'name'  =>  $file['name'],
						'path'  =>  $arr['video_id'],
						'size'  =>  $file['size'],
						'type'  =>  $ext,
						'mime'  =>  Typecho_Common::mimeContentType($file['tmp_name'])
					);
				}else{
					return array(
						'name'  =>  $file['name'],
						'path'  =>  '上传失败',
						'size'  =>  $file['size'],
						'type'  =>  $ext,
						'mime'  =>  Typecho_Common::mimeContentType($file['tmp_name'])
					);
				}
			}else{
				return self::uploadLocal($db,$file, $content = null,$ext,$option);
			}
		}else{
			return self::uploadLocal($db,$file, $content = null,$ext,$option);
		}
    }
	
	// 上传文件到本地
    public static function uploadLocal($db,$file, $content = null,$ext,$option){
        if($option->isuploadlocal=='y'){
			$dirtime=date("Y")."/".date("m").'/';
			$dir=__TYPECHO_ROOT_DIR__ . "/usr/uploads/".$dirtime."/";
			if(!file_exists($dir)){
				mkdir($dir);
			}
			$dirupload="/usr/uploads/".$dirtime."/";
			$pathgbk = iconv("utf-8", "gbk", $dir.$file['name']);
			move_uploaded_file($file['tmp_name'], $pathgbk);
			return array(
				'name'  =>  $file['name'],
				'path'  =>  $dirupload.$file['name'],
				'size'  =>  $file['size'],
				'type'  =>  $ext,
				'mime'  =>  Typecho_Common::mimeContentType($file['tmp_name'])
			);
		}else{
			return array(
				'name'  =>  $file['name'],
				'path'  =>  '未开启本地上传',
				'size'  =>  $file['size'],
				'type'  =>  $ext,
				'mime'  =>  Typecho_Common::mimeContentType($file['tmp_name'])
			);
		}
    }

    // 上传文件处理函数
    public static function uploadHandle($file){
        return self::uploadFile($file);
    }

    // 修改文件处理函数
    public static function modifyHandle($content, $file){
        return self::uploadFile($file, $content);
    }

    // 删除文件
    public static function deleteHandle(array $content){
        self::deleteFile($content['attachment']->path);
    }

    // 获取实际文件绝对访问路径
    public static function attachmentHandle(array $content){
        $option = self::getConfig();
		$options = Typecho_Widget::widget('Widget_Options');
		$plug_url = $options->pluginUrl;
		//http://player.youku.com/embed/
		$path=$content['attachment']->path;
		$part = explode('.', $content['attachment']->name);
        $ext = (($length = count($part)) > 1) ? strtolower($part[$length-1]) : '';
		if(strpos($path,"==")){
			return Typecho_Common::url($content['attachment']->path.'', 'https://player.youku.com/embed/');
		}else if(in_array($ext,array('gif','jpg','jpeg','png','bmp'))){
			return Typecho_Common::url($content['attachment']->path.'.jpg', 'https://ws3.sinaimg.cn/large/');
		}else{
			return Typecho_Common::url($content['attachment']->path, $plug_url.'/../..');
		}
    }
}