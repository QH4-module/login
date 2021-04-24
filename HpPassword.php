<?php
/**
 * File Name: AccountCommon.php
 * ©2020 All right reserved Qiaotongtianxia Network Technology Co., Ltd.
 * @author: hyunsu
 * @date: 2020-12-23 1:18 下午
 * @email: hyunsu@foxmail.com
 * @description:
 * @version: 1.0.0
 * ============================= 版本修正历史记录 ==========================
 * 版 本:          修改时间:          修改人:
 * 修改内容:
 *      //
 */

namespace qh4module\login;


use qttx\helper\StringHelper;

class HpPassword
{
    /**
     * 根据用户输入的密码生成随机码
     * @param $password
     * @return array 返回密码和混淆值
     */
    public static function generatePassword($password)
    {
        $salt = StringHelper::random(8);

        $password = md5($salt . $password);

        return [$password, $salt];
    }

    /**
     * 对比输入的密码是否正确
     * @param $input    string 用户输入的密码
     * @param $password string 数据库记录的密码
     * @param $salt string 数据库记录的混淆值
     * @return bool
     */
    public static function comparePassword($input, $password, $salt)
    {
        return md5($salt . $input) === $password;
    }
}
