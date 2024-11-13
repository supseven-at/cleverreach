<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title'        => 'CleverReach',
    'description'  => 'CleverReach plugin and finishers and validators for EXT:form',
    'category'     => 'misc',
    'state'        => 'stable',
    'author'       => 'Supseven',
    'author_email' => 'office@supseven.at',
    'version'      => '1.0.0',
    'constraints'  => [
        'depends' => [
            'typo3' => '11.5.0-11.5.999',
            'php'   => '8.1.0-8.2.999',
        ],
        'conflicts' => [],
        'suggests'  => [],
    ],
];
