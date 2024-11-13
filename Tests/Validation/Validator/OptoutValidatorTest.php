<?php

declare(strict_types=1);

namespace Supseven\Cleverreach\Tests\Validation\Validator;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use Supseven\Cleverreach\DTO\RegistrationRequest;
use Supseven\Cleverreach\Service\ApiService;
use Supseven\Cleverreach\Service\ConfigurationService;
use Supseven\Cleverreach\Tests\LocalBaseTestCase;
use Supseven\Cleverreach\Validation\Validator\OptoutValidator;

/**
 * @author Georg Großberger <g.grossberger@supseven.at>
 */
class OptoutValidatorTest extends LocalBaseTestCase
{
    /**
     * @param RegistrationRequest|null $receiver
     * @param int $expectedErrorCode
     */
    #[Test]
    #[DataProvider('validateDataProvider')]
    public function testValidate(?RegistrationRequest $receiver, int $expectedErrorCode): void
    {
        $api = $this->createMock(ApiService::class);
        $api->expects(self::any())->method('isReceiverOfGroup')->willReturn(true);

        $subject = new OptoutValidator($api, $this->getConfigrationServiceStub());
        $result = $subject->validate($receiver);
        $errors = $result->getFlattenedErrors();
        $error = current(current($errors));

        self::assertSame($expectedErrorCode, $error->getCode());
    }

    public static function validateDataProvider(): array
    {
        $noEmail = new RegistrationRequest('', true, 1);
        $invalidEmail = new RegistrationRequest('abc', true, 1);
        $invalidGroup = new RegistrationRequest('abc@domain.tld', true, 3);

        return [
            'No model'      => [null, 20001],
            'Missing Email' => [$noEmail, 20002],
            'Invalid Email' => [$invalidEmail, 20002],
            'Invalid group' => [$invalidGroup, 20004],
        ];
    }

    #[Test]
    public function testValidateCorrect(): void
    {
        $api = $this->createMock(ApiService::class);
        $api->expects(self::any())->method('isReceiverOfGroup')->willReturn(true);

        $receiver = new RegistrationRequest('abc@domain.tld', true, 1);

        $subject = new OptoutValidator($api, $this->getConfigrationServiceStub());
        $result = $subject->validate($receiver);

        self::assertSame([], $result->getFlattenedErrors());
    }

    #[Test]
    public function testValidateUnregistered(): void
    {
        $api = $this->createMock(ApiService::class);
        $api->expects(self::any())->method('isReceiverOfGroup')->willReturn(false);

        $receiver = new RegistrationRequest('abc@domain.tld', true, 1);

        $subject = new OptoutValidator($api, $this->getConfigrationServiceStub());
        $result = $subject->validate($receiver);
        $errors = $result->getFlattenedErrors();
        $error = current(current($errors));

        self::assertSame(20005, $error->getCode());
    }

    private function getConfigrationServiceStub(): ConfigurationService&Stub
    {
        $config = $this->createStub(ConfigurationService::class);
        $config->method('isTestEmail')->willReturn(false);
        $config->method('getCurrentNewsletters')->willReturn([
            1 => [
                'label'  => 'FirstNewsletter',
                'formId' => '2',
            ],
        ]);

        return $config;
    }
}
