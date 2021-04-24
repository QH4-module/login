<?php
/**
 * File Name: ExtLogin.php
 * ©2020 All right reserved Qiaotongtianxia Network Technology Co., Ltd.
 * @author: hyunsu
 * @date: 2021/4/22 5:07 下午
 * @email: hyunsu@foxmail.com
 * @description:
 * @version: 1.0.0
 * ============================= 版本修正历史记录 ==========================
 * 版 本:          修改时间:          修改人:
 * 修改内容:
 *      //
 */

namespace qh4module\login\external;


use qh4module\token\JWT;
use qttx\components\request\Request;
use qttx\web\External;
use qttx\web\ServiceModel;

class ExtLogin extends External
{
    /**
     * @var int 间隔多久登录算连续失败,单位秒
     * 超过这个时间,重新计算失败次数
     * 默认10分钟内算连续登录失败
     */
    public $login_fail_interval_time = 600;

    /**
     * @var int 最大登录失败次数
     * 连续失败分为   同一个ip地址连续失败
     *              用户输入同一个账号连续失败
     *              同一个设备连续失败
     * 3种失败不论哪个达到最大次数,都会触发 `maxLoginFailHandle()` 函数,并且不触发 `loginFailHandle()` 函数
     * 0 表示不限制失败次数
     */
    public $max_login_fail_num = 8;

    /**
     * @var string[] 设备类型限制,根据业务重写该属性
     */
    public $device_type_limit = ['ios', 'android', 'web', 'pc', 'wechat'];

    /**
     * @var string[] 密码登录允许作为账号的字段
     */
    public $enable_username = ['account', 'mobile', 'email'];

    /**
     * 返回 `user` 表名称
     * @return string
     */
    public function userTableName()
    {
        return '{{%user}}';
    }

    /**
     * 返回 `user_login_history` 表名称
     * @return string
     */
    public function loginHistoryTableName()
    {
        return '{{%user_login_history}}';
    }

    /**
     * 连续登录失败达到最大次数的处理函数
     * 返回值将作为错误信息处理
     * @param ServiceModel $model
     * @param int $type 1 密码登录 2手机号登录
     * @param int $max_login_fail_num 最大失败次数
     * @return bool
     */
    public function maxLoginFailHandle($model,$type, $max_login_fail_num)
    {
        return "连续登录失败超过{$max_login_fail_num}次，请稍后再试";
    }

    /**
     * 登录失败的处理函数
     * 返回值作为错误信息处理
     * @param ServiceModel $model
     * @param int $type 1 密码登录 2手机号登录
     * @param $tag int 失败的原因
     *                  -1 用户不存在
     *                  -2 密码或验证码错误
     *                  -3 非正常用户(state != 1)
     * @return bool
     */
    public function loginFailHandle($model,$type, $tag)
    {
        if ($tag == -3) {
            return '用户禁止登录';
        }else{
            if ($type == 1) {
                return '用户名密码错误';
            } else {
                return '手机号验证码错误';
            }
        }
    }


    /**
     * 登录成功的处理函数,默认返回token
     * 函数返回值会传递给客户端
     * @param ServiceModel $model
     * @param int $type 1 密码登录 2手机号登录
     * @param array $user 用户信息
     * @return mixed
     */
    public function loginSuccessHandle($model,$type, array $user)
    {
        $key = file_get_contents(APP_PATH . '/libs/rsa-private-key.pem');
        $time = time();
        $payload = array(
            'user_id' => $user['id'],
            'iss' => 'system',
            'iat' => $time,
            'exp' => $time + 7 * 24 * 3600,
            'nbf' => $time,
        );
        $token = JWT::encode($payload, $key);

        return $token;
    }

    /**
     * 获取客户端ip地址
     * @return string
     */
    public function getClientIp()
    {
        $ip = Request::getClientIp();
        if ($ip == '0.0.0.0') {
            return '';
        }
        return $ip;
    }

    /**
     * 手机号登录才需要
     * 检查验证码
     * 默认作为阿里云短信验证码校验
     * @param string $mobile 手机号
     * @param string $code 验证码
     * @return bool
     */
    public function validateMsgCode($mobile, $code)
    {
       return true;
    }

}