<?php

return [
    'foundation' => [
        'properties' => ['--fixture-background', '--fixture-foreground'],
        'aliases' => [
            '--color-fixture-background' => '--fixture-background',
            '--color-fixture-foreground' => '--fixture-foreground',
        ],
        'contrast_pairs' => [
            'fixture' => [
                'foreground' => '--fixture-foreground',
                'background' => '--fixture-background',
            ],
        ],
    ],
    'modules' => [
        'surfaces' => [
            'components' => ['panel'],
            'controllers' => [],
            'dependencies' => [],
        ],
        'actions' => [
            'components' => ['action'],
            'controllers' => [],
            'dependencies' => ['surfaces'],
        ],
        'feedback' => [
            'components' => [],
            'controllers' => ['status'],
            'dependencies' => [],
        ],
    ],
    'presets' => [
        'constellation' => [
            'base' => [
                'presets/constellation/theme.css',
                'presets/constellation/aliases.css',
            ],
            'properties' => [
                '--fixture-surface',
                '--fixture-surface-foreground',
                '--fixture-radius',
            ],
            'aliases' => [
                '--color-fixture-surface' => '--fixture-surface',
                '--radius-fixture' => '--fixture-radius',
            ],
            'contrast_pairs' => [
                'fixture-surface' => [
                    'foreground' => '--fixture-surface-foreground',
                    'background' => '--fixture-surface',
                ],
            ],
            'sources' => [
                [
                    'path' => 'presets/constellation/layout/surfaces.css',
                    'modules' => ['surfaces', 'actions'],
                ],
                [
                    'path' => 'presets/constellation/feedback.css',
                    'modules' => ['feedback'],
                ],
            ],
        ],
        'orbit' => [
            'base' => [],
            'properties' => [],
            'aliases' => [],
            'contrast_pairs' => [],
            'sources' => [
                [
                    'path' => 'presets/orbit/all.css',
                    'modules' => ['surfaces', 'actions', 'feedback'],
                ],
            ],
        ],
    ],
];
