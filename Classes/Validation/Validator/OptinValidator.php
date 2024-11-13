<?php

declare(strict_types=1);

namespace Supseven\Cleverreach\Validation\Validator;

use Supseven\Cleverreach\DTO\RegistrationRequest;
use Supseven\Cleverreach\Service\ApiService;
use Supseven\Cleverreach\Service\ConfigurationService;
use TYPO3\CMS\Extbase\Validation\Error;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

/**
 * Validate data of a reciver DTO for opt in process
 *
 * @author Georg Großberger <g.grossberger@supseven.at>
 */
class OptinValidator extends AbstractValidator
{
    protected $acceptsEmptyValues = false;

    public function __construct(
        private readonly ApiService $apiService,
        private readonly ConfigurationService $configurationService
    ) {
    }

    protected function isValid($value): void
    {
        if (!$value instanceof RegistrationRequest) {
            $this->addError('Receiver missing', 10001);

            return;
        }

        $email = filter_var($value->email, FILTER_SANITIZE_EMAIL);

        if (!$email || $email !== $value->email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->result->forProperty('email')->addError(new Error('Email invalid', 10002));
        }

        if ($value->agreed !== true) {
            $this->result->forProperty('agreed')->addError(new Error('Not accepted', 10003));
        }

        $newsletter = $this->configurationService->getNewsletterForGroup($value->groupId);

        if (!is_array($newsletter) && empty($newsletter['formId'])) {
            $this->result->forProperty('groupId')->addError(new Error('unknown newsletter', 10004));
        }

        if ($this->configurationService->isTestEmail($email)) {
            return;
        }

        $receiver = $this->apiService->getReceiverOfGroup($value->email, $value->groupId);

        if ($receiver && $receiver->isActive()) {
            $this->addError('already registered', 10005);
        }
    }
}
