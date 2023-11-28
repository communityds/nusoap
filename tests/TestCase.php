<?php

namespace CommunityDS\NuSOAP\Tests;

use nusoap_client;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * Indicates if expected values should be updated as part of the test
     * by the `UPDATE_EXPECTED` environment variable.
     *
     * To use the environment variable:
     *
     * ```
     * UPDATE_EXPECTED=true vendor/bin/phpunit
     * ```
     *
     * @return boolean
     */
    protected function updateExpected()
    {
        return getenv('UPDATE_EXPECTED') == 'true';
    }

    /**
     * @return string
     */
    protected function getSugarNamespace()
    {
        return 'http://www.sugarcrm.com/sugarcrm';
    }

    /**
     * Ensures there is no fault in the client.
     *
     * @param nusoap_client $client
     */
    protected function assertNotSoapFault($client)
    {
        $this->assertEquals(false, $client->fault, 'Expecting no fault');
    }

    /**
     * Ensures there is a fault in the client.
     *
     * @param nusoap_client $client
     * @param string $code
     * @param string $message
     * @param mixed $detail
     */
    protected function assertSoapFault($client, $code, $message, $detail = '')
    {
        $this->assertEquals(true, $client->fault, 'Expecting a fault');
        $this->assertEquals($code, $client->faultcode, 'Fault code is not as expected');
        $this->assertEquals($message, $client->faultstring, 'Fault string is not as expected');
        $this->assertEquals($detail, $client->faultdetail, 'Fault detail is not as expected');
    }
}
