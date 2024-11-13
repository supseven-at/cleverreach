<?php

declare(strict_types=1);

namespace Supseven\Cleverreach\Tests\Service;

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use Supseven\Cleverreach\DTO\Receiver;
use Supseven\Cleverreach\Service\ApiService;
use Supseven\Cleverreach\Service\RestService;
use Supseven\Cleverreach\Tests\LocalBaseTestCase;

/**
 * @author Georg Großberger <g.grossberger@supseven.at>
 */
class ApiServiceTest extends LocalBaseTestCase
{
    #[Test]
    public function testConnect(): void
    {
        $params = [
            'client_id' => 123,
            'login'     => 'abc',
            'password'  => 'def',
        ];

        $token = 'abcdef';

        $rest = $this->createMock(RestService::class);
        $rest->expects(self::once())->method('setUrl')->with(self::equalTo('https://api.cleverreach.com'));
        $rest->expects(self::once())->method('post')->with(self::equalTo('/login'), self::equalTo($params))->willReturn($token);
        $rest->expects(self::once())->method('setBearerToken')->with(self::equalTo($token));

        $subject = new ApiService($this->getConfiguration(), $rest, new NullLogger());
        $subject->connect();

        // Calling again must not do anything
        $subject->connect();
    }

    /**
     * @param $receivers
     * @param $groupId
     */
    #[Test]
    #[DataProvider('receiversProvider')]
    public function testAddReceiversToGroup($receivers, $groupId): void
    {
        $groupId ??= 123;

        $expectedList = [];

        if ($receivers instanceof Receiver) {
            $expectedList[] = $receivers->toArray();
        }

        if (is_array($receivers)) {
            $expectedList = array_map(static fn (Receiver $r): array => $r->toArray(), $receivers);
        }

        if (is_string($receivers)) {
            $expectedList[] = (Receiver::create($receivers))->toArray();
        }

        $result = [['status' => 'insert success']];

        $rest = $this->createMock(RestService::class);
        $rest->expects(self::once())->method('post')->with(
            self::equalTo('/groups.json/' . $groupId . '/receivers/insert'),
            self::equalTo($expectedList)
        )->willReturn($result);

        $subject = new ApiService($this->getConfiguration(), $rest, new NullLogger());
        $subject->disableConnect();

        self::assertTrue($subject->addReceiversToGroup($receivers, $groupId));
    }

    public static function receiversProvider(): \Generator
    {
        $receiver1 = Receiver::create('example@domain.com');

        yield 'One receiver object, no group ID' => [$receiver1, null];
        yield 'One receiver object, with group ID' => [$receiver1, 789];

        $receiver2 = Receiver::create('another-example@domain.com');

        yield 'Two receiver objects, no group ID' => [[$receiver1, $receiver2], null];
        yield 'One receiver string, with group ID' => ['someone@domain.com', 789];
    }

    #[Test]
    public function testRemoveReceiversFromGroup(): void
    {
        $rest = $this->createMock(RestService::class);
        $rest->expects(self::once())->method('delete')->with('/groups.json/123/receivers/456');

        $subject = new ApiService($this->getConfiguration(), $rest, new NullLogger());
        $subject->disableConnect();

        $subject->removeReceiversFromGroup('456');
    }

    #[Test]
    public function testActivateReceiversInGroup(): void
    {
        $rest = $this->createMock(RestService::class);
        $rest->expects(self::once())->method('put')->with('/groups.json/123/receivers/456/setactive');

        $subject = new ApiService($this->getConfiguration(), $rest, new NullLogger());
        $subject->disableConnect();

        $subject->activateReceiversInGroup('456');
    }

    #[Test]
    public function testDisableReceiversInGroup(): void
    {
        $rest = $this->createMock(RestService::class);
        $rest->expects(self::once())->method('put')->with('/groups.json/123/receivers/456/setinactive');

        $subject = new ApiService($this->getConfiguration(), $rest, new NullLogger());
        $subject->disableConnect();

        $subject->disableReceiversInGroup('456');
    }

    #[Test]
    public function testGetGroup(): void
    {
        $expected = ['name' => 'abc'];
        $rest = $this->createMock(RestService::class);
        $rest->expects(self::once())->method('get')->with(
            self::equalTo('/groups.json/456')
        )->willReturn($expected);

        $subject = new ApiService($this->getConfiguration(), $rest, new NullLogger());
        $subject->disableConnect();

        $actual = $subject->getGroup(456);
        self::assertEquals($expected, $actual);
    }

