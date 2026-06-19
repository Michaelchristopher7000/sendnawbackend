<?php

namespace HenryEjemuta\Vtpass\Tests;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Middleware;
use HenryEjemuta\Vtpass\Client;
use HenryEjemuta\Vtpass\VtpassException;
use PHPUnit\Framework\TestCase;

class ClientTest extends TestCase
{
    private $container = [];

    private function getMockClient(array $responses)
    {
        $mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($mock);
        
        $history = Middleware::history($this->container);
        $handlerStack->push($history);

        return new Client('api_key', 'pub_key', 'sec_key', [
            'handler' => $handlerStack,
        ]);
    }

    public function testGetServiceCategories()
    {
        $mockResponse = [
            'response_description' => '000',
            'content' => [
                ['identifier' => 'airtime', 'name' => 'Airtime Recharge']
            ]
        ];

        $client = $this->getMockClient([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $client->getServiceCategories();

        $this->assertCount(1, $this->container);
        $transaction = $this->container[0];
        $this->assertEquals('GET', $transaction['request']->getMethod());
        $this->assertStringContainsString('service-categories', (string)$transaction['request']->getUri());
        $this->assertEquals('api_key', $transaction['request']->getHeaderLine('api-key'));
        $this->assertEquals('pub_key', $transaction['request']->getHeaderLine('public-key'));

        $this->assertIsArray($result);
        $this->assertEquals('000', $result['response_description']);
    }

    public function testPurchaseAirtime()
    {
        $mockResponse = [
            'code' => '000',
            'response_description' => 'TRANSACTION SUCCESSFUL',
            'requestId' => '202202071830YUs83meikd',
            'amount' => 100,
        ];

        $client = $this->getMockClient([
            new Response(200, [], json_encode($mockResponse))
        ]);

        $result = $client->purchaseAirtime('mtn', 100, '08012345678');

        $this->assertCount(1, $this->container);
        $transaction = $this->container[0];
        $this->assertEquals('POST', $transaction['request']->getMethod());
        $this->assertStringContainsString('pay', (string)$transaction['request']->getUri());
        
        $body = json_decode((string)$transaction['request']->getBody(), true);
        $this->assertEquals('mtn', $body['serviceID']);
        $this->assertEquals(100, $body['amount']);
        $this->assertEquals('08012345678', $body['phone']);
        $this->assertArrayHasKey('request_id', $body);
        
        // Check Auth Headers for POST
        $this->assertEquals('api_key', $transaction['request']->getHeaderLine('api-key'));
        $this->assertEquals('sec_key', $transaction['request']->getHeaderLine('secret-key'));
    }

    public function testErrorHandling()
    {
        $this->expectException(VtpassException::class);
        $this->expectExceptionMessage('API Request Failed: Invalid credentials');

        $client = $this->getMockClient([
            new Response(401, [], json_encode(['code' => '011', 'response_description' => 'Invalid credentials']))
        ]);

        $client->getServiceCategories();
    }
}
