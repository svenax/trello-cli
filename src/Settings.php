<?php

namespace Svenax\Trello;

/**
 * Manage settings stored in ~/.trello as JSON.
 */
class Settings
{
    /** @var array */
    private static $data;

    public static function get($key, $def = null)
    {
        @list($sec, $key) = explode('/', $key);

        $data = self::data();
        $res = $key === null ? @$data[$sec] : @$data[$sec][$key];

        return $res ?: $def;
    }

    public static function set($key, $val)
    {
        @list($sec, $key) = explode('/', $key);

        $data = self::data();
        if ($key === null) {
            $data[$sec] = $val;
        } else {
            $data[$sec][$key] = $val;
        }

        self::save($data);
    }

    // Internal --------------------------------------------------------------

    private static function data()
    {
        if (self::$data === null) {
            self::$data = json_decode(file_get_contents($_SERVER['HOME'] . '/.trello'), true);
        }

        return self::$data;
    }

    private static function save($data)
    {
        file_put_contents($_SERVER['HOME'] . '/.trello', json_encode($data, JSON_PRETTY_PRINT));
    }
}
