<?php
/**
 * File Name: TraitLoginController.php
 * ©2020 All right reserved Qiaotongtianxia Network Technology Co., Ltd.
 * @author: hyunsu
 * @date: 2021/4/22 5:15 下午
 * @email: hyunsu@foxmail.com
 * @description:
 * @version: 1.0.0
 * ============================= 版本修正历史记录 ==========================
 * 版 本:          修改时间:          修改人:
 * 修改内容:
 *      //
 */

namespace qh4module\login;


use qh4module\login\external\ExtLogin;
use qh4module\login\models\PasswordLoginModel;
use qh4module\token\HpToken;

trait TraitLoginController
{
    /**
     * 控制登录模块用的扩展类
     * @return ExtLogin
     */
    protected function ext_login()
    {
        return new ExtLogin();
    }

    /**
     * 用户退出
     */
    public function actionLogout()
    {
        HpToken::setTokenInfo([
            'is_logout' => 1,
            'del_time' => time(),
        ]);

        return true;
    }

    /**
     * 通过密码登录
     * @return array
     */
    public function actionLoginByPassword()
    {
        $model = new PasswordLoginModel([
            'external' => $this->ext_login()
        ]);

        return $this->runModel($model);
    }
}