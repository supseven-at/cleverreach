<?php

declare(strict_types=1);

namespace Supseven\Cleverreach\Form\Finishers;

/**
 * This file is part of the "cleverreach" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Supseven\Cleverreach\DTO\Receiver;
use Supseven\Cleverreach\Service\ApiService;
use Supseven\Cleverreach\Service\ConfigurationService;
use TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher;
use TYPO3\CMS\Form\Domain\Finishers\Exception\FinisherException;

class CleverreachFinisher extends AbstractFinisher
{
    /**
     * @var ApiService
     */
    protected ApiService $api;

    /**
     * @var ConfigurationService
     */
    protected ConfigurationService $configurationService;

    /**
     * @var array
     */
    protected $defaultOptions = [];

    /**
     * @param ApiService $api
     * @param ConfigurationService $configurationService
     */
    public function __construct(ApiService $api, ConfigurationService $configurationService)
    {
        $this->api = $api;
        $this->configurationService = $configurationService;
    }

    /**
     * Executes this finisher
     * @see AbstractFinisher::execute()
     */
    protected function executeInternal(): void
    {
        $groupId = (int)($this->options['groupId'] ?? '') ?: $this->configurationService->getGroupId();
        $formId = (int)($this->options['formId'] ?? '') ?: $this->configurationService->getFormId();

        $email = null;
        $attributes = [];

        foreach ($this->finisherContext->getFormValues() as $identifier => $value) {
            $element = $this->finisherContext->getFormRuntime()->getFormDefinition()->getElementByIdentifier($identifier);

            if ($element !== null) {
                $properties = $element->getProperties();

                if (!empty($properties['cleverreachField'])) {
                    switch ($properties['cleverreachField']) {
                        case 'email':
                            $email = filter_var($value, FILTER_SANITIZE_EMAIL);
                            break;
                        case 'formId':
                            $formId = (int)filter_var($value, FILTER_SANITIZE_NUMBER_INT);
                            break;
                        case 'groupId':
                            $groupId = (int)filter_var($value, FILTER_SANITIZE_NUMBER_INT);
                            break;
                        default:
                            $attributes[$properties['cleverreachField']] = $value;
                    }
                }
            }
        }

        if ($this->configurationService->isTestEmail($email)) {
            return;
        }

        if (empty($groupId) || empty($formId)) {
            throw new FinisherException('Form ID or Group ID not set.');
        }

        $mode = strtolower($this->options['mode'] ?? $this->options['cleverreachmode'] ?? '');

        if ($mode && $email) {
            if ($mode === ApiService::MODE_OPTIN) {
                $receiver = Receiver::create($email, $attributes);
                $this->api->addReceiversToGroup($receiver, $groupId);
                $this->api->sendSubscribeMail($email, $formId, $groupId);
            } elseif ($mode === ApiService::MODE_OPTOUT) {
                $this->api->sendUnsubscribeMail($email, $formId, $groupId);
            }
        }
    }
}
