<img src="https://ws3.sinaimg.cn/large/ecabade5ly1fqwuz2k658j20le05nt8i" alt="Typecho-WeiboFile新浪微博图片上传插件" />

### Typecho-WeiboFile新浪（优酷）微博图片（视频）上传插件
---

1、将 Typecho 的附件上传至新浪（优酷）微博云存储中，无需申请appid，不占用服务器大小，可永久保存，只需一个不会登录的微博小号即可。<br />
2、在图床的基础上新增上传视频和视频解析的功能。<br />
3、上传视频提示POST Length过大，尽管服务器修改了php.ini的post_max_size，也一直未解决，所以做了限制，如果你知道解决办法，麻烦告诉我吧，万分感谢。

程序有可能会遇到bug不改版本号直接修改代码的时候，所以扫描以下二维码关注公众号“同乐儿”，可直接与作者二呆产生联系，不再为bug烦恼，随时随地解决问题。

<img src="http://me.tongleer.com/content/uploadfile/201706/008b1497454448.png">

#### 使用方法：
第一步：下载本插件，放在 `usr/plugins/` 目录中（插件文件夹名必须为WeiboFile）；<br />
第二步：激活插件；<br />
第三步：填写微博小号等等配置；<br />
第四步：完成。

#### 使用注意：
此插件V1.0.1版本使用php5.6编写，php7.0“可能”会报Sinaupload.php中的语法错误，建议使用php5.6，因为7.0实在太高了=_=!

#### 与我联系：
作者：二呆<br />
网站：http://www.tongleer.com/<br />
Github：https://github.com/muzishanshi/WeiboFile

#### 参考资料：
幻想领域：https://www.52ecy.cn/<br />
七牛：https://www.qiniu.com/

#### 更新记录：
2018-07-11 新增大多数视频格式上传、管理，网站登录用户也可上传视频，但存在上传视频过大无响应问题，待解决。<br />
2018-07-10 新增解析秒拍、抖音视频功能<br />
2018-07-09 新增上传视频功能<br />
2018-07-02 增加了使用备注信息和上传后的图片增加后缀可成为一个完整的图片路径<br />
2018-05-02 第一版本实现