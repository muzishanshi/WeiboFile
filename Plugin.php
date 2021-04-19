<?php
/**
 * WeiboFile插件源于新浪图床(已使用微博官方api实现)，而后扩展了阿里图床、京东图床、360图床、新浪同步等功能，因技术有限，若存在bug欢迎邮件反馈，方能逐步升级，不过此插件只推荐使用微博官方API进行上传微博图床，其他图床能用就用，不能就不用，若想用其他图床，则请前往https://image.kieng.cn/或https://pic.onji.cn/进行上传。<div class="WeiboFileSet"><br /><a href="javascript:;" title="插件因兴趣于闲暇时间所写，故会有代码不规范、不专业和bug的情况，但完美主义促使代码还说得过去，如有bug或使用问题进行反馈即可。">鼠标轻触查看备注</a>&nbsp;<a href="http://club.tongleer.com" target="_blank">论坛</a>&nbsp;<a href="https://www.tongleer.com/api/web/pay.png" target="_blank">打赏</a>&nbsp;<a href="http://mail.qq.com/cgi-bin/qm_share?t=qm_mailme&email=diamond0422@qq.com" target="_blank">反馈</a></div><style>.WeiboFileSet a{background: #4DABFF;padding: 5px;color: #fff;}</style>
 * @package WeiboFile For Typecho
 * @author 二呆
 * @version 1.0.20<br /><span id="WeiboFileUpdateInfo"></span><script>WeiboFileXmlHttp=new XMLHttpRequest();WeiboFileXmlHttp.open("GET","https://www.tongleer.com/api/interface/WeiboFile.php?action=update&version=20",true);WeiboFileXmlHttp.send(null);WeiboFileXmlHttp.onreadystatechange=function () {if (WeiboFileXmlHttp.readyState ==4 && WeiboFileXmlHttp.status ==200){document.getElementById("WeiboFileUpdateInfo").innerHTML=WeiboFileXmlHttp.responseText;}}</script>
 * @link http://www.tongleer.com/
 * @date 2020-05-12
 */
date_default_timezone_set('Asia/Shanghai');
require __DIR__ . '/include/Sinaupload.php';
include_once dirname(__FILE__) .'/include/saetv2.ex.class.php';

class WeiboFile_Plugin implements Typecho_Plugin_Interface{
	/** @var string 提交路由前缀 */
    public static $action = 'manage_weibofile';
    /** @var string 控制菜单链接 */
    public static $panel  = 'WeiboFile/templates/manage_weibofile.php';
    // 激活插件
    public static function activate(){
		$db = Typecho_Db::get();
		$versions=explode("/",Typecho_Widget::widget('Widget_Options')->Version);
		if($versions[1]>="19.10.15"){
			self::$panel='WeiboFile/templates/manage_weibofile2.php';
		}
		if($versions[1]>="19.10.20"){
			self::$panel='WeiboFile/templates/manage_weibofile3.php';
		}
		Helper::addPanel(3, self::$panel, '图床管理', 'WeiboFile微博图床', 'administrator');
		Helper::addAction(self::$action, 'WeiboFile_Plugin');
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
		//微博同步
		Typecho_Plugin::factory('Widget_Contents_Post_Edit')->finishPublish = array(__CLASS__, 'weibosync');
		//判断目录是否存在
		if(!is_dir(dirname(__FILE__)."/config")){mkdir (dirname(__FILE__)."/config", 0777, true );}
		if(!is_dir(dirname(__FILE__)."/uploadfile")){mkdir (dirname(__FILE__)."/uploadfile", 0777, true );}
        return _t('插件已经激活，需先配置WeiboFile插件信息！');
    }

    // 禁用插件
    public static function deactivate(){
		//删除页面模板
		$db = Typecho_Db::get();
		$versions=explode("/",Typecho_Widget::widget('Widget_Options')->Version);
		if($versions[1]>="19.10.15"){
			self::$panel='WeiboFile/templates/manage_weibofile2.php';
		}
		if($versions[1]>="19.10.20"){
			self::$panel='WeiboFile/templates/manage_weibofile3.php';
		}
		Helper::removeAction(self::$action);
		Helper::removePanel(3, self::$panel);
		$queryTheme= $db->select('value')->from('table.options')->where('name = ?', 'theme'); 
		$rowTheme = $db->fetchRow($queryTheme);
		@unlink(dirname(__FILE__).'/../../themes/'.$rowTheme['value'].'/page_weibofile_videoupload.php');
		@unlink(dirname(__FILE__).'/../../themes/'.$rowTheme['value'].'/page_weibofile_webimgupload.php');
        return _t('插件已被禁用');
    }
	
