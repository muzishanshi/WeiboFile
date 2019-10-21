<?php
include 'header.php';
include 'menu.php';

$stat = Typecho_Widget::widget('Widget_Stat');
$posts = Typecho_Widget::widget('Widget_Contents_Post_Admin');
$isAllPosts = ('on' == $request->get('__typecho_all_posts') || 'on' == Typecho_Cookie::get('__typecho_all_posts'));
$options=Typecho_Widget::widget('Widget_Options');
$option=$options->plugin('WeiboFile');
$plug_url = $options->pluginUrl;
$versions=explode("/",Typecho_Widget::widget('Widget_Options')->Version);
if($versions[1]>="19.10.15"){
	WeiboFile_Plugin::$panel='WeiboFile/templates/manage_weibofile2.php';
}
?>
    <div class="admin-content-body typecho-list row typecho-page-main">
      <div class="am-cf am-padding typecho-page-title">
			<div class="am-fl am-cf">
				<?php include 'page-title.php'; ?>
			</div>
	  </div>
      <div class="am-g typecho-list-operate clearfix">
		<form method="get">
			<div class="operate am-u-sm-12 am-u-md-6">
			  <div class="am-btn-toolbar typecho-option-tabs">
				<div class="am-btn-group am-btn-group-xs">
				  <button type="button" class="am-btn am-btn-xs" style="height:28px;"><label><i class="sr-only"><?php _e('全选'); ?></i><input type="checkbox" class="typecho-table-select-all" /></label></button>
				  <div class="am-dropdown am-btn-group am-btn-group-xs" data-am-dropdown>
					<button class="am-btn am-dropdown-toggle" data-am-dropdown-toggle><i class="sr-only"><?php _e('操作'); ?></i><?php _e('选中项'); ?> <span class="am-icon-caret-down"></span></button>
					<ul class="am-dropdown-content dropdown-menu">
					  <li><a lang="<?php _e('你确认要删除这些文章吗?'); ?>" href="<?php $security->index('/action/contents-post-edit?do=delete'); ?>"><?php _e('删除'); ?></a></li>
					</ul>
				  </div>
				</div>
				<div class="am-btn-group am-btn-group-xs">
				  <a href="https://www.tongleer.com" title="同乐儿" class="am-btn am-btn-default"><?php _e('官网'); ?></a>
				  <a href="<?php $options->adminUrl('manage-posts.php'. (isset($request->uid) ? '?uid=' . $request->uid : '')); ?>" class="am-btn am-btn-default <?php if(!isset($request->status) || 'all' == $request->get('status')): ?>am-btn-primary<?php endif; ?>"><?php _e('可用'); ?></a>
				  <a href="<?php $options->adminUrl('manage-posts.php?status=waiting' . (isset($request->uid) ? '&uid=' . $request->uid : '')); ?>" class="am-btn am-btn-default <?php if('waiting' == $request->get('status')): ?>am-btn-primary<?php endif; ?>">
					<?php _e('待审核'); ?>
					<?php if(!$isAllPosts && $stat->myWaitingPostsNum > 0 && !isset($request->uid)): ?>
						<span class=""><?php $stat->myWaitingPostsNum(); ?></span>
					<?php elseif($isAllPosts && $stat->waitingPostsNum > 0 && !isset($request->uid)): ?>
						<span class=""><?php $stat->waitingPostsNum(); ?></span>
					<?php elseif(isset($request->uid) && $stat->currentWaitingPostsNum > 0): ?>
						<span class=""><?php $stat->currentWaitingPostsNum(); ?></span>
					<?php endif; ?>
				  </a>
				  <a href="<?php $options->adminUrl('manage-posts.php?status=draft' . (isset($request->uid) ? '&uid=' . $request->uid : '')); ?>" class="am-btn am-btn-default <?php if('draft' == $request->get('status')): ?>am-btn-primary<?php endif; ?>">
					<?php _e('草稿'); ?>
					<?php if(!$isAllPosts && $stat->myDraftPostsNum > 0 && !isset($request->uid)): ?>
						<span class=""><?php $stat->myDraftPostsNum(); ?></span>
					<?php elseif($isAllPosts && $stat->draftPostsNum > 0 && !isset($request->uid)): ?>
						<span class=""><?php $stat->draftPostsNum(); ?></span>
					<?php elseif(isset($request->uid) && $stat->currentDraftPostsNum > 0): ?>
						<span class=""><?php $stat->currentDraftPostsNum(); ?></span>
					<?php endif; ?>
				  </a>
				</div>
				<?php if($user->pass('editor', true) && !isset($request->uid)): ?>
				<div class="am-btn-group am-btn-group-xs">
				  <a href="<?php echo $request->makeUriByRequest('__typecho_all_posts=on'); ?>" class="am-btn am-btn-default<?php if($isAllPosts): ?> am-btn-primary<?php endif; ?>"><?php _e('所有'); ?></a>
				  <a href="<?php echo $request->makeUriByRequest('__typecho_all_posts=off'); ?>" class="am-btn am-btn-default<?php if(!$isAllPosts): ?> am-btn-primary<?php endif; ?>"><?php _e('我的'); ?></a>
				</div>
				<?php endif; ?>
			  </div>
			</div>
			<div class="am-u-sm-12 am-u-md-3">
			  <div class="am-form-group">
				<select name="category" data-am-selected="{btnSize: 'sm'}">
				  <option value=""><?php _e('所有分类'); ?></option>
				  <?php Typecho_Widget::widget('Widget_Metas_Category_List')->to($category); ?>
                  <?php while($category->next()): ?>
				  <option value="<?php $category->mid(); ?>"<?php if($request->get('category') == $category->mid): ?> selected="true"<?php endif; ?>><?php $category->name(); ?></option>
				  <?php endwhile; ?>
				</select>
			  </div>
			</div>
			<div class="am-u-sm-12 am-u-md-3">
			  <div class="am-input-group am-input-group-sm">
				<input type="text" class="am-form-field" placeholder="<?php _e('请输入关键字'); ?>" value="<?php echo htmlspecialchars($request->keywords); ?>" name="keywords" />
				<span class="am-input-group-btn">
					<button class="am-btn am-btn-default" type="submit"><?php _e('筛选'); ?></button>
					<?php if ('' != $request->keywords || '' != $request->category): ?>
					<a href="<?php $options->adminUrl('manage-posts.php' . (isset($request->status) || isset($request->uid) ? '?' . (isset($request->status) ? 'status=' . htmlspecialchars($request->get('status')) : '') . (isset($request->uid) ? '?uid=' . htmlspecialchars($request->get('uid')) : '') : '')); ?>" class="am-btn am-btn-default"><?php _e('&laquo; 取消筛选'); ?></a>
					<?php endif; ?>
					<?php if(isset($request->uid)): ?>
					<input type="hidden" value="<?php echo htmlspecialchars($request->get('uid')); ?>" name="uid" />
					<?php endif; ?>
					<?php if(isset($request->status)): ?>
						<input type="hidden" value="<?php echo htmlspecialchars($request->get('status')); ?>" name="status" />
					<?php endif; ?>
				</span>
			  </div>
			</div>
		</form>
      </div>

      <div class="am-g">
		<form method="post" name="manage_posts" class="am-form">
			<div class="typecho-table-wrap am-u-sm-12">
				<table class="typecho-list-table">
					<colgroup>
						<col width="20"/>
						<col width="2%"/>
						<col width="38%"/>
						<col width=""/>
						<col width="15%"/>
						<col width="15%"/>
						<col width="15%"/>
					</colgroup>
					<thead>
						<tr>
							<th> </th>
							<th> </th>
							<th><?php _e('标题'); ?></th>
							<th><?php _e('作者'); ?></th>
							<th><?php _e('分类'); ?></th>
							<th><?php _e('日期'); ?></th>
							<th><?php _e('操作'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php if($posts->have()): ?>
						<?php while($posts->next()): ?>
						<tr id="<?php $posts->theId(); ?>">
							<td><input type="checkbox" value="<?php $posts->cid(); ?>" name="cid[]"/></td>
							<td><a href="<?php $options->adminUrl('manage-comments.php?cid=' . ($posts->parentId ? $posts->parentId : $posts->cid)); ?>" class="balloon-button size-<?php echo Typecho_Common::splitByCount($posts->commentsNum, 1, 10, 20, 50, 100); ?>" title="<?php $posts->commentsNum(); ?> <?php _e('评论'); ?>"><?php $posts->commentsNum(); ?></a></td>
							<td>
							<a href="<?php $options->adminUrl('write-post.php?cid=' . $posts->cid); ?>"><?php $posts->title(); ?></a>
							<?php 
							if ($posts->hasSaved || 'post_draft' == $posts->type) {
								echo '<em class="status">' . _t('草稿') . '</em>';
							} else if ('hidden' == $posts->status) {
								echo '<em class="status">' . _t('隐藏') . '</em>';
							} else if ('waiting' == $posts->status) {
								echo '<em class="status">' . _t('待审核') . '</em>';
							} else if ('private' == $posts->status) {
								echo '<em class="status">' . _t('私密') . '</em>';
							} else if ($posts->password) {
								echo '<em class="status">' . _t('密码保护') . '</em>';
							}
							?>
							<a href="<?php $options->adminUrl('write-post.php?cid=' . $posts->cid); ?>" title="<?php _e('编辑 %s', htmlspecialchars($posts->title)); ?>"><i class="i-edit"></i></a>
							<?php if ('post_draft' != $posts->type): ?>
							<a href="<?php $posts->permalink(); ?>" title="<?php _e('浏览 %s', htmlspecialchars($posts->title)); ?>"><i class="i-exlink"></i></a>
							<?php endif; ?>
							</td>
							<td><a href="<?php $options->adminUrl('manage-posts.php?uid=' . $posts->author->uid); ?>"><?php $posts->author(); ?></a></td>
							<td><?php $categories = $posts->categories; $length = count($categories); ?>
							<?php foreach ($categories as $key => $val): ?>
								<?php echo '<a href="';
								$options->adminUrl('manage-posts.php?category=' . $val['mid']
								. (isset($request->uid) ? '&uid=' . $request->uid : '')
								. (isset($request->status) ? '&status=' . $request->status : ''));
								echo '">' . $val['name'] . '</a>' . ($key < $length - 1 ? ', ' : ''); ?>
							<?php endforeach; ?>
							</td>
							<td>
							<?php if ($posts->hasSaved): ?>
							<span class="description">
							<?php $modifyDate = new Typecho_Date($posts->modified); ?>
							<?php _e('保存于 %s', $modifyDate->word()); ?>
							</span>
							<?php else: ?>
							<?php $posts->dateWord(); ?>
							<?php endif; ?>
							</td>
							<td>
							<?php
							$post_content = $posts->content;
							preg_match_all( "/<(img|IMG).*?src=[\'|\"](.*?)[\'|\"].*?[\/]?>/", $post_content, $matches );
							if(count($matches[2])>0){
								/*
								//转换阿里图传链接
								$aliprefix=str_replace("/","\/",$option->aliprefix);
								$aliprefix=str_replace(".","\.",$aliprefix);
								preg_match_all( "/<(img|IMG).*?src=[\'|\"](?!".$aliprefix.")(.*?)[\'|\"].*?[\/]?>/", $post_content, $submatches );
								if(count($submatches[2])>0){
									echo '
										<a href="javascript:;" class="alifile_convert_id" id="alifile_convert_id'.$posts->cid.'" data-id="'.$posts->cid.'">转换阿里</a>
									';
								}else{
									echo '无需转换';
								}
								//转换微博图传链接
								$weiboprefix=str_replace("/","\/",$option->weiboprefix);
								$weiboprefix=str_replace(".","\.",$weiboprefix);
								preg_match_all( "/<(img|IMG).*?src=[\'|\"](?!".$weiboprefix.")(.*?)[\'|\"].*?[\/]?>/", $post_content, $submatches );
								if(count($submatches[2])>0){
									echo '
										<a href="javascript:;" class="weibofile_convert_id" id="weibofile_convert_id'.$posts->cid.'" data-id="'.$posts->cid.'">转换微博</a>
									';
								}else{
									echo '无需转换';
								}
								*/
								//图片本地化
								$siteUrl=str_replace("/","\/",$options ->siteUrl);
								$siteUrl=str_replace(".","\.",$siteUrl);
								preg_match_all( "/<(img|IMG).*?src=[\'|\"](?!".$siteUrl.")(.*?)[\'|\"].*?[\/]?>/", $post_content, $localmatches );
								if(count($localmatches[2])>0){
									echo '
										<a href="javascript:;" class="weibofile_local_id" id="weibofile_local_id'.$posts->cid.'" data-id="'.$posts->cid.'">本地化</a>
									';
								}else{
									echo '无需本地化';
								}
							}else{
								echo '未包含图片';
							}
							?>
							</td>
						</tr>
						<?php endwhile; ?>
						<?php else: ?>
						<tr>
							<td colspan="6"><h6 class="typecho-list-table-title"><?php _e('没有任何文章'); ?></h6></td>
						</tr>
						<?php endif; ?>
					</tbody>
				</table>
				<div class="am-cf">
				  <div class="am-btn-group am-btn-group-xs">
					  <button type="button" class="am-btn am-btn-xs" style="height:28px;"><label><i class="sr-only"><?php _e('全选'); ?></i><input type="checkbox" class="typecho-table-select-all" /></label></button>
					  <div class="am-dropdown am-btn-group am-btn-group-xs" data-am-dropdown>
						<button class="am-btn am-dropdown-toggle" data-am-dropdown-toggle><i class="sr-only"><?php _e('操作'); ?></i><?php _e('选中项'); ?> <span class="am-icon-caret-down"></span></button>
						<ul class="am-dropdown-content dropdown-menu">
						  <li><a lang="<?php _e('你确认要删除这些文章吗?'); ?>" href="<?php $security->index('/action/contents-post-edit?do=delete'); ?>"><?php _e('删除'); ?></a></li>
						</ul>
					  </div>
				  </div>
				  <?php if($posts->have()): ?>
				  <div class="am-fr">
					<ul class="am-pagination typecho-pager">
						<?php $posts->pageNav(); ?>
					</ul>
				  </div>
				  <?php endif; ?>
				</div>
				<hr />
				<p>共 <?=$posts->categories[0]["count"]?$posts->categories[0]["count"]:0;?> 条记录</p>
			</div>
		</form>
      </div>
    </div>
	<footer class="admin-content-footer">
	<?php include 'copyright.php';?>
	</footer>
<?php
include 'copyright.php';
include 'common-js.php';
include 'table-js.php';
include 'footer.php';
?>
<script>
$(function(){
	/*
	$(".alifile_convert_id").each(function(){
		var id=$(this).attr("id");
		$("#"+id).click( function () {
			$.post("<?=$plug_url;?>/WeiboFile/ajax/weiboconvert.php",{action:"updateALTCLinks",postid:$(this).attr("data-id")},function(data){
				var data=JSON.parse(data);
				if(data.status=="noneconfig"){
					alert(data.msg);
				}
				window.location.reload();
			});
		});
	});
	$(".weibofile_convert_id").each(function(){
		var id=$(this).attr("id");
		$("#"+id).click( function () {
			$.post("<?=$plug_url;?>/WeiboFile/ajax/weiboconvert.php",{action:"updateWBTCLinks",postid:$(this).attr("data-id")},function(data){
				var data=JSON.parse(data);
				if(data.status=="noneconfig"){
					alert(data.msg);
				}
				window.location.reload();
			});
		});
	});
	*/
	$(".weibofile_local_id").each(function(){
		var id=$(this).attr("id");
		$("#"+id).click( function () {
			$.post("<?=$plug_url;?>/WeiboFile/ajax/weiboconvert.php",{action:"localWBTCLinks",postid:$(this).attr("data-id")},function(data){
				window.location.reload();
			});
		});
	});
});
</script>