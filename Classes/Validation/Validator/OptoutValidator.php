<?php

declare(strict_types=1);

namespace Supseven\Cleverreach\Validation\Validator;

use Supseven\Cleverreach\DTO\RegistrationRequest;
use Supseven\Cleverreach\Service\ApiService;
use Supseven\Cleverreach\Service\ConfigurationService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Validation\Error;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

/**
 * Validate data of a reciver DTO for opt out process
 *
 * @author Georg Großberger <g.grossberger@supseven.at>
 */
class OptoutValidator extends AbstractValidator
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
            $this->addError('Receiver missing', 20001);

            return;
        }

        $email = filter_var($value->email, FILTER_SANITIZE_EMAIL);

        if (!$email || $email !== $value->email || !GeneralUtility::validEmail($email)) {
            $this->result->forProperty('email')->addError(new Error('Email invalid', 20002));
        }

        $newsletter = $this->configurationService->getNewsletterForGroup($value->groupId);

        if (!is_array($newsletter) || empty($newsletter['formId'])) {
            $this->result->forProperty('groupId')->addError(new Error('unknown newsletter', 20004));
        }

        if ($this->configurationService->isTestEmail($email)) {
            return;
        }

        if (!$this->apiService->isReceiverOfGroup($value->email, $value->groupId)) {
            $this->addError('not subscribed', 20005);
        }
    }
}