	private static function Judge_database(){
        $db= Typecho_Db::get();
        $getAdapterName = $db->getAdapterName();
        if(preg_match('/^M|m?ysql$/',$getAdapterName)){
            return true;
        }else{
            throw new Typecho_Plugin_Exception(_t('对不起，使用了不支持的数据库，无法使用此功能，仅支持mysql数据库。'));
        }
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
		$db = Typecho_Db::get();
		$prefix = $db->getPrefix();
		$options = Typecho_Widget::widget('Widget_Options');
		$plug_url = $options->pluginUrl;
		
		$config_weibooauth=@unserialize(ltrim(file_get_contents(dirname(__FILE__).'/../../plugins/WeiboFile/config/config_weibooauth.php'),'<?php die; ?>'));
		$config_weibotoken=@unserialize(ltrim(file_get_contents(dirname(__FILE__).'/../../plugins/WeiboFile/config/config_weibotoken.php'),'<?php die; ?>'));
		
		if (!isset($_GET['oauth_token']) && !isset($config_weibotoken["access_token"])){
			$weiboflag='<a id="weibooauth" href="javascript:;"><img src="'.$plug_url.'/WeiboFile/images/t-login.png"></a>';
		}else{
			$c = new SaeTClientV2( $config_weibooauth["weiboappkey"] , $config_weibooauth["weiboappsecret"] , $config_weibotoken["access_token"] );
			$ms  = $c->show_user_by_id($config_weibotoken["uid"]);
			$weiboflag='<ul style="list-style:none">';
			if(isset($ms['error_code'])){
				$weiboflag.='
					<li>获取用户信息失败,错误代码:'.$ms['error_code'].',错误信息：'.$ms['error'].'</li>
					<li><a id="weiboreoauth" href="javascript:;">更换账号</a>,(更换账户
					时请先退出当前浏览器中新浪微博(weibo.com)的登录状态).</li>
				';
			}else{
				$ti = $c->get_token_info();
				$weiboflag.='
					<li><img src="'.$ms['profile_image_url'].'" style="border:2px #CCCCCC solid;"/></li>
					<li>当前新浪微博账号<b>'.$ms['name'].'</b>，<a id="weiboreoauth" href="javascript:;">更换账号</a>(更换账户
					时请先退出当前浏览器中新浪微博(weibo.com)的登录状态).</li>
					<li>离授权过期还有：'.self::sinav2_expire_in($ti['expire_in']).'，授权开始时间：'.gmdate('Y-n-j G:i l', $ti['create_at']).'，授权过期时间：'.gmdate('Y-n-j G:i l', 0+$ti['create_at']+$ti['expire_in']).'</li>
				';
			}
			$weiboflag.='</ul>';
		}
		
		//版本检查
		$headDiv=new Typecho_Widget_Helper_Layout();
		$headDiv->html('
			<script src="https://apps.bdimg.com/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
			
			<p>App Key：&nbsp;&nbsp;&nbsp;&nbsp;<input type="text" id="weiboappkey" value="'.$config_weibooauth["weiboappkey"].'" placeholder="填写完成点击微博登陆" /></p>
			<p>App Secret：<input type="text" id="weiboappsecret" value="'.$config_weibooauth["weiboappsecret"].'" placeholder="填写完成点击微博登陆" /></p>
			<p>回调接口：'.$plug_url.'/WeiboFile/weibocallback.php</p>
			<p>特别注意：微博开放平台的安全域名要与网站域名一致。</p>
			<p>'.$weiboflag.'</p>
			<script>
				$(function(){
					$("#weibooauth").click(function(){
						if($("#weiboappkey").val()==""||$("#weiboappsecret").val()==""){
							alert("请填写AppKey和AppSecret");
							return;
						}
						$.post("'.$plug_url.'/WeiboFile/ajax/weibooauth.php",{action:"weibooauth",weiboappkey:$("#weiboappkey").val(),weiboappsecret:$("#weiboappsecret").val()},function(data){
							var data=JSON.parse(data);
							if(data.status=="ok"){
								window.open(data.sinav2_aurl, "_blank", "height=400, width=600, top=0, left=0, toolbar=no, menubar=no, scrollbars=no, resizable=no, location=no, status=no");
							}else{
								alert("授权失败");
							}
						});
					});
					$("#weiboreoauth").click(function(){
						$.post("'.$plug_url.'/WeiboFile/ajax/weibooauth.php",{action:"weiboreoauth",weiboappkey:$("#weiboappkey").val(),weiboappsecret:$("#weiboappsecret").val()},function(data){
							var data=JSON.parse(data);
							if(data.status=="ok"){
								window.location.reload();
							}else{
								alert(data.msg);
							}
						});
					});
				});
			</script>
		');
		$headDiv->render();
		
		$albumtype = new Typecho_Widget_Helper_Form_Element_Radio('albumtype', array(
            'weibo'=>_t('微博图床'),
			'ali'=>_t('阿里图床'),
			'qihu'=>_t('奇虎360图床'),
			'jd'=>_t('京东图床')
        ), 'jd', _t('使用图床类型'), _t("选择上传阿里/微博图床，<font color='blue'>如果选择微博图床，因微博开启了防盗链，故如需显示图片必须在网站的head标签中加入&lt;meta name='referrer' content='same-origin'>代码或者修改以下微博图片链接前缀为http://的链接才行，https://前缀的图片链接不能显示，需要单独访问一次，才能显示。</font>。"));
		$form->addInput($albumtype->addRule('enum', _t(''), array('ali', 'weibo', 'qihu', 'jd')));
		
		$qihuprefix = new Typecho_Widget_Helper_Form_Element_Text('qihuprefix', array('value'), 'http://p0.so.qhimgs1.com/', _t('奇虎360图片链接前缀'), _t('奇虎360图片链接前缀'));
        $form->addInput($qihuprefix);
		
		$jdprefix = new Typecho_Widget_Helper_Form_Element_Text('jdprefix', array('value'), 'https://img14.360buyimg.com/uba/', _t('京东图片链接前缀'), _t('京东图片链接前缀'));
        $form->addInput($jdprefix);
		
		$aliprefix = new Typecho_Widget_Helper_Form_Element_Text('aliprefix', array('value'), 'https://ae01.alicdn.com/kf/', _t('阿里图片链接前缀'), _t('阿里图片链接前缀'));
        $form->addInput($aliprefix);
		
		$isweibosync = new Typecho_Widget_Helper_Form_Element_Radio('isweibosync', array(
            'y'=>_t('是'),
            'n'=>_t('否')
        ), 'n', _t('是否开启同步微博'), _t("发布文章同时同步到微博"));
		$form->addInput($isweibosync->addRule('enum', _t(''), array('y', 'n')));
		
		$issavealbum = new Typecho_Widget_Helper_Form_Element_Radio('issavealbum', array(
            'y'=>_t('是')
        ), 'y', _t('是否保存到微博相册'), _t("保存到微博相册时如果频繁会禁用当前微博的接口，所以每次只能上传一张图片。<font color='blue'>使用微博授权方式上传，在成功授权后，还要注意微博账号需要绑定手机。</font>"));
		$form->addInput($issavealbum->addRule('enum', _t(''), array('y', 'n')));
		
		$weiboprefix = new Typecho_Widget_Helper_Form_Element_Text('weiboprefix', array('value'), 'https://ws3.sinaimg.cn/large/', _t('微博图片链接前缀'), _t('微博图片链接前缀'));
        $form->addInput($weiboprefix);
		
		$isuploadlocal = new Typecho_Widget_Helper_Form_Element_Radio('isuploadlocal', array(
            'y'=>_t('是'),
            'n'=>_t('否')
        ), 'y', _t('非图片本地上传'), _t("1、启用后可支持上传其他格式文件到本地服务器。<br />2、使用Typecho自带的附件上传文件时，如果长时间没有弹出网址窗口可尝试刷新一次页面即可。"));
		$form->addInput($isuploadlocal->addRule('enum', _t(''), array('y', 'n')));
		
		$isEnableJQuery = new Typecho_Widget_Helper_Form_Element_Radio('isEnableJQuery', array(
            'y'=>_t('是'),
            'n'=>_t('否')
        ), 'y', _t('是否加载JQuery'), _t("如果主题head中自带jquery，需要选择否；如果主题中未加载jquery，则需要选择是。"));
		$form->addInput($isEnableJQuery->addRule('enum', _t(''), array('y', 'n')));
		
		$webimgupload = new Typecho_Widget_Helper_Form_Element_Radio('webimgupload', array(
            'y'=>_t('启用'),
            'n'=>_t('禁用')
        ), 'n', _t('前台图床'), _t("启用后开启前台图床上传，如有将图床模块嵌入网页的需求，则可以将代码<font color='blue'>&lt;?php include('page_weibofile_webimgupload.php'); ?></font>放于侧边拦或其他合适代码位置。"));
		$form->addInput($webimgupload->addRule('enum', _t(''), array('y', 'n')));
		$webimgupload = @isset($_POST['webimgupload']) ? addslashes(trim($_POST['webimgupload'])) : '';
		self::moduleImagesUpload($db,$webimgupload);
		
		$webimgheight = new Typecho_Widget_Helper_Form_Element_Text('webimgheight', array('value'), '300', _t('前台图床自定义高度'), _t('设置合适的高度可有利于前台展示。'));
        $form->addInput($webimgheight);
		
		$webimgbg = new Typecho_Widget_Helper_Form_Element_Text('webimgbg', array('value'), 'https://ae01.alicdn.com/kf/HTB1jpKrVXzqK1RjSZFC762bxVXa1.png', _t('前台图床默认背景'), _t('前台图床无上传时的背景。'));
        $form->addInput($webimgbg);
		//视频
		$videoupload = new Typecho_Widget_Helper_Form_Element_Radio('videoupload', array(
            'y'=>_t('启用'),
            'n'=>_t('禁用')
        ), 'n', _t('优酷视频上传'), _t("1、上传的视频需要优酷审核，并且因优酷官方会有需要作者每月手动刷新token才可上传的限制，故<font color='red'>不建议开启</font>。<br />2、使用Typecho自带的附件上传优酷视频时，如果长时间没有弹出网址窗口可尝试刷新一次页面即可。<br />3、为您做到心中有数，启用后会创建".$prefix."weibofile_videoupload数据表、page_weibofile_videoupload.php主题文件、视频上传页面3项，不启用则不会创建，不会添加任何无用项目且与其他插件互不冲突，谢谢支持。"));
        $form->addInput($videoupload->addRule('enum', _t(''), array('y', 'n')));
		$videoupload = @isset($_POST['videoupload']) ? addslashes(trim($_POST['videoupload'])) : '';
		self::moduleVideoUpload($db,$videoupload);
		//已废弃
		$weibouser = new Typecho_Widget_Helper_Form_Element_Text('weibouser', null, '', _t('微博用户名(非新注册、非二维码登陆，且使用过一段时间的账号)'), _t('<font color="red">因微博官方原因，此处已废弃，可无需填写，在上方登陆个人微博账号后再进行操作。</font>备注：设置后可多尝试多上传几次，上传成功尽量不要将此微博小号登录微博系的网站、软件，可以登录，但不确定会不会上传失败，上传失败了再重新上传2次同样可以正常上传，如果小号等级过低，可尝试微博大号，微博账号不能有手机验证权限，插件可正常使用，无需担心。'));
        $form->addInput($weibouser);

        $weibopass = new Typecho_Widget_Helper_Form_Element_Password('weibopass', null, '', _t('微博密码'),'<font color="red">因微博官方原因，此处已废弃，可无需填写，在最上方登陆个人微博账号后再进行操作。</font>');
        $form->addInput($weibopass);
		//视频列表
		try{
			$queryVideoAdmin= $db->select()->from('table.weibofile_videoupload')->order('videoupinstime',Typecho_Db::SORT_DESC);
			$resultVideoAdmin = $db->fetchAll($queryVideoAdmin);
			if(count($resultVideoAdmin)>0){
				$footDiv = new Typecho_Widget_Helper_Layout();
				$divstr1='
					<h2>优酷视频相关配置</h2>
					<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/amazeui/2.7.2/css/amazeui.min.css"/>
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
		$dottomDiv=new Typecho_Widget_Helper_Layout();
		$dottomDiv->html('
			
		');
		$dottomDiv->render();
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
				WeiboFile_Plugin::Judge_database();
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
		@unlink(dirname(__FILE__).'/../../../'.$filepath);
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
			if($option->albumtype=="weibo"){
				if($option->issavealbum=="n"){
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
				}else{
					$name=$file['name'];
					$options = Typecho_Widget::widget('Widget_Options');
					$plug_url = $options->pluginUrl;
					$config_weibooauth=@unserialize(ltrim(file_get_contents(dirname(__FILE__).'/../../plugins/WeiboFile/config/config_weibooauth.php'),'<?php die; ?>'));
					$config_weibotoken=@unserialize(ltrim(file_get_contents(dirname(__FILE__).'/../../plugins/WeiboFile/config/config_weibotoken.php'),'<?php die; ?>'));
					
					$time=time();
					$utfname=$time."_".$name;
					$gbkname = iconv("utf-8", "gbk", $utfname);
					move_uploaded_file($filename, dirname(__FILE__).'/uploadfile/'.$gbkname);
					$img=$plug_url."/WeiboFile/uploadfile/".$utfname;
					
					/* 修改了下风格，并添加文章关键词作为微博话题，提高与其他相关微博的关联率 */
					$string1 = '【'.$options->title.'】';
					$string2 = '来源：'.$options->siteUrl;
					/* 微博字数控制，避免超标同步失败 */
					$postData = $string1.mb_strimwidth("贴图",0, 140,'...').$string2;
					
					$c = new SaeTClientV2( $config_weibooauth["weiboappkey"] , $config_weibooauth["weiboappsecret"] , $config_weibotoken["access_token"] );
					$arr=$c->share($postData,$img);
					unlink(dirname(__FILE__).'/uploadfile/'.$gbkname);
					
					if(isset($arr['original_pic'])){
						$url=basename($arr['original_pic']);
						return array(
							'name'  =>  $name,
							'path'  =>  $url,
							'size'  =>  $file['size'],
							'type'  =>  $ext,
							'mime'  =>  "image/*"
						);
					}else{
						return array(
							'name'  =>  $name,
							'path'  =>  '上传失败',
							'size'  =>  $file['size'],
							'type'  =>  $ext,
							'mime'  =>  "image/*"
						);
					}
				}
			}else if($option->albumtype=="ali"){
				if(@$file['size']<=1024*1024*3){
					$tempfilename = iconv("utf-8", "gbk", @$file['name']);
					move_uploaded_file(@$file['tmp_name'], dirname(__FILE__).'/uploadfile/'.$tempfilename);
					$ch = curl_init();
					$filePath = dirname(__FILE__).'/uploadfile/'.$tempfilename;
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
					@unlink(dirname(__FILE__).'/uploadfile/'.$tempfilename);
					$arr=json_decode($json,true);
					$imgurls=explode("/",$arr['data']["src"]);
					return array(
						'name'  =>  $file['name'],
						'path'  =>  $imgurls[count($imgurls)-1],
						'size'  =>  $file['size'],
						'type'  =>  $ext,
						'mime'  =>  "image/*"
					);
				}
				return array(
					'name'  =>  $file['name'],
					'path'  =>  '暂时最大支持3M图片，增加大小可手动修改ajax/videoInsert.php中86行代码。',
					'size'  =>  $file['size'],
					'type'  =>  $ext,
					'mime'  =>  "image/*"
				);
			}else if($option->albumtype=="qihu"){
				if(@$file['size']<=1024*1024*3){
					$tempfilename = iconv("utf-8", "gbk", @$file['name']);
					move_uploaded_file(@$file['tmp_name'], dirname(__FILE__).'/uploadfile/'.$tempfilename);
					$ch = curl_init();
					$filePath = dirname(__FILE__).'/uploadfile/'.$tempfilename;
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
					@unlink(dirname(__FILE__).'/uploadfile/'.$tempfilename);
					$arr=json_decode($json,true);
					$imgurls=explode("/",$arr['data']["src"]);
					return array(
						'name'  =>  $file['name'],
						'path'  =>  $imgurls[count($imgurls)-1],
						'size'  =>  $file['size'],
						'type'  =>  $ext,
						'mime'  =>  "image/*"
					);
				}
				return array(
					'name'  =>  $file['name'],
					'path'  =>  '暂时最大支持3M图片，增加大小可手动修改ajax/videoInsert.php中86行代码。',
					'size'  =>  $file['size'],
					'type'  =>  $ext,
					'mime'  =>  "image/*"
				);
			}else if($option->albumtype=="jd"){
				if(@$file['size']<=1024*1024*3){
					$tempfilename = iconv("utf-8", "gbk", @$file['name']);
					move_uploaded_file(@$file['tmp_name'], dirname(__FILE__).'/uploadfile/'.$tempfilename);
					$ch = curl_init();
					$filePath = dirname(__FILE__).'/uploadfile/'.$tempfilename;
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
					@unlink(dirname(__FILE__).'/uploadfile/'.$tempfilename);
					$arr=json_decode($json,true);
					$imgurls=explode("/",$arr['data']["src"]);
					if(strpos($imgurls[4],"ERROR")!==false){
						$basename="上传失败换张图片试试";
					}else{
						$basename=substr($arr['data']["src"],strpos($arr['data']["src"],$imgurls[4]));
					}
					return array(
						'name'  =>  $file['name'],
						'path'  =>  $basename,
						'size'  =>  $file['size'],
						'type'  =>  $ext,
						'mime'  =>  "image/*"
					);
				}
				return array(
					'name'  =>  $file['name'],
					'path'  =>  '暂时最大支持3M图片，增加大小可手动修改ajax/videoInsert.php中86行代码。',
					'size'  =>  $file['size'],
					'type'  =>  $ext,
					'mime'  =>  "image/*"
				);
			}
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
						'mime'  =>  "video/*"
					);
				}else{
					return array(
						'name'  =>  $file['name'],
						'path'  =>  '上传失败',
						'size'  =>  $file['size'],
						'type'  =>  $ext,
						'mime'  =>  "video/*"
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
				mkdir($dir,0777,true);
			}
			$dirupload="/usr/uploads/".$dirtime;
			$newname=time().".".end(explode(".",$file['name']));
			move_uploaded_file($file['tmp_name'], $dir.$newname);
			return array(
				'name'  =>  $newname,
				'path'  =>  $dirupload.$newname,
				'size'  =>  $file['size'],
				'type'  =>  $ext,
				'mime'  =>  "video/*"
			);
		}else{
			return array(
				'name'  =>  $file['name'],
				'path'  =>  '未开启本地上传',
				'size'  =>  $file['size'],
				'type'  =>  $ext,
				'mime'  =>  "video/*"
			);
		}
    }

    // 上传文件处理函数
    public static function uploadHandle($file){
        return self::uploadFile($file);
    }

    // 修改文件处理函数
    public static function modifyHandle($content, $file){
		self::deleteFile($content['attachment']->path);
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
			if($option->albumtype=="weibo"){
				if($option->issavealbum=="n"){
					return Typecho_Common::url($content['attachment']->path.'.jpg', $option->weiboprefix);
				}else{
					return Typecho_Common::url($content['attachment']->path,$option->weiboprefix);
				}
			}else if($option->albumtype=="ali"){
				return Typecho_Common::url($content['attachment']->path, $option->aliprefix);
			}else if($option->albumtype=="qihu"){
				return Typecho_Common::url($content['attachment']->path, $option->qihuprefix);
			}else if($option->albumtype=="jd"){
				return Typecho_Common::url($content['attachment']->path, $option->jdprefix);
			}
		}else{
			return Typecho_Common::url($content['attachment']->path, $plug_url.'/../..');
		}
    }
	
	/*格式化时间戳*/
	public static function sinav2_expire_in($timestamp){
		$d = floor($timestamp/86400);
		$h = floor(($timestamp%86400)/3600);
		$i = floor((($timestamp%86400)%3600)/60);
		$s = floor((($timestamp%86400)%3600)%60);
		return "{$d}天{$h}小时{$i}分{$s}秒";
	}
	
	/*获取微博字符长度函数*/
	public static function WeiboLength($str){
		$len=0;
		$arr = self::arr_split_zh($str);   //先将字符串分割到数组中
		foreach ($arr as $v){
			$temp = ord($v);        //转换为ASCII码
			if ($temp > 0 && $temp < 127) {
				$len = $len+0.5;
			}else{
				$len ++;
			}
		}
		return ceil($len);        //加一取整
	}
	/*拆分字符串函数,只支持 gb2312编码*/
	public static function arr_split_zh($tempaddtext){
		$tempaddtext = iconv("UTF-8", "GBK//IGNORE", $tempaddtext);
		$cind = 0;
		$arr_cont=array();
		for($i=0;$i<strlen($tempaddtext);$i++){
			if(strlen(substr($tempaddtext,$cind,1)) > 0){
				if(ord(substr($tempaddtext,$cind,1)) < 0xA1 ){ //如果为英文则取1个字节
					array_push($arr_cont,substr($tempaddtext,$cind,1));
					$cind++;
				}else{
					array_push($arr_cont,substr($tempaddtext,$cind,2));
					$cind+=2;
				}
			}
		}
		foreach ($arr_cont as &$row){
			$row=iconv("gb2312","UTF-8",$row);
		}
		return $arr_cont;
	}
	
	/* 抓取文章第一张图片作为特色图片 */
	public static function catch_first_image($get_post_centent) {
		ob_start();
		ob_end_clean();
		preg_match_all('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $get_post_centent,$matches);
		if(isset($matches [1] [0])){
			return $matches [1] [0];
		}else{
			return false;
		}
	} 
	
	/*微博同步*/
	public static function weibosync($contents, $widget){
		$option = self::getConfig();
		if($option->isweibosync!="y"){return;}
		$options = Typecho_Widget::widget('Widget_Options');
		$config_weibooauth=@unserialize(ltrim(file_get_contents(dirname(__FILE__).'/../../plugins/WeiboFile/config/config_weibooauth.php'),'<?php die; ?>'));
		$config_weibotoken=@unserialize(ltrim(file_get_contents(dirname(__FILE__).'/../../plugins/WeiboFile/config/config_weibotoken.php'),'<?php die; ?>'));
		if(isset($config_weibooauth["weiboappkey"])&&isset($config_weibotoken["access_token"])&&$contents["visibility"]=="publish"&&$contents['created'] < time()&&$contents['created']==null){
			$keywords = ""; 
			/* 获取文章标签关键词 */
			$tags = explode(",",$contents["tags"]);
			$index=0;
			foreach ($tags as $tag ) {
				if($tag!=""){
					if($index==0){
						$keywords = " 标签：".$tag;
					}else{
						$keywords = $keywords.','.$tag;
					}
				}
				$index++;
			}
			/* 修改了下风格，并添加文章关键词作为微博话题，提高与其他相关微博的关联率 */
			$string1 = '【'.$options->title.'】' . $contents["title"].'：';
			$string2 = $keywords.' 查看全文：'.$widget->permalink;
			/* 微博字数控制，避免超标同步失败 */
			$wb_num = (138 - self::WeiboLength($string1.$string2))*2;
			$content="";
			if(strpos($contents["text"], '<!--markdown-->')===0){
				$content=substr($contents["text"],15);
			}
			$postData = $string1.mb_strimwidth(strip_tags( addslashes(Markdown::convert($content))),0, $wb_num,'...').$string2;
			/* 获取特色图片，如果没设置就抓取文章第一张图片，需要主题函数支持 */
			$img = self::catch_first_image(Markdown::convert($content));
			$c = new SaeTClientV2( $config_weibooauth["weiboappkey"] , $config_weibooauth["weiboappsecret"] , $config_weibotoken["access_token"] );
			$res=$c->share($postData,$img);
		}
	}
	public static function httpClient($url,$data){
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
			return $arr;
		}
		return 0;
	}
}