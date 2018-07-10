<?php
$db = Typecho_Db::get();
$prefix = $db->getPrefix();

$queryVideoList= $db->select()->from('table.weibofile_videoupload')->where('table.weibofile_videoupload.videoupuid = ?', $uid)->order('videoupinstime',Typecho_Db::SORT_DESC);
$resultVideoList = $db->fetchAll($queryVideoList);
if(count($resultVideoList)>0){
	?>
	<script src="http://apps.bdimg.com/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
	<section class="typecho-post-option">
		<h4>视频列表（可在插件设置处管理视频）</h4>
		<div style="background-color:#fff;overflow:scroll; height:250px; width:100%; border: solid 0px #aaa; margin: 0 auto;">
			<ul>
				<?php $videotemi=1;foreach($resultVideoList as $value){?>
					<li>
						<?php
						if($value['videouppic']==''){
							?>
							<a class="videonoimgid" id="videonoimgid<?=$videotemi;?>" data-url="<?=$value['videoupurl'];?>" href="javascript:void();"><?php echo $value['videoupinstime'].'上传';;?></a>
							<?php
						}else{
							?>
							<a class="videoimgid" id="videoimgid<?=$videotemi;?>" data-url="<?=$value['videoupurl'];?>" href="javascript:void();"><img src="<?=$value['videouppic'];?>" /></a>
							<?php
						}
						?>
					</li>
				<?php $videotemi++;}?>
			</ul>
		</div>
	</section>
	<script>
	$(function() {
		$(".videonoimgid").each(function(){
			var id=$(this).attr("id");
			$("#"+id).click( function () {
				var html='\r\n<iframe height="400" width="100%" src="'+$(this).attr('data-url')+'" frameborder="0" "allowfullscreen"></iframe>';
				$('#text').val($('#text').val()+html);
			});
		});
		$(".videoimgid").each(function(){
			var id=$(this).attr("id");
			$("#"+id).click( function () {
				var html='\r\n<iframe height="400" width="100%" src="'+$(this).attr('data-url')+'" frameborder="0" "allowfullscreen"></iframe>';
				$('#text').val($('#text').val()+html);
			});
		});
	});
	</script>
	<?php
}
?>