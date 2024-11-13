<?php

declare(strict_types=1);

namespace Supseven\Cleverreach\Controller;

use Psr\Http\Message\ResponseInterface;
use Supseven\Cleverreach\DTO\RegistrationRequest;
use Supseven\Cleverreach\DTO\Subscriber;
use Supseven\Cleverreach\Service\ConfigurationService;
use Supseven\Cleverreach\Service\SubscriptionService;
use Supseven\Cleverreach\Validation\Validator\OptinValidator;
use TYPO3\CMS\Extbase\Annotation\IgnoreValidation;
use TYPO3\CMS\Extbase\Annotation\Validate;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Actions for newsletter optin/optout
 *
 * @author Georg Großberger <g.grossberger@supseven.at>
 */
class NewsletterController extends ActionController
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
        private readonly ConfigurationService $configurationService,
    ) {
    }

    /**
     * @param RegistrationRequest|null $receiver
     * @return ResponseInterface
     */
    #[IgnoreValidation(['value' => 'receiver'])]
    public function optinFormAction(?RegistrationRequest $receiver = null): ResponseInterface
    {
        $newsletter = $this->getNewsletters();

        $this->view->assign('data', $this->request->getAttribute('currentContentObject')->data);
        $this->view->assign('receiver', $receiver ?? new RegistrationRequest());
        $this->view->assign('newsletter', $newsletter);

        return $this->htmlResponse();
    }

    /**
     * @param RegistrationRequest|null $receiver
     * @return ResponseInterface
     */
    #[Validate(['validator' => OptinValidator::class, 'param' => 'receiver'])]
    public function optinSubmitAction(?RegistrationRequest $receiver = null): ResponseInterface
    {
        if (!$receiver) {
            $this->redirect('optinForm');
        }

        $groupId = $receiver->groupId;
        $formId = (int)$this->configurationService->getNewsletterForGroup($groupId)['formId'];
        $subscription = new Subscriber($receiver->email, $groupId, $formId);

        $this->subscriptionService->subscribe($subscription);

        $uri = $this->uriBuilder->reset()->setCreateAbsoluteUri(true)->setTargetPageUid((int)$this->settings['redirect']['optin'])->build();

        return $this->redirectToUri($uri);
    }

    /**
     * @param RegistrationRequest|null $receiver
     * @return ResponseInterface
     */
    #[IgnoreValidation(['value' => 'receiver'])]
    public function optoutFormAction(?RegistrationRequest $receiver = null): ResponseInterface
    {
        $newsletter = $this->getNewsletters();

        $this->view->assign('data', $this->request->getAttribute('currentContentObject')->data);
        $this->view->assign('receiver', $receiver ?? new RegistrationRequest());
        $this->view->assign('newsletter', $newsletter);

        return $this->htmlResponse();
    }

    /**
     * @Validate(validator="\Supseven\Cleverreach\Validation\Validator\OptoutValidator", param="receiver")
     * @param RegistrationRequest|null $receiver
     * @return ResponseInterface
     */
    public function optoutSubmitAction(?RegistrationRequest $receiver = null): ResponseInterface
    {
        if (!$receiver) {
            return $this->redirect('optinForm');
        }

        $groupId = $receiver->groupId;
        $formId = (int)$this->configurationService->getNewsletterForGroup($groupId)['formId'];
        $subscription = new Subscriber($receiver->email, $groupId, $formId);

        $this->subscriptionService->unsubscribe($subscription);

        $uri = $this->uriBuilder->reset()
            ->setCreateAbsoluteUri(true)
            ->setTargetPageUid((int)$this->settings['redirect']['optout'])
            ->build();

        return $this->redirectToUri($uri);
    }

    /**
     * @return array
     */
    private function getNewsletters(): array
    {
        $newsletter = [];

        foreach ($this->configurationService->getCurrentNewsletters() as $item) {
            $newsletter[$item['groupId']] = $item['label'];
        }

        return $newsletter;
    }
}
