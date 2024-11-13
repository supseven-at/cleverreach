<?php

declare(strict_types=1);

namespace Supseven\Cleverreach\Tests\Service;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Stream;
use GuzzleHttp\RequestOptions;
use PHPUnit\Framework\Attributes\Test;
use Supseven\Cleverreach\Service\RestService;
use Supseven\Cleverreach\Tests\LocalBaseTestCase;
use TYPO3\CMS\Core\Http\RequestFactory;

/**
 * @author Georg Großberger <g.grossberger@supseven.at>
 */
class RestServiceTest extends LocalBaseTestCase
{
    #[Test]
    public function testPut(): void
    {
        $method = 'PUT';
        $params = ['some' => 'data'];

        $this->testRequest($params, 'resp', 'resp', $method);
        $this->testRequest($params, '{"resp": "abc"}', ['resp' => 'abc'], $method);
    }

    #[Test]
    public function testDelete(): void
    {
        $method = 'DELETE';
        $params = ['some' => 'data'];

        $this->testRequest($params, 'resp', 'resp', $method);
        $this->testRequest($params, '{"resp": "abc"}', ['resp' => 'abc'], $method);
    }

    #[Test]
    public function testPost(): void
    {
        $method = 'POST';
        $params = ['some' => 'data'];

        $this->testRequest($params, 'resp', 'resp', $method);
        $this->testRequest($params, '{"resp": "abc"}', ['resp' => 'abc'], $method);
    }

    #[Test]
    public function testGet(): void
    {
        $method = 'GET';
        $params = ['some' => 'data'];

        $this->testRequest($params, 'resp', 'resp', $method);
        $this->testRequest($params, '{"resp": "abc"}', ['resp' => 'abc'], $method);
    }

    /**
     * @param array|null $requestParams
     * @param string $responseBody
     * @param string|array $expected
     * @param string $method
     */
    protected function testRequest(?array $requestParams, string $responseBody, string | array $expected, string $method): void
    {
        $token = 'abcd1234';
        $base = 'https://api.service.com';
        $path = '/some/api/function';

        $responseStream = new Stream(fopen('php://memory', 'wb'));
        $responseStream->write($responseBody);
        $responseStream->rewind();
        $response = new Response(200, [], $responseStream);

        $requestOptions = [];
        $requestOptions[RequestOptions::HEADERS] = ['Authorization' => 'Bearer ' . $token];

        if ($requestParams && ($method === 'POST' || $method === 'PUT')) {
            $requestOptions[RequestOptions::BODY] = json_encode($requestParams);
        }

        $expectedUrl = $base . $path;

        if ($requestParams && ($method === 'GET' || $method === 'DELETE')) {
            $expectedUrl .= '?' . http_build_query($requestParams);
        }

        $factory = $this->createMock(RequestFactory::class);
        $factory->expects(self::once())->method('request')->with(
            self::equalTo($expectedUrl),
            self::equalTo($method),
            self::equalTo($requestOptions)
        )->willReturn($response);
        $subject = new RestService($factory);
        $subject->setUrl($base);
        $subject->setBearerToken($token);
        $actual = $subject->{strtolower($method)}($path, $requestParams);

        self::assertEquals($expected, $actual);
    }
}
