

## 更新日志

##### v1.21 :boom:
* 屏蔽隐私信息/account/profile
* 后端调用接口/api/
* 支持绑定手机号/account/bind

##### v1.20
* 增加短消息接口 /public/sms
* 支持手机短信登录
* 支持使用手机号与密码登录

##### v1.10
* 增加账号信息接口 /account/profile  
* 增加修改账号信息接口 /account/modify  
* 增加修改密码接口 /account/password

___

## 部署说明
平台账号针对第三方登录如FB,WX使用统一的第三方应用ID配置.数据库表名形如oauth_fb  
如各个游戏应用使用非统一的第三方应用ID配置用于登录,则对应表名形如1001010_oauth_fb

___

## 账号接口文档

#### 账号注册接口 /public/register
参数名 | 类型 | 必选 | 描述
--- | --- |:---:| ---
username | varchar(64)  | 是 | 用户名(最少6位 0-9a-zA-Z@_-.)
password | varchar(32)  | 是 | 密码(最少6位)
app_id   | varchar(16)  | 是 | 应用ID(用于统计)
uuid     | varchar(36)  | 否 | 唯一设备ID(建议)  
adid     | varchar(36)  | 否 | 广告追踪标识  
device   | varchar(32)  | 否 | 设备信息  
version  | varchar(32)  | 否 | 客户端版本  
channel  | varchar(32)  | 否 | 渠道 - 默认留空  
ip       | varchar(15)  | 否 | 客户端IP (服务端传参时建议)  

返回值：
```json
{
    "code": 0,
    "msg": "success",
    "open_id": "123456",
    "name": "丹妮",
    "gender": "2",
    "photo": "https://avatars2.githubusercontent.com/u/4596910?v=3&s=460",
    "access_token": "eyJ0eXAiNiJ9.eyJzdWhY2fQ.P-b4TJ-wEsP_EjxHRr7c"
}
```

___

#### 登录接口 /public/access_token
1、账号密码登录  

参数名 | 类型 | 必选 | 描述
--- | --- |:---:| ---
username | varchar(64)  | 是 | 用户名或手机号, 最少6位
password | varchar(32)  | 是 | 密码, 最少6位
app_id   | varchar(16)  | 是 | 应用ID(用于统计)
uuid     | varchar(36)  | 否 | 唯一设备ID(建议)  
adid     | varchar(36)  | 否 | 广告追踪标识  
device   | varchar(32)  | 否 | 设备信息  
version  | varchar(32)  | 否 | 客户端版本  
channel  | varchar(32)  | 否 | 渠道 - 默认留空  
ip       | varchar(15)  | 否 | 客户端IP (服务端传参时建议)  

2.1、OAuth2.0授权码模式authorization_code  

参数名 | 类型 | 必选 | 描述
--- | --- |:---:| ---
platform    | varchar(16)  | 是 | 平台类型，如weixin、google
code        | varchar(256) | 是 | 第三方平台授权码，用于换取第三方平台的access_token
app_id   | varchar(16)  | 是 | 应用ID(用于统计)
uuid     | varchar(36)  | 否 | 唯一设备ID(建议)  
adid     | varchar(36)  | 否 | 广告追踪标识  
device   | varchar(32)  | 否 | 设备信息  
version  | varchar(32)  | 否 | 客户端版本  
channel  | varchar(32)  | 否 | 渠道 - 默认留空  
ip       | varchar(15)  | 否 | 客户端IP (服务端传参时建议)  

2.2、OAuth2.0客户端模式access_token  

参数名 | 类型 | 必选 | 描述
--- | --- |:---:| ---
platform     | varchar(16)  | 是 | 平台类型，如facebook
access_token | varchar(1000) | 是 | 第三方平台access_token（*仅在此处使用第三方平台access_token，其他均为本平台access_token*）
open_id      | varchar(32)  | 是 | 第三方open_id
open_name    | varchar(32)  | 否 | 第三方open_name
gender       | int(3)       | 否 | 性别, 默认0, 男1 女2
mobile       | varchar(32)  | 否 | 手机号
email        | varchar(64)  | 否 | 邮件地址
photo        | varchar(512) | 否 | 头像
birthday     | date(10)     | 否 | 1987-11-04
refresh_token| varchar(256) | 否 | refresh_token
app_id   | varchar(16)  | 是 | 应用ID(用于统计)
uuid     | varchar(36)  | 否 | 唯一设备ID(建议)  
adid     | varchar(36)  | 否 | 广告追踪标识  
device   | varchar(32)  | 否 | 设备信息  
version  | varchar(32)  | 否 | 客户端版本  
channel  | varchar(32)  | 否 | 渠道 - 默认留空  
ip       | varchar(15)  | 否 | 客户端IP (服务端传参时建议)  