    #[Test]
    public function testIsReceiverOfGroupFound(): void
    {
        $rest = $this->createMock(RestService::class);
        $rest->expects(self::once())->method('get')->with(
            self::equalTo('/groups.json/456/receivers/123')
        )->willReturn(['some' => 'not used info']);

        $subject = new ApiService($this->getConfiguration(), $rest, new NullLogger());
        $subject->disableConnect();

        $result = $subject->isReceiverOfGroup(123, 456);
        self::assertTrue($result);
    }

    #[Test]
    public function testIsReceiverOfGroupNotFound(): void
    {
        $exception = new BadResponseException(
            'not found',
            $this->createStub(Request::class),
            $this->createStub(Response::class)
        );
        $rest = $this->createMock(RestService::class);
        $rest->expects(self::once())->method('get')->with(
            self::equalTo('/groups.json/456/receivers/123')
        )->willThrowException($exception);

        $subject = new ApiService($this->getConfiguration(), $rest, new NullLogger());
        $subject->disableConnect();

        $result = $subject->isReceiverOfGroup(123, 456);
        self::assertFalse($result);
    }

    #[Test]
    public function testGetReceiverOfGroupFound(): void
    {
        $data = [
            'email'             => 'someone@domain.tld',
            'registered'        => 123456,
            'activated'         => 123456,
            'deactivated'       => 123456,
            'attributes'        => [],
            'global_attributes' => [],
        ];
        $rest = $this->createMock(RestService::class);
        $rest->expects(self::once())->method('get')->with(
            self::equalTo('/groups.json/456/receivers/123')
        )->willReturn($data);

        $expected = Receiver::make($data);

        $subject = new ApiService($this->getConfiguration(), $rest, new NullLogger());
        $subject->disableConnect();

        $actual = $subject->getReceiverOfGroup(123, 456);
        self::assertEquals($expected, $actual);
    }

    #[Test]
    public function testGetReceiverOfGroupNotFound(): void
    {
        $exception = new BadResponseException(
            'not found',
            $this->createStub(Request::class),
            $this->createStub(Response::class)
        );
        $rest = $this->createMock(RestService::class);
        $rest->expects(self::once())->method('get')->with(
            self::equalTo('/groups.json/456/receivers/123')
        )->willThrowException($exception);

        $subject = new ApiService($this->getConfiguration(), $rest, new NullLogger());
        $subject->disableConnect();

        $result = $subject->getReceiverOfGroup(123, 456);
        self::assertNull($result);
    }

    #[Test]
    public function testIsReceiverOfGroupAndActiveFound(): void
    {
        $data = [
            'email'             => 'someone@domain.tld',
            'registered'        => 123456,
            'activated'         => 123456,
            'deactivated'       => 0,
            'attributes'        => [],
            'global_attributes' => [],
        ];
        $rest = $this->createMock(RestService::class);
        $rest->expects(self::once())->method('get')->with(
            self::equalTo('/groups.json/456/receivers/123')
        )->willReturn($data);

        $subject = new ApiService($this->getConfiguration(), $rest, new NullLogger());
        $subject->disableConnect();

        $result = $subject->isReceiverOfGroupAndActive(123, 456);
        self::assertTrue($result);
    }

    #[Test]
    public function testIsReceiverOfGroupAndActiveNotFound(): void
    {
        $data = [
            'email'             => 'someone@domain.tld',
            'registered'        => 123456,
            'activated'         => 123456,
            'deactivated'       => 123456,
            'attributes'        => [],
            'global_attributes' => [],
        ];
        $rest = $this->createMock(RestService::class);
        $rest->expects(self::once())->method('get')->with(
            self::equalTo('/groups.json/456/receivers/123')
        )->willReturn($data);

        $subject = new ApiService($this->getConfiguration(), $rest, new NullLogger());
        $subject->disableConnect();

        $result = $subject->isReceiverOfGroupAndActive(123, 456);
        self::assertFalse($result);
    }

