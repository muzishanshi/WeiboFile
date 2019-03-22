<?php
/**
 * 前台图床页面
 *
 * @package custom
 */
?>
<?php
date_default_timezone_set('Asia/Shanghai');
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$options = Typecho_Widget::widget('Widget_Options');
$option=$options->plugin('WeiboFile');
$plug_url = $options->pluginUrl;
if(strpos($this->permalink,'?')){
	$url=substr($this->permalink,0,strpos($this->permalink,'?'));
}else{
	$url=$this->permalink;
}
try{
?>
<html>
<head>
	<meta charset="UTF-8">
	<title><?php $this->archiveTitle(array('category'=>_t(' %s '),'search'=>_t(' %s '),'tag'=>_t(' %s '),'author'=>_t(' %s ')),'',' - ');?><?php $this->options->title();?></title>
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no">
	<meta name="format-detection" content="telephone=no">
	<meta name="renderer" content="webkit">
	<meta http-equiv="Cache-Control" content="no-siteapp"/>
	<meta name="author" content="同乐儿">
	<link rel="alternate icon" href="https://ws3.sinaimg.cn/large/ecabade5ly1fxpiemcap1j200s00s744.jpg" type="image/png" />
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/layer/2.3/layer.js"></script>
</head>
<body>
<div id="weibofile_webimg_container" onclick="weibofile_file.click()" style="margin:5px 0px;position: relative; border: 2px dashed #e2e2e2; background-image:url('<?=$option->webimgbg;?>'); text-align: center; cursor: pointer;height: 100%;">
	<p id="weibofile_webimg_upload" style="height: <?=$option->webimgheight;?>px;line-height:<?=$option->webimgheight;?>px;position: relative;font-size:20px; color:#d3d3d3;">将图片拖拽到此上传</p> 
	<input type="file" id="weibofile_file" style="display:none" accept="image/*" multiple /> 
</div>
<script>
var weibofile_webimgdiv = document.getElementById('weibofile_webimg_upload');
var weibofile_file = document.getElementById('weibofile_file');
document.ondragenter = document.ondrop = document.ondragover = function(e){
	e.preventDefault();
	weibofile_webimgdiv.style.display = 'block';
}
weibofile_webimgdiv.ondragenter = function(e){
	weibofile_webimgdiv.innerHTML = '松开开始上传';
	e.preventDefault();
}
weibofile_webimgdiv.ondragleave = function(e){
	weibofile_webimgdiv.innerHTML = '离开取消上传';
	e.preventDefault();
}
weibofile_webimgdiv.ondragover = function(e){
	e.preventDefault();
}
var dropHandler = function(e){
	var file;
	e.preventDefault();
	file = e.dataTransfer.files && e.dataTransfer.files;
	upLoad(file);
}
document.body.addEventListener('drop', function(e) {
	dropHandler(e);
}, false);
weibofile_file.addEventListener('change', function() {
	inputFileHandler();
}, false);
var inputFileHandler = function(){
	var file = weibofile_file.files;
	upLoad(file);
}
function upLoad(file){
	var xhr = new XMLHttpRequest();
	var data;
	var upLoadFlag = true;
	if(upLoadFlag === false){
		layer.msg('正在上传中……请稍后……');
		return;
	}
	if(!file){
		layer.msg('不要上传图片了吗……');
		return;
	}
	data = new FormData();
	data.append('action', 'imageUpload');
	for (var i=0;i<file.length;i++){
		if(file[i] && file[i].type.indexOf('image') === -1){
			layer.msg('这不是一张图片……请重新选择……');
			return;
		}
		data.append('webimgupload['+i+']', file[i]);
	}
	xhr.open("POST", "<?=$plug_url.'/WeiboFile/ajax/imageUpload.php';?>");
	xhr.send(data);
	upLoadFlag = false;
	xhr.onreadystatechange = function(){
		if(xhr.readyState == 4 && xhr.status == 200){
			upLoadFlag = true;
			var data=JSON.parse(xhr.responseText);
			if(data.status=="noset"){
				weibofile_webimgdiv.innerHTML = data.msg;
			}else if(data.status=="disable"){
				weibofile_webimgdiv.innerHTML = data.msg;
			}else if(data.status=="ok"){
				weibofile_webimgdiv.innerHTML = '将图片拖拽到此上传';
				layer.confirm('<small><font color="green">'+data.msg+'<br />'+data.hrefs+'</font></small><textarea style="width:100%;margin: 0 auto;" rows="2" onfocus="this.select();">'+data.codes+'</textarea>', {
					btn: ['关闭']
				},function(index){
					layer.close(index);
				});
				var urls=data.urls.split("<br />");
				document.getElementById('weibofile_webimg_container').style.backgroundImage = "url("+urls[0]+")";
			}
		}
	}
}
</script>
</body>
</html>
<?php
}catch(Exception $e){}
?>