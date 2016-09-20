# 数据响应及格式

## 响应头:

> Authorization : 认证成功后返回的 token，浏览器下次认证时需要携带此 token。服务器自动从请求头
 'Authorization' 数据或 GET/POST/COOKIE 的参数 'token' 中获取认证数据。

## 成功响应

- 状态码：200

- 响应体：直接返回JSON数据

## 错误响应

> 使用自定义类： App\Exceptions\ApiException::class 抛出错误/异常。在 App\Exceptions\Handler::class
中捕获并返回JSON响应。eg: throw new ApiException(['user'=>'用户不存在。'], 422, '验证用户名失败。');

- 状态码：422/400/500/...

- 响应体：
```
message: '登录失败',
status_code: 422,
errors: [
  'username': [
    '用户不存在',
  ],
  'password': [
    '密码不正确',
  ]
],
```
## 常用状态码

- 200 ：响应成功，所有逻辑正确才返回

- 400 ：错误请求

- 401 ：未授权/未认证

- 404 ：未找到

- 405 ：请求方法错误(GET/POST/...)

- 422 ：请求格式正确但语义错误，如字段未通过验证

- 429 ：请求次数过多

- 500 ：服务器错误（包括大于 500 ）