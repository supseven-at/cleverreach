<?php

declare(strict_types=1);

use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

(static function (): void {
    $plugins = ['Optin', 'Optout'];

    foreach ($plugins as $plugin) {
        $key = 'cleverreach_' . strtolower($plugin);

        ExtensionUtility::registerPlugin(
            'Cleverreach',
            $plugin,
            'LLL:EXT:cleverreach/Resources/Private/Language/shared.xlf:plugin.' . $key . '.title',
            'cleverreach',
            'plugins',
            'LLL:EXT:cleverreach/Resources/Private/Language/shared.xlf:plugin.' . $key . '.description',
        );

        $GLOBALS['TCA']['tt_content']['types'][$key] = $GLOBALS['TCA']['tt_content']['types']['header'];
    }
})();
