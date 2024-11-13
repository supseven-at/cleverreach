<?php

declare(strict_types=1);

namespace Supseven\Cleverreach\Service;

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;
use Supseven\Cleverreach\DTO\Receiver;
use TYPO3\CMS\Core\SingletonInterface;

class ApiService implements SingletonInterface
{
    public const string MODE_OPTIN = 'optin';
    public const string MODE_OPTOUT = 'optout';

    private bool $connected = false;

    /**
     * @param ConfigurationService $configurationService
     * @param RestService $rest
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly ConfigurationService $configurationService,
        private readonly RestService $rest,
        private readonly LoggerInterface $logger
    ) {
    }

    public function disableConnect(): void
    {
        $this->connected = true;
    }

    public function connect(): void
    {
        if ($this->connected) {
            return;
        }

        $this->rest->setUrl($this->configurationService->getRestUrl());

        try {
            //skip this part if you have an OAuth access token
            $token = $this->rest->post(
                '/login',
                [
                    'client_id' => $this->configurationService->getClientId(),
                    'login'     => $this->configurationService->getLoginName(),
                    'password'  => $this->configurationService->getPassword(),
                ]
            );
            $this->rest->setBearerToken($token);
            $this->connected = true;
        } catch (GuzzleException $ex) {
            $this->logger->alert('cannot login at cleverreach: ' . $ex->getMessage(), [$ex]);
        }
    }

    /**
     * Inserts receiver to a list. Ignores, if already in list.
     *
     * @param mixed $receivers
     * @param int|null $groupId
     * @return bool
     */
    public function addReceiversToGroup(Receiver|array|string $receivers, ?int $groupId = null): bool
    {
        $this->connect();
        $groupId ??= $this->configurationService->getGroupId();
        $receiversList = match (true) {
            $receivers instanceof Receiver => [$receivers->toArray()],
            is_array($receivers)           => array_map(
                static fn (Receiver $r) => $r->toArray(),
                (array)array_filter($receivers, static fn ($r) => $r instanceof Receiver)
            ),
            default => [(Receiver::create((string)$receivers))->toArray()],
        };

        try {
            $return = $this->rest->post(
                '/groups.json/' . $groupId . '/receivers/insert',
                $receiversList
            );

            if (is_array($return)) {
                $status = $return[0]['status'] ?? '';

                return $status === 'insert success' || str_starts_with($status, 'duplicate address');
            }
        } catch (GuzzleException $ex) {
            $this->logger->alert('cannot add receivers to group: ' . $ex->getMessage(), [$ex, $receiversList]);
        }

        return false;
    }

    /**
     * @param int|string $receiverId
     * @param int|null $groupId
     */
    public function removeReceiversFromGroup(int|string $receiverId, ?int $groupId = null): void
    {
        $this->connect();
        $groupId ??= $this->configurationService->getGroupId();

        try {
            $this->rest->delete('/groups.json/' . $groupId . '/receivers/' . $receiverId);
        } catch (GuzzleException $ex) {
            $this->logger->alert('cannot remove receivers from group: ' . $ex->getMessage(), [$ex, $receiverId]);
        }
    }

    /**
     * Sets receiver state to inactive
     *
     * @param int|string $receiverId
     * @param int|null $groupId
     */
    public function disableReceiversInGroup(int|string $receiverId, ?int $groupId = null): void
    {
        $this->connect();
        $groupId ??= $this->configurationService->getGroupId();

        try {
            $this->rest->put('/groups.json/' . $groupId . '/receivers/' . $receiverId . '/setinactive');
        } catch (GuzzleException $ex) {
            $this->logger->alert('cannot disable receivers in group: ' . $ex->getMessage(), [$ex, $receiverId]);
        }
    }

    /**
     * Sets receiver state to inactive
     *
     * @param int|string $receiverId
     * @param int|null $groupId
     */
    public function activateReceiversInGroup(int|string $receiverId, ?int $groupId = null): void
    {
        $this->connect();
        $groupId ??= $this->configurationService->getGroupId();

        try {
            $this->rest->put('/groups.json/' . $groupId . '/receivers/' . $receiverId . '/setactive');
        } catch (GuzzleException $ex) {
            $this->logger->alert('cannot activate receivers in group: ' . $ex->getMessage(), [$ex, $receiverId]);
        }
    }

    /**
     * @param int|null $groupId
     * @return string|array|null
     */
    public function getGroup(?int $groupId = null): null | string | array
    {
        $this->connect();
        $groupId ??= $this->configurationService->getGroupId();

        try {
            return $this->rest->get('/groups.json/' . $groupId);
        } catch (GuzzleException $ex) {
            $this->logger->alert('cannot fetch group: ' . $ex->getMessage(), [$ex]);
        }

        return null;
    }