3、游客登陆UUID登录

参数名 | 类型 | 必选 | 描述
--- | --- |:---:| ---
app_id   | varchar(16)  | 是 | 应用ID(用于统计)  
uuid     | varchar(36)  | 是 | 唯一设备ID  
adid     | varchar(36)  | 否 | 广告追踪标识  
device   | varchar(32)  | 否 | 设备信息  
version  | varchar(32)  | 否 | 客户端版本  
channel  | varchar(32)  | 否 | 渠道 - 默认留空  
ip       | varchar(15)  | 否 | 客户端IP (服务端传参时建议)  

4、手机SMS登录 :fire:

参数名 | 类型 | 必选 | 描述
--- | --- |:---:| ---
app_id   | varchar(16)  | 是 | 应用ID(用于统计)
mobile   | varchar(16)  | 是 | 手机号码  
sms      | varchar(6)   | 是 | 短信验证码, 获取参考/public/sms
password | varchar(32)  | 否 | 密码, 仅用于首次登陆设定密码, 后续登录自动忽略
uuid     | varchar(36)  | 否 | 唯一设备ID(建议)  
adid     | varchar(36)  | 否 | 广告追踪标识  
device   | varchar(32)  | 否 | 设备信息  
version  | varchar(32)  | 否 | 客户端版本  
channel  | varchar(32)  | 否 | 渠道 - 默认留空  
ip       | varchar(15)  | 否 | 客户端IP (服务端传参时建议)  

返回值：
```json
{
    "code": 0,
    "msg": "success",
    "open_id": "123456",
    "name": "丹妮",
    "gender": "2",
    "photo": "https://avatars2.githubusercontent.com/u/4596910?v=3&s=460",
    "access_token": "eyJ0eXAiNiJ9.eyJzdWhY2fQ.P-b4TJ-wEsP_EjxHRr7c"
}
```

