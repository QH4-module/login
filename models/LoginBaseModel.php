<?php
/**
 * File Name: LoginBaseModel.php
 * ©2020 All right reserved Qiaotongtianxia Network Technology Co., Ltd.
 * @author: hyunsu
 * @date: 2020-12-23 4:14 下午
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
use QTTX;
use qttx\components\db\DbModel;
use qttx\web\ServiceModel;

/**
 * Class LoginBaseModel
 * @package service\account\login\models
 */
class LoginBaseModel extends ServiceModel
{
    /**
     * @var string 接收参数,设备类型,该参数根据业务自定义,非必须
     * 设备类型和设备号对登录无影响,设备类型和设备号共同影响失败次数
     * @see ExtLogin::max_login_fail_num 关于失败次数
     */
    public $device_type = '';

    /**
     * @var string 接收参数,设备号,非必须
     */
    public $device_id = '';

    /**
     * @var ExtLogin
     */
    protected $external;

    /**
     * @var array 根据用户输入找到的用户
     */
    protected $result_user;


    /**
     * {@inheritDoc}
     */
    public function rules()
    {
        return $this->mergeRules([
            [['device_type'], 'in', 'range' => $this->external->device_type_limit],
            [['device_id'], 'string', 'max' => 200],
        ], $this->external->rules());
    }

    /**
     * {@inheritDoc}
     */
    public function attributeLangs()
    {
        return $this->mergeLanguages([
            'device_type' => '设备类型',
            'device_id' => '设备编号',
        ],$this->external->attributeLangs());
    }


    /**
     * 获取用户输入最后一次登录信息
     * @param DbModel $db
     * @param string $input
     * @return array
     */
    public function getLastInputLoginHistory($db, $input)
    {
        return $db->select('*')
            ->from($this->external->loginHistoryTableName())
            ->where('user_input= :input')
            ->bindValue('input', $input)
            ->orderByDESC(['create_time'])
            ->row();
    }

    /**
     * 获取指定ip最后一次登录信息
     * @param DbModel $db
     * @param $ip
     * @return array
     */
    public function getLastIpLoginHistory($db, $ip)
    {
        return $db->select('*')
            ->from($this->external->loginHistoryTableName())
            ->where('from_ip= :ip')
            ->bindValue('ip', $ip)
            ->orderByDESC(['create_time'])
            ->row();
    }

    /**
     * 获取指定设备最后一次登录信息
     * @param DbModel $db
     * @param $device_type
     * @param $device_id
     * @return array
     */
    public function getLastDeviceLoginHistory($db, $device_type, $device_id)
    {
        return $db->select('*')
            ->from($this->external->loginHistoryTableName())
            ->where('device_id= :id and device_type= :type')
            ->bindValues([
                'id' => $device_id,
                'type' => $device_type
            ])
            ->orderByDESC(['create_time'])
            ->row();
    }

    /**
     * @param DbModel $db
     * @param array $cols
     * @return int
     */
    protected function insertHistory($db, $cols = [])
    {
        $_cols = [
            'id' => QTTX::$app->snowflake->id(),
            'user_input' => '',
            'from_ip' => $this->external->getClientIp(),
            'create_time' => time(),
            'is_success' => 0,
            'device_type' => '',
            'device_id' => '',
            'ip_fail_num' => 0,
            'input_fail_num' => 0,
            'device_fail_num' => 0,
            'user_id' => '',
        ];

        if (!empty($this->result_user)) {
            $_cols['user_id'] = $this->result_user['id'];
        }

        $_cols = array_merge($_cols, $cols);

        return $db->insert($this->external->loginHistoryTableName())
            ->cols($_cols)
            ->query();
    }

    /**
     * 登录成功处理
     * @param DbModel $db
     * @param $input
     * @param string $device_type
     * @param string $device_id
     */
    protected function loginSuccess($db, $input, $device_type = '', $device_id = '')
    {
        $this->insertHistory($db, [
            'user_input' => $input,
            'is_success' => 1,
            'device_type' => $device_type,
            'device_id' => $device_id,
            'input_fail_num' => 0,
            'ip_fail_num' => 0,
            'device_fail_num' => 0,
        ]);
    }

    /**
     * 检查失败次数
     * @param $db
     * @param $input
     * @param string $device_type
     * @param string $device_id
     * @return int
     */
    protected function checkFailNum($db, $input, $device_type = '', $device_id = '')
    {
        $last_input = self::getLastInputLoginHistory($db, $input);
        $last_ip = self::getLastIpLoginHistory($db, $this->external->getClientIp());
        if (!empty($device_id) && !empty($device_type)) {
            $last_device = self::getLastDeviceLoginHistory($db, $device_type, $device_id);
        } else {
            $last_device = [];
        }

        $input_fail_num = empty($last_input) ? 1 : $last_input['input_fail_num'];
        $ip_fail_num = empty($last_ip) ? 1 : $last_ip['ip_fail_num'];
        $device_fail_num = empty($last_device) ? 1 : $last_device['device_fail_num'];

        return max($input_fail_num, $ip_fail_num, $device_fail_num);
    }


    /**
     * 登录失败处理
     * @param DbModel $db
     * @param $input
     * @param string $device_type
     * @param string $device_id
     * @return int 失败次数
     */
    protected function loginFail($db, $input, $device_type = '', $device_id = '')
    {
        $time = time();
        $last_input = self::getLastInputLoginHistory($db, $input);
        $last_ip = self::getLastIpLoginHistory($db, $this->external->getClientIp());
        if (!empty($device_id) && !empty($device_type)) {
            $last_device = self::getLastDeviceLoginHistory($db, $device_type, $device_id);
        } else {
            $last_device = [];
        }

        // 计算3中方式连续失败次数
        $input_fail_num = (empty($last_input) || ($last_input['create_time'] + $this->external->login_fail_interval_time < $time))
            ? 1 : $last_input['input_fail_num'] + 1;
        $ip_fail_num = (empty($last_ip) || ($last_ip['create_time'] + $this->external->login_fail_interval_time < $time))
            ? 1 : $last_ip['ip_fail_num'] + 1;
        $device_fail_num = (empty($last_device) || ($last_device['create_time'] + $this->external->login_fail_interval_time < $time))
            ? 1 : $last_device['device_fail_num'] + 1;

        $this->insertHistory($db, [
            'user_input' => $input,
            'is_success' => 0,
            'device_type' => $device_type,
            'device_id' => $device_id,
            // 如果没有过登录历史,或者上次登录已经超过10分钟,则重新计算错误次数
            'input_fail_num' => $input_fail_num,
            'ip_fail_num' => $ip_fail_num,
            'device_fail_num' => $device_fail_num,
        ]);

        return max($input_fail_num, $ip_fail_num, $device_fail_num);
    }
}
