<?php
$options = Typecho_Widget::widget('Widget_Options');
$option=$options->plugin('WeiboFile');
if(strpos($_SERVER['PHP_SELF'],"write-page")){
	Typecho_Widget::widget('Widget_Contents_Page_Edit')->to($post);
}else{
	Typecho_Widget::widget('Widget_Contents_Post_Edit')->to($post);
}
try{
?>
<input type="hidden" id="weibofile_is_videoupload" value="<?=$option->videoupload;?>" />
<input type="hidden" id="cid" value="<?=$post->cid;?>" />
<script type="text/javascript">
	$(function(){
		if($('#wmd-button-row').length>0){
			if($("#weibofile_is_videoupload").val()=="y"){
				$('#wmd-button-row').append('<li class="wmd-button" id="wmd-button-video" style="font-size:20px;float:left;color:#AAA;width:20px;" title=添加视频><b>V</b></li>');
			}
			$('#wmd-button-row').append('<li class="wmd-button" id="wmd-button-weiboimg" style="font-size:20px;float:left;color:#AAA;width:20px;" title=微博图床><b>W</b></li>');
		}else{
			if($("#weibofile_is_videoupload").val()=="y"){
	 		$('#text').before('<a href="#" id="wmd-button-video" title="添加视频"><b>V</b></a>');
			}
			$('#text').before('<a href="#" id="wmd-button-weiboimg" title="微博图床"><b>W</b></a>');
		}
	 	$(document).on('click', '#wmd-button-video', function(){
	 		$(this).after(
	 			'<div class="wmd-prompt-dialog"><div><p><b>本地小视频</b></p><p>此处仅支持约5M以下小视频上传，支持大多数格式。<br /><font color="red">使用过程中请遵守法律法规，注意视频内容的合法性。</font><br />请勿重复上传，并且上传成功后要等待优酷审核成功方可正常播放，为保证审核顺利，请上传合法并拥有完整内容的视频。由于优酷方无法自动刷新token，如遇上传失败需联系站长手动修改token即可。</p></div><form enctype="multipart/form-data"><input type="file" name="video_file" id="video_file" accept="video/*"><br /><br /><button id="uploadBtn" type="button" class="btn-s primary" onclick="startUpload(this);">上传并插入</button><button type="button" class="btn-s" onclick="cancelAlert();">取消</button><br /><br /><div id="video_upload_msg"></div><br /></form><div><p><b>解析视频</b></p><p>目前支持抖音视频</div><form><input type="text" name="video_url" id="video_url"><br /><br /><button id="parseBtn" type="button" class="btn-s primary" onclick="startParse(this);">解析并插入</button><button type="button" class="btn-s" onclick="cancelAlert();">取消</button><br /><br /><div id="video_parse_msg"></div></form></div>');

	 	});
		$(document).on('click', '#wmd-button-weiboimg', function(){
	 		$(this).after(
	 			'<div class="wmd-prompt-dialog"><div><p><b>图床</b></p><p>微博图床一次只能上传一张图片，其他图床暂时最大支持3M图片，增加大小可手动修改ajax/videoInsert.php中的代码。</p></div><form enctype="multipart/form-data"><input type="file" name="img_file" id="img_file" accept="image/*" multiple><br /><br /><button id="uploadImgBtn" type="button" class="btn-s primary" onclick="startImgUpload(this);">上传并插入</button><button type="button" class="btn-s" onclick="cancelAlert();">取消</button><br /><br /><div id="img_upload_msg"></div><br /></form></div>');

	 	});

	 	/*移除弹窗*/
	 	if(($('.wmd-prompt-dialog').length != 0) && e.keyCode == '27') {
			cancelAlert();
		}
	});
	
	function startParse(e){
		var Expression=/http(s)?:\/\/([\w-]+\.)+[\w-]+(\/[\w- .\/?%&=]*)?/;
		var objExp=new RegExp(Expression);
		if(objExp.test($('#video_url').val()) != true){
			$('#video_parse_msg').html('<font color="red">输入合法的视频地址</font>');
			return;
		}
		$.post("<?php echo $ajaxurl?>",{action:"parseMovie",video_url:$('#video_url').val()},function(data){
			var data=JSON.parse(data);
			if(data.error == undefined){
				$('#video_parse_msg').html('<font color="green">解析成功</font>');
				var html='\r\n<iframe height="400" width="100%" src="'+data.url+'" frameborder="0" "allowfullscreen"></iframe>';
				$('#text').val($('#text').val()+html);
			}else{
				$('#video_parse_msg').html('<font color="red">输入的地址无法解析，请换个地址再试试吧</font>');
			}
		});
	}
	
	function isUploadVideo(filename){
        var strFilter=".wmv|.avi|.dat|.asf|.rm|.rmvb|.ram|.mpg|.mpeg|.3gp|.mov|.mp4|.m4v|.dvix|.vob|.mkv|.ram|.qt|.divx|.cpk|.fli|.flc|.mod|.flv|"
        if(filename.indexOf(".")>-1){
            var p = filename.lastIndexOf(".");
            var strPostfix=filename.substring(p,filename.length) + '|';        
            strPostfix = strPostfix.toLowerCase();
            if(strFilter.indexOf(strPostfix)>-1){
                return true;
            }
        }
        return false;            
    }

	function startUpload(e){
		if($('#video_upload_msg').html()=='<font color="green">上传中，请稍后……</font>'){
			alert('上传中，请稍后……');
			return;
		}
		video_file = $('#video_file');
		if(video_file.val() == ''){
			$('#video_upload_msg').html('<font color="red">请选择一个视频</font>');
			return;
		}
		if(video_file[0].files[0].size>5000000){
			$('#video_upload_msg').html('<font color="red">视频过大</font>');
			return;
		}
		video_file_name = video_file[0].files[0].name;
		if(!isUploadVideo(video_file_name)){
			$('#video_upload_msg').html('<font color="red">格式不正确</font>');
			return;
		}else{
			$('#video_upload_msg').html('<font color="red">&nbsp;</font>');
		}
		var xhr=new XMLHttpRequest();
		xhr.open('POST','<?php echo $ajaxurl.'?uid='.$uid;?>',true);
		xhr.upload.onprogress=function(e){
			$('#video_upload_msg').html('<font color="green">上传中，请稍后……</font>');
		}
		xhr.onerror=function(e){
			$('#video_upload_msg').html('<font color="red">上传错误</font>');
			return;
		}
		xhr.onreadystatechange=function(e){
			if(xhr.readyState===4 && xhr.status===200){
				var data=JSON.parse(xhr.responseText);
				if(data.code==100){
					$('#video_upload_msg').html('<font color="green">上传成功，可等视频审核通过后再将文章“公开度”设置为“公开”</font>');
					$('#uploadBtn').css('display','none');
					var html='\r\n<iframe height="400" width="100%" src="https://player.youku.com/embed/'+data.video_id+'" frameborder="0" "allowfullscreen"></iframe>';
					$('#text').val($('#text').val()+html);
				}else{
					$('#video_upload_msg').html('<font color="red">上传失败</font>');
				}
			}
		}
		var upload=new FormData();
		upload.append('movieFile',video_file[0].files[0]);
		upload.append('action','uploadMovie');
		upload.append('title',video_file_name);
		xhr.send(upload);
	}
	
	function isUploadImg(filename){
        var strFilter=".jpg|.jpeg|.png|.gif|.bmp|"
        if(filename.indexOf(".")>-1){
            var p = filename.lastIndexOf(".");
            var strPostfix=filename.substring(p,filename.length) + '|';        
            strPostfix = strPostfix.toLowerCase();
            if(strFilter.indexOf(strPostfix)>-1){
                return true;
            }
        }
        return false;            
    }

	function startImgUpload(e){
		if($('#img_upload_msg').html()=='<font color="green">上传中，请稍后……</font>'){
			alert('上传中，请稍后……');
			return;
		}
		var img_file = document.getElementById('img_file');
		if(img_file.files.length==0){
			$('#img_upload_msg').html('<font color="red">请选择一个图片</font>');
			return;
		}
		var xhr=new XMLHttpRequest();
		xhr.open('POST','<?php echo $ajaxurl.'?uid='.$uid;?>',true);
		xhr.upload.onprogress=function(e){
			$('#img_upload_msg').html('<font color="green">上传中，请稍后……</font>');
		}
		xhr.onerror=function(e){
			$('#img_upload_msg').html('<font color="red">上传错误</font>');
			return;
		}
		xhr.onreadystatechange=function(e){
			if(xhr.readyState===4 && xhr.status===200){
				var data=JSON.parse(xhr.responseText);
				$.each(data,function(index,array) {
					var html='';
					if(array.code==100){
						html='\r\n!['+array.name+'][1]\r\n\r\n\r\n  [1]: '+array.url;
					}else{
						html='\r\n!['+array.name+'][1]上传失败';
					}
					$("#text").append(html);
				});
				$('#img_upload_msg').html('<font color="green">上传完成</font>');
				$('#uploadImgBtn').css('display','none');
			}
		}
		var data=new FormData();
		for (var i=0;i<img_file.files.length;i++){
			if(img_file.files[i] && img_file.files[i].type.indexOf('image') === -1){
				$('#img_upload_msg').html('<font color="red">格式不正确</font>');
				return;
			}else{
				$('#img_upload_msg').html('<font color="red">&nbsp;</font>');
			}
			data.append('imgFile['+i+']', img_file.files[i]);
		}
		data.append('cid',$("#cid").val());
		data.append('action','uploadImg');
		xhr.send(data);
	}

	function cancelAlert() {
		$('.wmd-prompt-dialog').remove()
	}
</script>
<?php
}catch(Exception $e){}
?>