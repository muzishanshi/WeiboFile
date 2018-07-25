<?php
/**
 * 视频上传页面
 *
 * @package custom
 */
?>
<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php $this->need('header.php'); ?>
<?php
date_default_timezone_set('Asia/Shanghai');
$action = isset($_POST['action']) ? addslashes($_POST['action']) : '';
if($action=='upload_video'){
	if ($_FILES['movieFile']['size'] > 0) {
		if($_FILES['movieFile']['error']!=0){
			echo "<script>alert('上传错误');location.href='".$this->permalink."';</script>";exit;
		}
		if($_FILES['movieFile']['size']>1024*1024*1024){
			echo "<script>alert('最大上传视频为1G');location.href='".$this->permalink."';</script>";exit;
		}
		$video_type = array(
			'wmv', 'avi', 'dat','asf','rm','rmvb','ram','mpg','mpeg','3gp',
			'mov','mp4','m4v','dvix','dv','vob','mkv','vob','ram','qt',
			'divx','cpk','fli','flc','mod','flv'
		);
		ini_set('max_input_time', '600');
		ini_set('max_execution_time', '600');
		ini_set('memory_limit', '128M');
		ini_set('upload_max_filesize','1024M');
		ini_set('post_max_size','1024M');
		set_time_limit(0);
		$video_extension = strtolower(pathinfo($_FILES['movieFile']['name'],  PATHINFO_EXTENSION));
		if (!in_array($video_extension, $video_type)) {
			echo "<script>alert('视频格式有误');location.href='".$this->permalink."';</script>";exit;
		}
		$filename = iconv("utf-8", "gbk", $_FILES['movieFile']['name']);
		move_uploaded_file($_FILES['movieFile']['tmp_name'], dirname(__FILE__).'/'.$filename);
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
		curl_setopt($ch, CURLOPT_URL, 'http://me.tongleer.com/mob/app/wap/json/blogjson.php');
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
				'videoupuid'   =>  Typecho_Cookie::get('__typecho_uid'),
				'videoupurl'     =>  'http://player.youku.com/embed/'.$arr['video_id'],
				'videouppic'     =>  '',
				'videoupinstime'     =>  date('Y-m-d H:i:s',Typecho_Date::time()),
				'videouptype'     =>  'youku'
			);
			$insert = $this->db->insert('table.weibofile_videoupload')->rows($insertData);
			$insertId = $this->db->query($insert);
		}
		echo "<script>location.href='".$this->permalink."';</script>";exit;
	}else{
		echo "<script>alert('请先选择上传文件');location.href='".$this->permalink."';</script>";exit;
	}
}
?>
<link rel="stylesheet" href="http://cdn.amazeui.org/amazeui/2.7.2/css/amazeui.min.css"/>
<script src="http://apps.bdimg.com/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
<style>
.page-main{
	background-color:#fff;
	width:960px;
	margin:60px auto 0px auto;
}
@media screen and (max-width: 960px) {
	.banner-head {width: 100%;}
}
</style>
<!-- content section -->
<section class="page-main">
	<form id="video_form" class="am-form" method="post" action="" enctype="multipart/form-data">
		<fieldset>
			<div>
				<div id="video_div" style="width:auto;height:100px;border:3px dashed silver;line-height:100px; text-align:center; font-size:20px; color:#d3d3d3;cursor:pointer;">
					<div class="am-form-group am-form-file">
					  <div>
						<button type="button" class="am-btn am-btn-default am-btn-sm">
						  <i class="am-icon-cloud-upload"></i> <span id="video_span">选择要上传的视频</span></button>
					  </div>
					  <input type="file" name="movieFile" id="movieFile">
					</div>
				</div>
			</div>
			<input type="hidden" id="isLogin" value="<?=$this->user->hasLogin();?>" />
			<input type="hidden" name="action" value="upload_video" />
			<button type="submit" class="am-btn am-btn-success am-btn-block" id="startbtn">开始上传</button>
		</fieldset>
	</form>
	<?php
	if($this->user->hasLogin()){
		$queryVideos= $this->db->select()->from('table.weibofile_videoupload')->where('table.weibofile_videoupload.videoupuid = ?', Typecho_Cookie::get('__typecho_uid'))->order('videoupinstime',Typecho_Db::SORT_DESC);
		$resultVideos = $this->db->fetchAll($queryVideos);
		if(count($resultVideos)>0){
	?>
		<div style="background-color:#fff;overflow:scroll; height:500px; width:100%; border: solid 0px #aaa; margin: 0 auto;">
		  <table class="am-table am-table-bordered am-table-striped am-table-compact">
			<thead>
				<tr>
					<th>视频</th>
					<th>时间</th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach($resultVideos as $value){
				?>
				<tr>
					<td>
						<?php
						if($value['videouppic']==''){
							?>
							<a href="<?=$value['videoupurl'];?>" target="_blank" rel="nofollow" title=""><?php echo $value['videoupinstime'].'上传';;?></a>
							<?php
						}else{
							?>
							<a href="<?=$value['videoupurl'];?>" target="_blank" rel="nofollow" title=""><img src="<?=$value['videouppic'];?>" /></a>
							<?php
						}
						?>
					</td>
					<td><?=$value['videoupinstime'];?></td>
				</tr>
				<?php
				}
				?>
			<tbody>
		  </table>
		</div>
		<?php
		}
	}
	?>
</section>
<script>
window.onload = function(){
	$('#movieFile').change(function(){
		var file = $('#movieFile')[0].files[0];
		$('#video_span').html(file.name);
	});
	$('#video_form').submit(function(){
		var file = $('#movieFile')[0].files[0];
		if($('#isLogin').val()!=1){
			$('#video_span').html('<font color="red">登录后方可上传视频</font>');
			return false;
		}
		if(file==undefined){
			$('#video_span').html('<font color="red">选择要上传的视频</font>');
			return false;
		}
		if(file.size>100000000){
			$('#video_span').html('<font color="red">选择的视频过大</font>');
			return false;
		}
		$('#startbtn').text('正在上传中……请耐心等待……如果出现超时，也是会上传成功的……');
	});
}
</script>
<!-- end content section -->
<script src="http://cdn.amazeui.org/amazeui/2.7.2/js/amazeui.min.js" type="text/javascript"></script>
<?php $this->need('footer.php'); ?>