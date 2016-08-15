## Shop-Api

一个开发中的电商网站 api 个人学习实践项目。

## 使用到的包或工具
- laravel/lumen-framework : 开发框架
- vlucas/phpdotenv : 框架使用 .env 配置文件
- dingo/api : Api构建包，在 app.php 中注册
- illuminate/redis : 缓存，在 app.php 中注册，若配置失败自动转化为 File 缓存，缓存键名在 App/Repositories/Caches/CacheKeysDefined.php 中定义及设置时长。
- apidoc : Api 文档构建工具及规范，url : http://apidocjs.com/ ，文档本地目录：/public/api-doc/
- raveren/kint : 开发调试工具