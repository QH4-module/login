<?php
/**
 * File Name: PasswordLoginModel.php
 * ©2020 All right reserved Qiaotongtianxia Network Technology Co., Ltd.
 * @author: hyunsu
 * @date: 2020-12-25 8:42 上午
 * @email: hyunsu@foxmail.com
 * @description:
 * @version: 1.0.0
 * ============================= 版本修正历史记录 ==========================
 * 版 本:          修改时间:          修改人:
 * 修改内容:
 *      //
 */

namespace qh4module\login\models;


use qh4module\login\external\ExtLogin;
use qh4module\login\HpPassword;

/**
 * Class PasswordLoginModel
 * 通过账号密码登录
 * @property ExtLogin $external 功能控制类
 * @package service\account\login\models
 */
class PasswordLoginModel extends LoginBaseModel
{
    /**
     * @var string 接收参数,必须,用户名
     */
    public $username;

    /**
     * @var string 接收参数,必须,密码
     */
    public $password;

    /**
     * @var string 设备类型,该参数根据业务自定义
     */
    public $deviceType = '';

    /**
     * @var string 设备号
     */
    public $deviceId = '';


    /**
     * {@inheritDoc}
     */
    public function rules()
    {
        return $this->mergeRules([
            [['username', 'password'], 'required'],
            [['username', 'password'], 'string'],
            [['deviceType'], 'in', 'range' => $this->external->device_type_limit],
            [['deviceId'], 'string']
        ], $this->external->rules());
    }

    /**
     * {@inheritDoc}
     */
    public function attributeLangs()
    {
        return $this->mergeLanguages([
            'zh_cn' => [
                'username' => '用户名',
                'password' => '密码',
                'deviceType' => '设备类型',
                'deviceId' => '设备编号',
            ],
        ], $this->external->attributeLangs());
    }


    /**
     * {@inheritDoc}
     */
    public function run()
    {
        $db = $this->external->getDb();
        // 查找用户
        $this->findUser();
        if (empty($this->result_user)) {    // 用户不存在
            if ($this->loginFail($db,$this->username, $this->deviceType, $this->deviceId)) {
                $msg = $this->external->maxLoginFailHandle($this,1, $this->external->max_login_fail_num);
            } else {
                $msg = $this->external->loginFailHandle($this,1, -1);
            }
            if ($msg) $this->addError('username', $msg);
            return false;
        }else if ($this->result_user['state'] != 1) {   //非正常用户
            $msg = $this->external->loginFailHandle($this,1, -3);
            if ($msg) $this->addError('username', $msg);
            return false;
        }

        if (HpPassword::comparePassword($this->password, $this->result_user['password'], $this->result_user['salt'])) {
            // 密码正确,登录成功
            $this->loginSuccess($db,$this->username, $this->deviceType, $this->deviceId);
            return $this->external->loginSuccessHandle($this,1, $this->result_user);
        } else {
            // 密码错误处理
            if ($this->loginFail($db,$this->username, $this->deviceType, $this->deviceId)) {
                $msg = $this->external->maxLoginFailHandle($this,1, $this->external->max_login_fail_num);
            } else {
                $msg = $this->external->loginFailHandle($this,1, -2);
            }
            if ($msg) $this->addError('username', $msg);
            return false;
        }
    }


    /**
     * 查找用户
     */
    protected function findUser()
    {
        $sql = $this->external->getDb()
            ->select('*')
            ->from($this->external->userTableName());

        $w1 = "";
        foreach ($this->external->enable_username as $item) {
            if (!empty($w1)) {
                $w1 .= ' or ';
            }
            $w1 .= " `{$item}`= :{$item} ";
        }

//        $sql->where("(a= :a or b= :b) and del_time=0")
        $sql->where("($w1) and del_time=0");
        foreach ($this->external->enable_username as $item) {
            $sql->bindValue($item, $this->username);
        }

        $this->result_user = $sql->row();
    }

}
