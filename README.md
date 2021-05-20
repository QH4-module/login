QH4框架扩展模块-登录模块

### 功能
1、用户名密码登录


### 依赖
该模块依赖于 `Token` 模块，需要该模块
```
composer require qh4/token
```


### api 列表
```php
actionLogout()
```
退出登录

```php
actionLoginByPassword()
```
通过密码登录

```php
actionLoginByMobile()
```
通过手机号和验证码登录


### 方法列表
```php
/**
 * 根据用户输入的密码生成随机码
 * @param $password
 * @return array 返回密码和混淆值
 */
function generatePassword($password)
```

```php
/**
 * 对比输入的密码是否正确
 * @param $input    string 用户输入的密码
 * @param $password string 数据库记录的密码
 * @param $salt string 数据库记录的混淆值
 * @return bool
 */
function comparePassword($input, $password, $salt)
```