<?php

declare(strict_types=1);

namespace Supseven\Cleverreach\Tests\Form\Finishers;

use PHPUnit\Framework\Attributes\Test;
use Supseven\Cleverreach\DTO\Receiver;
use Supseven\Cleverreach\Form\Finishers\CleverreachFinisher;
use Supseven\Cleverreach\Service\ApiService;
use Supseven\Cleverreach\Tests\LocalBaseTestCase;
use TYPO3\CMS\Form\Domain\Finishers\FinisherContext;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\FormElements\UnknownFormElement;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;

/**
 * @author Georg Großberger <g.grossberger@supseven.at>
 */
class CleverreachFinisherTest extends LocalBaseTestCase
{
    #[Test]
    public function testOptIn(): void
    {
        $groupId = '123';
        $formId = '456';
        $email = 'sombody@domain.tld';
        $name = 'Some Body';
        $formValues = [
            'groupId' => $groupId,
            'formId'  => $formId,
            'name'    => $name,
            'email'   => $email,
        ];

        $returnMap = [];

        foreach (array_keys($formValues) as $k) {
            $element = $this->createStub(UnknownFormElement::class);
            $element->method('getProperties')->willReturn(['cleverreachField' => $k]);
            $returnMap[] = [$k, $element];
        }

        $options = ['mode' => ApiService::MODE_OPTIN];

        $formDef = $this->createStub(FormDefinition::class);
        $formDef->method('getElementByIdentifier')->willReturnMap($returnMap);

        $runtime = $this->createStub(FormRuntime::class);
        $runtime->method('getFormDefinition')->willReturn($formDef);

        $ctx = $this->createStub(FinisherContext::class);
        $ctx->method('getFormValues')->willReturn($formValues);
        $ctx->method('getFormRuntime')->willReturn($runtime);

        $receiver = Receiver::create($email, ['name' => $name]);

        $api = $this->createMock(ApiService::class);
        $api->expects(self::once())->method('addReceiversToGroup')->with(
            self::equalTo($receiver),
            self::equalTo((int)$groupId)
        );
        $api->expects(self::once())->method('sendSubscribeMail')->with(
            self::equalTo($email),
            self::equalTo((int)$formId),
            self::equalTo((int)$groupId)
        );
        $api->expects(self::never())->method('sendUnsubscribeMail');

        $subject = new CleverreachFinisher($api, $this->getConfiguration());
        $subject->setOptions($options);
        $subject->execute($ctx);
    }

    #[Test]
    public function testOptOut(): void
    {
        $groupId = '123';
        $formId = '456';
        $email = 'sombody@domain.tld';
        $name = 'Some Body';
        $formValues = [
            'groupId' => $groupId,
            'formId'  => $formId,
            'name'    => $name,
            'email'   => $email,
        ];

        $returnMap = [];

        foreach (array_keys($formValues) as $k) {
            $element = $this->createStub(UnknownFormElement::class);
            $element->method('getProperties')->willReturn(['cleverreachField' => $k]);
            $returnMap[] = [$k, $element];
        }

        $options = ['mode' => ApiService::MODE_OPTOUT];

        $formDef = $this->createStub(FormDefinition::class);
        $formDef->method('getElementByIdentifier')->willReturnMap($returnMap);

        $runtime = $this->createStub(FormRuntime::class);
        $runtime->method('getFormDefinition')->willReturn($formDef);

        $ctx = $this->createStub(FinisherContext::class);
        $ctx->method('getFormValues')->willReturn($formValues);
        $ctx->method('getFormRuntime')->willReturn($runtime);

        $api = $this->createMock(ApiService::class);
        $api->expects(self::never())->method('addReceiversToGroup');
        $api->expects(self::never())->method('sendSubscribeMail');
        $api->expects(self::once())->method('sendUnsubscribeMail')->with(
            self::equalTo($email),
            self::equalTo((int)$formId),
            self::equalTo((int)$groupId)
        );

        $subject = new CleverreachFinisher($api, $this->getConfiguration());
        $subject->setOptions($options);
        $subject->execute($ctx);
    }
}