    /**
     * @param int|string $id id or email
     * @param int|null $groupId
     * @return bool
     */
    public function isReceiverOfGroup(int | string $id, ?int $groupId = null): bool
    {
        $this->connect();
        $groupId ??= $this->configurationService->getGroupId();

        try {
            $this->rest->get('/groups.json/' . $groupId . '/receivers/' . $id);

            return true;
        } catch (GuzzleException $ex) {
            // A 404 code just means "not in this group" and is a valid response
            if ($ex instanceof BadResponseException && (int)$ex->getCode() !== 404) {
                $this->logger->alert('cannot check if receiver is member of group: ' . $ex->getMessage(), [$ex]);
            }
        }

        return false;
    }

    /**
     * @param int|string $id id or email
     * @param int|null $groupId
     * @return Receiver|null
     */
    public function getReceiverOfGroup(int|string $id, ?int $groupId = null): ?Receiver
    {
        $this->connect();
        $groupId ??= $this->configurationService->getGroupId();
        $result = null;

        try {
            $return = $this->rest->get('/groups.json/' . $groupId . '/receivers/' . $id);
            $result = Receiver::make($return);
        } catch (GuzzleException $ex) {
            // A 404 code just means false and is a valid response
            if ($ex instanceof BadResponseException && (int)$ex->getCode() !== 404) {
                $this->logger->alert('cannot fetch data of receiver: ' . $ex->getMessage(), [$ex]);
            }
        }

        return $result;
    }

    /**
     * @param int|string $id id or email
     * @param int|null $groupId
     * @return bool
     */
    public function isReceiverOfGroupAndActive(int|string $id, ?int $groupId = null): bool
    {
        $receiver = $this->getReceiverOfGroup($id, $groupId ?? $this->configurationService->getGroupId());

        if ($receiver !== null) {
            return $receiver->isActive();
        }

        return false;
    }

    /**
     * @param string $email
     * @param int|null $formId
     * @param int|null $groupId
     */
    public function sendSubscribeMail(string $email, ?int $formId = null, ?int $groupId = null): void
    {
        $this->connect();
        $groupId ??= $this->configurationService->getGroupId();
        $formId ??= $this->configurationService->getFormId();
        $doidata = [
            'user_ip'    => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'referer'    => $_SERVER['HTTP_REFERER'] ?? '',
        ];

        try {
            $this->rest->post(
                '/forms.json/' . $formId . '/send/activate',
                [
                    'email'     => $email,
                    'groups_id' => $groupId,
                    'doidata'   => $doidata,
                ]
            );
        } catch (GuzzleException $ex) {
            // Because CleverReach sends a generic 403 error if the email address is deemed to be spam (which happens
            // frequently), and there is no way to distinguish this from any other reason an email address is forbidden,
            // we log this as a warning instead of an error.
            if ($ex instanceof BadResponseException && (int)$ex->getCode() === 403) {
                $this->logger->warning('Subscription forbidden: ' . $ex->getMessage(), [$ex]);
            } else {
                $this->logger->error('Cannot send subscribe email: ' . $ex->getMessage(), [$ex]);
            }
        }
    }

    /**
     * @param string $email
     * @param int|null $formId
     * @param int|null $groupId
     */
    public function sendUnsubscribeMail(string $email, ?int $formId = null, ?int $groupId = null): void
    {
        $this->connect();
        $groupId ??= $this->configurationService->getGroupId();
        $formId ??= $this->configurationService->getFormId();
        $doidata = [
            'user_ip'    => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'referer'    => $_SERVER['HTTP_REFERER'] ?? '',
        ];

        try {
            $this->rest->post(
                '/forms.json/' . $formId . '/send/deactivate',
                [
                    'email'     => $email,
                    'groups_id' => $groupId,
                    'doidata'   => $doidata,
                ]
            );
        } catch (GuzzleException $ex) {
            $this->logger->alert('cannot send unsubscribe email: ' . $ex->getMessage(), [$ex]);
        }
    }

    /**
     * @param string $email
     * @param string $attributeId
     * @param string $value
     */
    public function setAttributeOfReceiver(string $email, string $attributeId, string $value): void
    {
        $this->connect();

        try {
            $this->rest->put(
                '/receivers.json/' . $email . '/attributes/' . $attributeId,
                [
                    'value' => $value,
                ]
            );
        } catch (GuzzleException $ex) {
            $this->logger->alert('cannot set attribute of subscriber: ' . $ex->getMessage(), [$ex]);
        }
    }

    /**
     * @param string $email
     * @param int|null $groupId
     */
    public function deleteReceiver(string $email, ?int $groupId = null): void
    {
        $this->connect();

        try {
            $this->rest->delete(
                '/receivers.json/' . $email,
                [
                    'group_id' => $groupId,
                ]
            );
        } catch (GuzzleException $ex) {
            $this->logger->alert('cannot delete subscriber: ' . $ex->getMessage(), [$ex]);
        }
    }
}