    #[Test]
    public function testSendSubscribeMail(): void
    {
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';
        $_SERVER['HTTP_USER_AGENT'] = 'Browser/1.0';
        $_SERVER['HTTP_REFERER'] = 'https://www.site.tld/register-page';
        $email = 'someone@domain.tld';
        $groupId = 123;
        $formId = 789;

        $rest = $this->createMock(RestService::class);
        $rest->expects(self::once())->method('post')->with(
            self::equalTo('/forms.json/' . $formId . '/send/activate'),
            self::equalTo([
                'email'     => $email,
                'groups_id' => $groupId,
                'doidata'   => [
                    'user_ip'    => $_SERVER['REMOTE_ADDR'],
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                    'referer'    => $_SERVER['HTTP_REFERER'],
                ],
            ])
        );

        $subject = new ApiService($this->getConfiguration(), $rest, new NullLogger());
        $subject->disableConnect();
        $subject->sendSubscribeMail($email, $formId, $groupId);
    }

    #[Test]
    public function testSendSubscribeMailDoesNotFailOnEmptyServerVars(): void
    {
        $email = 'someone@domain.tld';
        $groupId = 123;
        $formId = 789;

        $rest = $this->createMock(RestService::class);
        $rest->expects(self::once())->method('post')->with(
            self::equalTo('/forms.json/' . $formId . '/send/activate'),
            self::equalTo([
                'email'     => $email,
                'groups_id' => $groupId,
                'doidata'   => [
                    'user_ip'    => '',
                    'user_agent' => '',
                    'referer'    => '',
                ],
            ])
        );

        $subject = new ApiService($this->getConfiguration(), $rest, new NullLogger());
        $subject->disableConnect();
        $subject->sendSubscribeMail($email, $formId, $groupId);
    }

    #[Test]
    public function testSendUnsubscribeMail(): void
    {
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';
        $_SERVER['HTTP_USER_AGENT'] = 'Browser/1.0';
        $_SERVER['HTTP_REFERER'] = 'https://www.site.tld/register-page';
        $email = 'someone@domain.tld';
        $groupId = 123;
        $formId = 789;

        $rest = $this->createMock(RestService::class);
        $rest->expects(self::once())->method('post')->with(
            self::equalTo('/forms.json/' . $formId . '/send/deactivate'),
            self::equalTo([
                'email'     => $email,
                'groups_id' => $groupId,
                'doidata'   => [
                    'user_ip'    => $_SERVER['REMOTE_ADDR'],
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                    'referer'    => $_SERVER['HTTP_REFERER'],
                ],
            ])
        );

        $subject = new ApiService($this->getConfiguration(), $rest, new NullLogger());
        $subject->disableConnect();
        $subject->sendUnsubscribeMail($email, $formId, $groupId);
    }

    #[Test]
    public function testSendUnsubscribeMailDoesNotFailOnEmptyServerVars(): void
    {
        $email = 'someone@domain.tld';
        $groupId = 123;
        $formId = 789;

        $rest = $this->createMock(RestService::class);
        $rest->expects(self::once())->method('post')->with(
            self::equalTo('/forms.json/' . $formId . '/send/deactivate'),
            self::equalTo([
                'email'     => $email,
                'groups_id' => $groupId,
                'doidata'   => [
                    'user_ip'    => '',
                    'user_agent' => '',
                    'referer'    => '',
                ],
            ])
        );

        $subject = new ApiService($this->getConfiguration(), $rest, new NullLogger());
        $subject->disableConnect();
        $subject->sendUnsubscribeMail($email, $formId, $groupId);
    }

    #[Test]
    public function testSetAttributeOfReceiver(): void
    {
        $email = 'someone@domain.tld';
        $attribute = 'name';
        $value = 'Some Body';

        $rest = $this->createMock(RestService::class);
        $rest->expects(self::once())->method('put')->with(
            self::equalTo('/receivers.json/' . $email . '/attributes/' . $attribute),
            self::equalTo([
                'value' => $value,
            ])
        );

        $subject = new ApiService($this->getConfiguration(), $rest, new NullLogger());
        $subject->disableConnect();
        $subject->setAttributeOfReceiver($email, $attribute, $value);
    }

    #[Test]
    public function testDeleteReceiver(): void
    {
        $email = 'someone@domain.tld';
        $groupId = 123;

        $rest = $this->createMock(RestService::class);
        $rest->expects(self::once())->method('delete')->with(
            self::equalTo('/receivers.json/' . $email),
            self::equalTo([
                'group_id' => $groupId,
            ])
        );

        $subject = new ApiService($this->getConfiguration(), $rest, new NullLogger());
        $subject->disableConnect();
        $subject->deleteReceiver($email, $groupId);
    }
}
