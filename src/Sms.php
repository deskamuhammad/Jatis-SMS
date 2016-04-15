<?php

namespace Otezz\Jatis;

use GuzzleHttp\Client;

class Sms
{
    const JATIS_BASE_URL = 'sms-api.jatismobile.com';

    private $username;
    private $password;
    private $protocol;
    private $baseUrl;

    public function __construct($username, $password, $useHttps = true)
    {
        $this->username = $username;
        $this->password = $password;
        $this->protocol = $useHttps ? 'https' : 'http';
        $this->baseUrl  = $this->protocol . '://' . self::JATIS_BASE_URL;
    }
    
    public function send(array $data)
    {
        $query = $this->buildParams($data);

        $response = $this->postRequest($query);

        return $this->parseResponse($response);
    }

    private function buildParams(array $data)
    {
        return [
            'userid'    => $this->username,
            'password'  => $this->password,
            'msisdn'    => $data['destination'],
            'message'   => $data['message'],
            'sender'    => $data['sender'],
            'division'  => $data['division'],
            'batchname' => $data['batchname'],
            'uploadby'  => $data['pic'],
        ];
    }

    /**
     * @param $query
     * @return \Psr\Http\Message\StreamInterface
     */
    private function postRequest(array $query)
    {
        $client = new Client(['base_uri' => $this->baseUrl]);
        $response = $client->request('GET', 'index.ashx', [
            'query' => $query
        ]);

        return $response->getBody();
    }

    private function parseResponse($response)
    {
        $message = 'Unknown response';
        parse_str((string) $response, $jatisResponse);
        $statusCodes = [
            1  => 'Success',
            2  => 'Missing Parameter',
            3  => 'Invalid User Id or Password',
            4  => 'Invalid Message',
            5  => 'Invalid MSISDN',
            6  => 'Invalid Sender',
            7  => 'Client’s IP Address is not allowed',
            8  => 'Internal Server Error',
            9  => 'Invalid division',
            20 => 'Invalid Channel',
            21 => 'Token Not Enough',
            22 => 'Token Not Available',
        ];

        if (array_key_exists($jatisResponse['Status'], $statusCodes)) {
            $message = $statusCodes[$jatisResponse['Status']];
        }

        $status = [
            'code' => (int) $jatisResponse['Status'],
            'message' => $message,
        ];

        if (array_key_exists('MessageId', $jatisResponse)) {
            $status['messageId'] = $jatisResponse['MessageId'];
        }

        return $status;
    }
}