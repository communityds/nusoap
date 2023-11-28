<?php

namespace CommunityDS\NuSOAP\Tests;

use nusoap_server;
use soap_transport_http;

/**
 * Exploits the socket implementation of the HTTP transport by using
 * in-memory file pointers rather that sockets to communicate between
 * the client and a server instance.
 */
class MockTransportHttp extends soap_transport_http
{
    /**
     * @var nusoap_server
     */
    private $mockServer;

    protected function io_method()
    {
        return 'socket';
    }

    protected function connect($connection_timeout = 0, $response_timeout = 30)
    {
        if (is_resource($this->fp)) {
            @fclose($this->fp);
        }

        $this->fp = fopen('php://memory', 'r+');

        return true;
    }

    protected function sendRequest($data, $cookies = null)
    {
        $result = parent::sendRequest($data, $cookies);
        if ($result == false) {
            return $result;
        }

        // Ensure server does not output content
        if (!defined('NUSOAP_TEST')) {
            define('NUSOAP_TEST', true);
        }

        if (is_resource($this->fp)) {
            @fclose($this->fp);
        }

        // When running via the command line, the server will attempt to get
        // the headers from the $_SERVER object.
        $prevServer = $_SERVER;
        $_SERVER = [];
        $_SERVER['REQUEST_METHOD'] = 'POST';
        foreach ($this->outgoing_headers as $k => $v) {
            $_SERVER['HTTP_' . strtoupper(str_replace('-', '_', $k))] = $v;
        }

        $this->mockServer->service($data);

        $_SERVER = $prevServer;

        // The getResponse() function reads from the file pointer
        // so output the response (that includes the headers) to
        // an in-memory file
        $this->fp = fopen('php://memory', 'r+');
        fwrite($this->fp, $this->mockServer->response);
        fseek($this->fp, 0);

        return true;
    }

    /**
     * Creates a new transport instance for the provided server.
     *
     * @param nusoap_server $server
     *
     * @return static
     */
    public static function createMock($server)
    {
        $obj = new MockTransportHttp($server->wsdl->wsdl);
        $obj->mockServer = $server;
        return $obj;
    }
}
