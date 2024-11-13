<?php

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Cleverreach',
    'Optin',
    [
        \Supseven\Cleverreach\Controller\NewsletterController::class => 'optinForm, optinSubmit',
    ],
    [
        \Supseven\Cleverreach\Controller\NewsletterController::class => 'optinForm, optinSubmit',
    ],
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Cleverreach',
    'Optout',
    [
        \Supseven\Cleverreach\Controller\NewsletterController::class => 'optoutForm, optoutSubmit',
    ],
    [
        \Supseven\Cleverreach\Controller\NewsletterController::class => 'optoutForm, optoutSubmit',
    ],
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);
