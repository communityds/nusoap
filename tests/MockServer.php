<?php

namespace CommunityDS\NuSOAP\Tests;

use nusoap_server;

class MockServer extends nusoap_server
{
    public function service($data)
    {
        // Reset internal states
        $this->fault = false;

        return parent::service($data);
    }

    /**
     * Adds any missing $_SERVER values expected by the server.
     */
    public static function mockServerVars()
    {
        if (!isset($_SERVER['SERVER_NAME'])) {
            $_SERVER['SERVER_NAME'] = 'communityds.com.au';
        }
        if (!isset($_SERVER['SERVER_PORT'])) {
            $_SERVER['SERVER_PORT'] = 80;
        }
        $_SERVER['PHP_SELF'] = '/soap.php';
    }
}
