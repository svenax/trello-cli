<?php

namespace Svenax\Trello;

use Trello\Client;
use Trello\Manager;

/**
 * Authenticate and get client or manager.
 */
class Auth
{
    const APP_KEY = '23b0f004544f7d890fc7f1a88f4fd6f8';

    private static $client = null;
    private static $manager = null;

    public static function getClient()
    {
        if (self::$client === null) {
            self::$client = new Client();
            self::$client->authenticate(
                self::APP_KEY,
                Settings::get('user_token'),
                Client::AUTH_URL_CLIENT_ID
            );
        }

        return self::$client;
    }

    public static function getManager()
    {
        if (self::$manager === null) {
            self::$manager = new Manager(self::getClient());
        }

        return self::$manager;
    }
}
