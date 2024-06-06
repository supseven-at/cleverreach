<?php

declare(strict_types=1);

namespace Supseven\Cleverreach\Updates;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

class PluginNameUpdater implements UpgradeWizardInterface
{
    public function getIdentifier(): string
    {
        return 'cleverreachPluginNameUpdater';
    }

    public function getTitle(): string
    {
        return 'EXT:cleverreach: Migrate plugins to CTypes';
    }

    public function getDescription(): string
    {
        return 'Update content elements to use the new CTypes for the plugins instead of the old list_types';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    public function updateNecessary(): bool
    {
        return count($this->getRecords()) > 0;
    }

    public function executeUpdate(): bool
    {
        $records = $this->getRecords();
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();

        $stmt = $queryBuilder
            ->update('tt_content')
            ->set('CType', '?', false)
            ->set('list_type', $queryBuilder->quote(''), false)
            ->where($queryBuilder->expr()->eq('uid', '?'))
            ->prepare();

        foreach ($records as $record) {
            $newType = str_replace(
                ['_pi1', '_pi2'],
                ['_optin', 'optout'],
                $record['list_type']
            );

            $stmt->bindValue(1, $newType);
            $stmt->bindValue(2, $record['uid']);
            $stmt->executeStatement();
        }

        return true;
    }

    protected function getRecords(): array
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        return $queryBuilder
            ->select('uid', 'list_type')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    'CType',
                    $queryBuilder->createNamedParameter('list')
                ),
                $queryBuilder->expr()->like(
                    'list_type',
                    $queryBuilder->createNamedParameter('cleverreach_pi%')
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();
    }
}
