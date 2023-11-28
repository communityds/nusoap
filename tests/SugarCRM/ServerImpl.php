<?php

namespace CommunityDS\NuSOAP\Tests\SugarCRM;

abstract class ServerImpl
{
    /**
     * @var Server
     */
    private static $server;

    protected static function setError($type, $message, $detail = '')
    {
        self::$server->fault(
            $type,
            $message,
            '',
            $detail
        );
    }

    public static function login($user_auth, $application, $name_value_list)
    {
        $userName = isset($user_auth['user_name']) ? $user_auth['user_name'] : '';
        $password = isset($user_auth['password']) ? $user_auth['password'] : '';

        switch ($application) {
            case SugarCRMTest::class:
                switch ($userName) {
                    case 'test':
                        switch ($password) {
                            case 'password':
                                return static::getLoginResult($userName);
                            case 'extra':
                                self::setError('login_extra', 'Extra details', json_encode(['password' => $password]));
                                return;
                        }
                        break;
                }
                break;
        }

        self::setError('login_fail', 'Invalid authentication details');
    }

    public static function set_entry($session, $module, $name_values)
    {
        if ($session != self::getSessionId()) {
            self::setError('invalid_session', 'Session invalid');
            return;
        }

        if ($module != 'Contacts') {
            self::setError('invalid_module', "Unknown module: {$module}");
            return;
        }

        $values = [];
        foreach ($name_values as $item) {
            $values[$item['name']] = $item['value'];
        }

        if (isset($values['id']) && $values['id'] != self::getUserId()) {
            self::setError('invalid_record', "Unknown record: {$values['id']}");
            return;
        }

        return [
            'id' => self::getUserId(),
        ];
    }

    /**
     * @param Server $server
     */
    public static function setServer($server)
    {
        self::$server = $server;
    }

    /**
     * @param string $userName
     *
     * @return array
     */
    public static function getLoginResult($userName)
    {
        return [
            'id' => static::getSessionId(),
            'module_name' => 'Users',
            'name_value_list' => [
                ['name' => 'user_id', 'value' => static::getUserId()],
                ['name' => 'user_name', 'value' => $userName],
            ],
        ];
    }

    /**
     * @return string
     */
    public static function getSessionId()
    {
        return '1b745f3e036e3fc';
    }

    /**
     * @return string
     */
    public static function getUserId()
    {
        return '32ca7ada-5e2a-93c1-599e-5d36466c50fd';
    }
}
