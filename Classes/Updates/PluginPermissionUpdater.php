<?php

declare(strict_types=1);

namespace Supseven\Cleverreach\Updates;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\BackendModulePermissionMigration;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

class PluginPermissionUpdater implements UpgradeWizardInterface
{
    public function getIdentifier(): string
    {
        return 'cleverreachPluginPermissionUpdater';
    }

    public function getTitle(): string
    {
        return 'EXT:cleverreach: Migrate plugin permissions';
    }

    public function getDescription(): string
    {
        return 'Migrate permissions of be_groups to use new CTypes instead of cleverreach_pi list_types';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
            BackendModulePermissionMigration::class,
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
        $queryBuilder = $connectionPool->getQueryBuilderForTable('be_groups');
        $queryBuilder->getRestrictions()->removeAll();
        $stmt = $queryBuilder
            ->update('be_groups')
            ->set('explicit_allowdeny', '?', false)
            ->where($queryBuilder->expr()->eq('uid', '?'))
            ->prepare();

        foreach ($records as $record) {
            $newList = str_replace(
                ['list_type:cleverreach_pi1', 'list_type:cleverreach_pi2'],
                ['CType:cleverreach_optin', 'list_type:cleverreach_optin'],
                $record['explicit_allowdeny']
            );

            $stmt->bindValue(1, $newList);
            $stmt->bindValue(2, $record['uid']);
            $stmt->executeStatement();
        }

        return true;
    }

    protected function getRecords(): array
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $queryBuilder = $connectionPool->getQueryBuilderForTable('be_groups');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        return $queryBuilder
            ->select('uid', 'explicit_allowdeny')
            ->from('be_groups')
            ->where(
                $queryBuilder->expr()->like(
                    'explicit_allowdeny',
                    $queryBuilder->createNamedParameter('%list_type:cleverreach_pi%')
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();
    }

    protected function updateRow(array $row): void
    {
        $default = 'tt_content:CType:news_pi1,tt_content:CType:news_newsliststicky,tt_content:CType:news_newsdetail,tt_content:CType:news_newsdatemenu,tt_content:CType:news_newssearchform,tt_content:CType:news_newssearchresult,tt_content:CType:news_newsselectedlist,tt_content:CType:news_categorylist,tt_content:CType:news_taglist';

        if (GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion() >= 12) {
            $searchReplace = [
                'tt_content:list_type:news_pi1:ALLOW' => $default,
                'tt_content:list_type:news_pi1:DENY'  => '',
                'tt_content:list_type:news_pi1'       => $default,
            ];
        } else {
            $default .= ',';
            $default = str_replace(',', ':ALLOW,', $default);
            $searchReplace = [
                'tt_content:list_type:news_pi1:ALLOW' => $default,
                'tt_content:list_type:news_pi1:DENY'  => str_replace($default, 'ALLOW', 'DENY'),
            ];
        }

        $newList = str_replace(array_keys($searchReplace), array_values($searchReplace), $row['explicit_allowdeny']);
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_groups');
        $queryBuilder->update('be_groups')
            ->set('explicit_allowdeny', $newList)
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->createNamedParameter($row['uid'], Connection::PARAM_INT)
                )
            )
            ->executeStatement();
    }
}
