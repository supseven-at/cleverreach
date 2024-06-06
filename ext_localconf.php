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

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig('@import \'EXT:cleverreach/Configuration/TsConfig/NewElementWizard.tsconfig\';');

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['cleverreachPluginPermissionUpdater'] =
    \Supseven\Cleverreach\Updates\PluginPermissionUpdater::class;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update']['cleverreachPluginNameUpdater'] =
    \Supseven\Cleverreach\Updates\PluginNameUpdater::class;
