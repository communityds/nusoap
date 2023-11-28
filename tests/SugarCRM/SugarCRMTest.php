<?php

namespace CommunityDS\NuSOAP\Tests\SugarCRM;

use CommunityDS\NuSOAP\Tests\MockClient;
use CommunityDS\NuSOAP\Tests\MockServer;
use CommunityDS\NuSOAP\Tests\MockTransportHttp;
use CommunityDS\NuSOAP\Tests\TestCase;

class SugarCRMTest extends TestCase
{
    protected function soapServer()
    {
        $namespace = $this->getSugarNamespace();

        error_reporting(E_ALL);

        MockServer::mockServerVars();

        $server = new MockServer();
        $server->faultHttpCode = 200;
        $server->title = 'SugarCRMTest';
        $server->version = '1.0.0';
        $server->configureWSDL('sugarsoap', $namespace, 'https://communityds.com.au/soap.php');
        $server->register_class(ServerImpl::class);
        ServerImpl::setServer($server);

        $server->register(
            'login',
            ['user_auth' => 'tns:user_auth', 'application_name' => 'xsd:string', 'name_value_list' => 'tns:name_value_list'],
            ['return' => 'tns:entry_value'],
            $namespace
        );
        $server->register(
            'set_entry',
            ['session' => 'xsd:string', 'module_name' => 'xsd:string',  'name_value_list' => 'tns:name_value_list'],
            ['return' => 'tns:set_entry_result'],
            $namespace
        );

        $server->wsdl->addComplexType(
            'entry_value',
            'complexType',
            'struct',
            'all',
            '',
            [
                'id' => ['name' => 'id', 'type' => 'xsd:string'],
                'module_name' => ['name' => 'module_name', 'type' => 'xsd:string'],
                'name_value_list' => ['name' => 'name_value_list', 'type' => 'tns:name_value_list'],
            ]
        );
        $server->wsdl->addComplexType(
            'name_value',
            'complexType',
            'struct',
            'all',
            '',
            [
                'name' => ['name' => 'name', 'type' => 'xsd:string'],
                'value' => ['name' => 'value', 'type' => 'xsd:string'],
            ]
        );
        $server->wsdl->addComplexType(
            'name_value_list',
            'complexType',
            'array',
            '',
            'SOAP-ENC:Array',
            [],
            [
                ['ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'tns:name_value[]'],
            ],
            'tns:name_value'
        );
        $server->wsdl->addComplexType(
            'set_entry_result',
            'complexType',
            'struct',
            'all',
            '',
            [
                'id' => ['name' => 'id', 'type' => 'xsd:string'],
            ]
        );
        $server->wsdl->addComplexType(
            'user_auth',
            'complexType',
            'struct',
            'all',
            '',
            [
                'user_name' => ['name' => 'user_name', 'type' => 'xsd:string'],
                'password' => ['name' => 'password', 'type' => 'xsd:string'],
            ]
        );

        return $server;
    }

    protected function getWsdlPath()
    {
        return __DIR__ . '/data/wsdl.xml';
    }

    protected function soapClient($server = null)
    {
        if ($server == null) {
            $server = $this->soapServer();
        }

        $client = new MockClient(
            $this->getWsdlPath(),
            'wsdl'
        );
        $client->persistentConnection = MockTransportHttp::createMock($server);
        return $client;
    }

    public function testWsdl()
    {
        $updateExpected = getenv('UPDATE_EXPECTED') == 'true';

        $server = $this->soapServer();
        $output = $server->wsdl->serialize();
        if ($updateExpected) {
            file_put_contents($this->getWsdlPath(), $output);
        }
        $this->assertStringEqualsFile($this->getWsdlPath(), $output);
    }

    public function testLogin()
    {
        $client = $this->soapClient();

        $result = $client->call(
            'login',
            [
                'user_auth' => [
                    'user_name' => 'test',
                    'password' => 'password',
                ],
                'application_name' => __CLASS__,
            ]
        );
        $this->assertNotSoapFault($client);
        $this->assertEquals(ServerImpl::getLoginResult('test'), $result);
    }

    /**
     * @group dev
     */
    public function testFault()
    {
        $client = $this->soapClient();

        $client->call(
            'login',
            [
                'user_auth' => [
                    'user_name' => 'test',
                    'password' => 'invalid',
                ],
                'application_name' => __CLASS__,
            ]
        );
        $this->assertSoapFault($client, 'login_fail', 'Invalid authentication details');

        $client->call(
            'login',
            [
                'user_auth' => [
                    'user_name' => 'test',
                    'password' => 'extra',
                ],
                'application_name' => __CLASS__,
            ]
        );
        $this->assertSoapFault($client, 'login_extra', 'Extra details', '{"password":"extra"}');
    }

    public function testSetEntry()
    {
        $client = $this->soapClient();

        $result = $client->call(
            'set_entry',
            [
                'session' => ServerImpl::getSessionId(),
                'module_name' => 'Contacts',
                'name_value_list' => [
                    ['name' => 'id', 'value' => ServerImpl::getUserId()],
                    ['name' => 'first_name', 'value' => 'Jane'],
                    ['name' => 'last_name', 'value' => 'Example'],
                ],
            ]
        );
        $this->assertNotSoapFault($client);
        $this->assertEquals(
            ['id' => ServerImpl::getUserId()],
            $result
        );
    }
}