*更多OAuth2.0参考：*  
*[https://oauth.net/code](https://oauth.net/code)*  
*[https://github.com/thephpleague/oauth2-client](https://github.com/thephpleague/oauth2-client)*  
*[https://socialiteproviders.github.io](https://socialiteproviders.github.io)*  
*[http://www.ruanyifeng.com/blog/2014/05/oauth_2_0.html](http://www.ruanyifeng.com/blog/2014/05/oauth_2_0.html)*  

___

#### 注销接口 /public/logout
参数名 | 类型 | 必选 | 描述
--- | --- |:---:| ---
access_token | varchar(1000)  | 是 | 授权令牌access_token

返回值：
```json
{"code":0,"msg":"success"}
```

___

#### 验证ACCESS_TOKEN /public/verify_access_token/{ACCESS_TOKEN}（*仅限服务端调用本接口*）

*注: 如客户端直与账号系统交互，则需要游戏服务端通过本接口验证*  

参数名 | 类型 | 必选 | 描述
--- | --- |:---:| ---
access_token | varchar(1000)  | 是 | access_token(非第三方)

返回值：
```json
{
    "code": 0,
    "msg": "success",
    "open_id": "123456",
    "name": "丹妮",
    "gender": "2",
    "photo": "https://avatars2.githubusercontent.com/u/4596910?v=3&s=460"
}
```

___

#### 账号信息 /account/profile :fire:
参数名 | 类型 | 必选 | 描述
--- | --- |:---:| ---
access_token | varchar(1000) | 是 | 平台access_token

返回:

```json
{
    "code":0,
    "msg":"success",
    "id":"123456",
    "account":"joe***chu@domain.com",
    "name":"丹妮",
    "gender":"2",
    "status":"1",
    "mobile":"133****6810",
    "photo":"",
    "create_time":"2017-02-15 11:40:43",
    "update_time":"2017-03-13 16:31:43",
    "more":{
        "mobile":{
            "platform":"mobile",
            "open_id":"133****6810",
            "open_name":"133****6810",
            "create_time":"2017-03-13 16:31:43"
        },
        "weixin":{
            "platform":"weixin",
            "open_id":"ood--wOLf2qYhZTjmFyy7dIBCprI",
            "open_name":"丹妮",
            "create_time":"2017-02-17 15:36:53"
        },
        "device":{
            "platform":"device",
            "open_id":"D821317F-9E1B-4423-84BF-4D57F0B001A9",
            "open_name":"iphone 7 plus",
            "create_time":"2017-02-15 11:40:43"
        }
    }
}
```

___

#### 账号绑定 /account/bind (*如已绑定则不能再次绑定*) 开发中 :sleeping:

1、绑定登录账号

参数名 | 类型 | 必选 | 描述
--- | --- |:---:| ---
app_id      | varchar(32)   | 否 | 应用ID
access_token| varchar(1000) | 是 | 平台access_token
username    | varchar(64)   | 是 | 用户名, 最少6位
password    | varchar(32)   | 是 | 密码, 最少6位

2、绑定手机号

参数名 | 类型 | 必选 | 描述
--- | --- |:---:| ---
app_id      | varchar(32)   | 否 | 应用ID
access_token| varchar(1000) | 是 | 平台access_token
mobile      | varchar(16)   | 是 | 手机号码
sms         | varchar(16)   | 是 | 短信验证码（获取参考短信接口）

3、绑定第三方平台账号 - 授权码模式 (weixin, weibo, facebook, google)

参数名 | 类型 | 必选 | 描述
--- | --- |:---:| ---
app_id      | varchar(32)   | 是 | 应用ID（必须）
access_token| varchar(1000) | 是 | 平台access_token
platform    | varchar(16)   | 是 | 平台类型，如weixin、google、facebook等
open_id     | varchar(36)   | 是 | 第三方open_id
open_name   | varchar(36)   | 否 | 第三方open_name
code        | varchar(256)  | 是 | 第三方平台授权码


___

#### 修改账号信息 /account/modify :fire:
参数名 | 类型 | 必选 | 描述
--- | --- |:---:| ---
access_token| varchar(1000) | 是 | 平台access_token
account     | varchar(64)   | 否 | 登录账号名(最少6位 0-9a-zA-Z@_-.)
name        | varchar(32)   | 否 | 昵称
photo       | varchar(512)  | 否 | 头像地址

返回:
```json
{"code":0,"msg":"success"}
```

___

#### 修改密码 /account/password :fire:
参数名 | 类型 | 必选 | 描述
--- | --- |:---:| ---
access_token| varchar(1000) | 是 | 平台access_token
old_pwd     | varchar(64)   | 是 | 原始密码
password    | varchar(64)   | 是 | 新密码(最少6位)

返回:
```json
{"code":0,"msg":"success"}
```

___

#### 找回密码[web page] /public/forget
参数名 | 类型 | 必选 | 描述
--- | --- |:---:| ---
account | varchar(40)  | 是 | Email账号

___

#### 短信息接口 /public/sms :iphone:
参数名 | 类型 | 必选 | 描述
--- | --- |:---:| ---
mobile | varchar(16)  | 是 | 手机号码

___

#### 账号统计 /public/stat（*注册登录时会自动调用本接口*）
参数名 | 类型 | 必选 | 描述
--- | --- |:---:| ---
open_id  | varchar(32)  | 是 | 账号ID
app_id   | varchar(16)  | 是 | 应用ID(用于统计)
uuid     | varchar(36)  | 是 | 唯一设备ID(建议)
adid     | varchar(36)  | 否 | 广告追踪标识  
device   | varchar(32)  | 否 | 设备信息 例: iphone7 plus
version  | varchar(32)  | 否 | 客户端版本  
channel  | varchar(32)  | 否 | 渠道 - 默认留空  
ip      | varchar(15)  | 否 | 客户端IP(服务端传参时建议)
