## 接口文档

#### 登录验证接口 /publisher/:platform

* :platform 替换为发行平台标识  
* 尽量补全参数有助于统计分析
* 以下参数从客户端获取，从服务端请求

参数名 | 类型 | 必选 | 描述
--- | --- |:---:| ---
app_id   | varchar(16)  | 是 | 应用ID (用于统计)
token    | varchar(64)  | 是 | 发行平台token (也称session)
uid      | int(32)      | 是 | 发行平台UID
custom   | varchar(64)  | 否 | 自定义 (个别平台验证需要的额外参数,多个参数使用竖线分割)
uuid     | varchar(36)  | 否 | 唯一设备ID (建议,SDK根据算法生成,如没有可用adid代替)  
adid     | varchar(36)  | 否 | 广告追踪标识  
device   | varchar(32)  | 否 | 设备信息 例: iphone 6s Plus
version  | varchar(32)  | 否 | 客户端版本  
ip       | varchar(15)  | 否 | 客户端IP  

参考示例 (实际请求参数尽量补全)： 
```
/publisher/baidu?app_id=1001001&token=xxxx&uid=123456&uuid=D821317F-9E1B-4423-84BF-4D57F0B001A9  
```

响应示例：  
```json
{
    "code": 0,
    "msg": "success",
    "open_id": "123456",
    "name": "丹妮",
    "gender": "0",
    "photo": "https://secure.gravatar.com/avatar/0d9d154fb5b905d3f6d606f8b6cbb750?s=80&d=identicon",
    "raw": ""
    "access_token": "eyJ0eXAiNiJ9.eyJzdWhY2fQ.P-b4TJ-wEsP_EjxHRr7c"
}
```

返回说明:  

返回参数名 | 描述
--- | ---  
code    | 返回状态，0成功，其他失败
msg     | 返回消息  
open_id | 平台账号ID, 用于关联游戏 (注意: 非发行平台ID)  
name    | 昵称  
gender  | 性别  
photo   | 头像  
raw     | 发行渠道账号验证返回的原始信息  
access_token   | 授权token, 透传给客户端, 可用于客户端直接与服务端交互, 如登录玩家论坛, 修改玩家信息等  