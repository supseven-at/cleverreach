<?php

declare(strict_types=1);

namespace Supseven\Cleverreach\Tests\Service;

use PHPUnit\Framework\Attributes\Test;
use Supseven\Cleverreach\Service\ConfigurationService;
use Supseven\Cleverreach\Tests\LocalBaseTestCase;

/**
 * @author Georg Großberger <g.grossberger@supseven.at>
 */
class ConfigurationServiceTest extends LocalBaseTestCase
{
    #[Test]
    public function testGetFromGlobalTypoScript(): void
    {
        $restUrl = 'https://api.service.com';
        $clientId = '12345';
        $login = 'username';
        $password = 'pwd';
        $groupId = 1345;
        $formId = 123456;
        $unsubscribeMethod = 'unsubscribemethod';

        $GLOBALS['TSFE'] = new \stdClass();
        $GLOBALS['TSFE']->tmpl = new \stdClass();
        $GLOBALS['TSFE']->tmpl->setup = [
            'plugin.' => [
                'tx_cleverreach.' => [
                    'settings.' => [
                        'restUrl'           => $restUrl,
                        'clientId'          => (int)$clientId,
                        'login'             => $login,
                        'password'          => $password,
                        'groupId'           => (string)$groupId,
                        'formId'            => (string)$formId,
                        'unsubscribemethod' => $unsubscribeMethod,
                    ],
                ],
            ],
        ];
        $subject = new ConfigurationService();

        self::assertEquals($restUrl, $subject->getRestUrl());
        self::assertEquals($clientId, $subject->getClientId());
        self::assertEquals($login, $subject->getLoginName());
        self::assertEquals($password, $subject->getPassword());
        self::assertEquals($groupId, $subject->getGroupId());
        self::assertEquals($formId, $subject->getFormId());
        self::assertEquals($unsubscribeMethod, $subject->getUnsubscribeMethod());
    }
}
