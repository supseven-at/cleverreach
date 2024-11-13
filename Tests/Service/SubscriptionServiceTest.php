<?php

declare(strict_types=1);

namespace Supseven\Cleverreach\Tests\Service;

use PHPUnit\Framework\Attributes\Test;
use Supseven\Cleverreach\DTO\Subscriber;
use Supseven\Cleverreach\Service\ApiService;
use Supseven\Cleverreach\Service\ConfigurationService;
use Supseven\Cleverreach\Service\SubscriptionService;
use Supseven\Cleverreach\Tests\LocalBaseTestCase;

class SubscriptionServiceTest extends LocalBaseTestCase
{
    #[Test]
    public function testSubscribe(): void
    {
        $subscriber = new Subscriber('abc@domain.tld', 1, 2);

        $GLOBALS['TSFE'] = new \stdClass();
        $GLOBALS['TSFE']->rootLine = [['uid' => 1]];

        $config = $this->createStub(ConfigurationService::class);
        $config->method('isTestEmail')->willReturn(false);

        $api = $this->createMock(ApiService::class);
        $api->expects(self::once())->method('addReceiversToGroup');
        $api->expects(self::once())->method('sendSubscribeMail')->with(
            self::equalTo($subscriber->email),
            self::equalTo($subscriber->formId),
            self::equalTo($subscriber->groupId)
        );

        $subject = new SubscriptionService($api, $config);
        $subject->subscribe($subscriber);
    }

    #[Test]
    public function testUnsubscribe(): void
    {
        $subscriber = new Subscriber('abc@domain.tld', 1, 2);

        $GLOBALS['TSFE'] = new \stdClass();
        $GLOBALS['TSFE']->rootLine = [['uid' => 1]];

        $config = $this->createStub(ConfigurationService::class);
        $config->method('isTestEmail')->willReturn(false);

        $api = $this->createMock(ApiService::class);
        $api->expects(self::once())->method('sendUnsubscribeMail')->with(
            self::equalTo($subscriber->email),
            self::equalTo($subscriber->formId),
            self::equalTo($subscriber->groupId)
        );

        $subject = new SubscriptionService($api, $config);
        $subject->unsubscribe($subscriber);
    }
}
