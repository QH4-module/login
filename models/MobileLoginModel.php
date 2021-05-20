<?php
/**
 * File Name: MobileLoginModel.php
 * ©2020 All right reserved Qiaotongtianxia Network Technology Co., Ltd.
 * @author: hyunsu
 * @date: 2021/5/8 4:57 下午
 * @email: hyunsu@foxmail.com
 * @description:
 * @version: 1.0.0
 * ============================= 版本修正历史记录 ==========================
 * 版 本:          修改时间:          修改人:
 * 修改内容:
 *      //
 */

namespace qh4module\login\models;


class MobileLoginModel extends LoginBaseModel
{
    /**
     * @var string 手机号,必须
     */
    public $mobile;

    /**
     * @var string|int 验证码,必须
     */
    public $code;

    // 其它接收参数看父类

    /**
     * {@inheritDoc}
     */
    public function rules()
    {
        return $this->mergeRules([
            [['mobile', 'code'], 'required'],
            [['mobile'], 'mobile'],
            [['code'], 'match', 'pattern' => '/^[0-9A-Za-z]+$/'],
        ], parent::rules());
    }

    /**
     * {@inheritDoc}
     */
    public function attributeLangs()
    {
        return $this->mergeLanguages([
            'mobile' => '手机号',
            'code' => '验证码',
        ], parent::attributeLangs());
    }


    /**
     * {@inheritDoc}
     */
    public function run()
    {
        $db = $this->external->getDb();
        // 检查最大失败次数
        if ($this->external->max_login_fail_num > 0) {
            $max_num = $this->checkFailNum($db, $this->mobile, $this->device_type, $this->device_id);
            if ($max_num > $this->external->max_login_fail_num) {
                $msg = $this->external->maxLoginFailHandle($this, 1, $this->external->max_login_fail_num);
                if ($msg) $this->addError('mobile', $msg);
                return false;
            }
        }

        // 查找用户
        $this->findUser();
        // 用户不存在
        if (empty($this->result_user)) {
            $fail_num = $this->loginFail($db, $this->mobile, $this->device_type, $this->device_id);
            if ($this->external->max_login_fail_num > 0 && $fail_num > $this->external->max_login_fail_num) {
                // 失败上限处理
                $msg = $this->external->maxLoginFailHandle($this, 2, $this->external->max_login_fail_num);
            } else {
                // 普通失败处理
                $msg = $this->external->loginFailHandle($this, $fail_num, 2, -1);
            }
            if ($msg) $this->addError('mobile', $msg);
            return false;
        }

        // 用户状态不正常
        if ($this->result_user['state'] != 1) {
            $this->addError('username', '该账户禁止登录');
            return false;
        }

        if ($this->external->checkSmsCode($this->mobile,$this->code)) {
            // 验证码正确,登录成功
            $this->loginSuccess($db, $this->mobile, $this->device_type, $this->device_id);
            return $this->external->loginSuccessHandle($this, 2, $this->result_user);
        } else {
            // 验证码错误处理
            $fail_num = $this->loginFail($db, $this->mobile, $this->device_type, $this->device_id);
            if ($this->external->max_login_fail_num > 0 && $fail_num > $this->external->max_login_fail_num) {
                // 失败上限处理
                $msg = $this->external->maxLoginFailHandle($this, 2, $this->external->max_login_fail_num);
            } else {
                // 普通失败处理
                $msg = $this->external->loginFailHandle($this, $fail_num, 2, -2);
            }
            if ($msg) $this->addError('mobile', $msg);
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
            ->from($this->external->userTableName())
            ->whereArray(['mobile'=>$this->mobile])
            ->where("del_time=0");

        $this->result_user = $sql->row();
    }
}