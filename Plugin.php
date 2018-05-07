<?php
/**
 * 将 Typecho 的附件上传至新浪微博云存储中，无需申请appid，不占用服务器大小，可永久保存，只需一个不会登录的微博小号即可。
 * @package WeiboFile For Typecho
 * @author 二呆
 * @version 1.0.0
 * @link http://www.tongleer.com/
 * @date 2018-5-2
 */
require __DIR__ . '/Sinaupload.php';

class WeiboFile_Plugin implements Typecho_Plugin_Interface
{
    // 激活插件
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Upload')->uploadHandle = array('WeiboFile_Plugin', 'uploadHandle');
        Typecho_Plugin::factory('Widget_Upload')->modifyHandle = array('WeiboFile_Plugin', 'modifyHandle');
        Typecho_Plugin::factory('Widget_Upload')->deleteHandle = array('WeiboFile_Plugin', 'deleteHandle');
        Typecho_Plugin::factory('Widget_Upload')->attachmentHandle = array('WeiboFile_Plugin', 'attachmentHandle');
        return _t('插件已经激活，需先配置微博图床的信息！');
    }


    // 禁用插件
    public static function deactivate()
    {
        return _t('插件已被禁用');
    }


    // 插件配置面板
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        $weibouser = new Typecho_Widget_Helper_Form_Element_Text('weibouser', null, '', _t('微博小号用户名：'));
        $form->addInput($weibouser->addRule('required', _t('微博小号用户名不能为空！')));

        $weibopass = new Typecho_Widget_Helper_Form_Element_Text('weibopass', null, '', _t('微博小号密码：'));
        $form->addInput($weibopass->addRule('required', _t('微博小号密码不能为空！')));
    }


    // 个人用户配置面板
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }


    // 获得插件配置信息
    public static function getConfig()
    {
        return Typecho_Widget::widget('Widget_Options')->plugin('WeiboFile');
    }


    // 删除文件
    public static function deleteFile($filepath)
    {
        return true;
    }


    // 上传文件
    public static function uploadFile($file, $content = null)
    {

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
		
		$Sinaupload=new Sinaupload('');
		$cookie=$Sinaupload->login($option->weibouser,$option->weibopass);
		$result=$Sinaupload->upload($filename);
		$arr = json_decode($result,true);
        return array
            (
                'name'  =>  $file['name'],
                'path'  =>  $arr['data']['pics']['pic_1']['pid'],
                'size'  =>  $file['size'],
                'type'  =>  $ext,
                'mime'  =>  Typecho_Common::mimeContentType($filename)
            );
    }


    // 上传文件处理函数
    public static function uploadHandle($file)
    {
        return self::uploadFile($file);
    }


    // 修改文件处理函数
    public static function modifyHandle($content, $file)
    {
        return self::uploadFile($file, $content);
    }


    // 删除文件
    public static function deleteHandle(array $content)
    {
        self::deleteFile($content['attachment']->path);
    }


    // 获取实际文件绝对访问路径
    public static function attachmentHandle(array $content)
    {
        $option = self::getConfig();
        return Typecho_Common::url($content['attachment']->path, 'https://ws3.sinaimg.cn/large/');
    }
}