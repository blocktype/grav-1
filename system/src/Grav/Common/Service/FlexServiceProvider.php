<?php

/**
 * @package    Grav\Common\Service
 *
 * @copyright  Copyright (C) 2015 - 2020 Trilby Media, LLC. All rights reserved.
 * @license    MIT License; see LICENSE file for details.
 */

namespace Grav\Common\Service;

use Grav\Common\Config\Config;
use Grav\Common\Flex\Users\Storage\UserFolderStorage;
use Grav\Common\Grav;
use Grav\Events\FlexRegisterEvent;
use Grav\Framework\Flex\Flex;
use Grav\Framework\Flex\FlexFormFlash;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class FlexServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container)
    {
        $container['flex'] = function (Grav $container) {
            /** @var Config $config */
            $config = $container['config'];

            $flex = new Flex([], ['object' => $config->get('system.flex', [])]);
            FlexFormFlash::setFlex($flex);

            $accountsEnabled = $config->get('system.accounts.type', 'regular') === 'flex';
            $pagesEnabled = $config->get('system.pages.type', 'regular') === 'flex';

            // Add built-in types from Grav.
            if ($pagesEnabled) {
                $flex->addDirectoryType(
                    'grav-pages',
                    'blueprints://flex/pages.yaml',
                    [
                        'enabled' => $pagesEnabled
                    ]
                );
            }
            if ($accountsEnabled) {
                $flex->addDirectoryType(
                    'grav-accounts',
                    'blueprints://flex/accounts.yaml',
                    [
                        'enabled' => $accountsEnabled,
                        'data' => [
                            'storage' => $this->getFlexAccountsStorage($config),
                        ]
                    ]
                );
                $flex->addDirectoryType(
                    'grav-user-groups',
                    'blueprints://flex/user-groups.yaml',
                    [
                        'enabled' => $accountsEnabled
                    ]
                );
            }

            // Call event to register Flex Directories.
            $event = new FlexRegisterEvent($flex);
            $container->dispatchEvent($event);

            return $flex;
        };
    }

    private function getFlexAccountsStorage(Config $config)
    {
        $value = $config->get('system.accounts.storage', 'file');
        if (\is_array($value)) {
            return $value;
        }

        if ($value === 'folder') {
            return [
                'class' => UserFolderStorage::class,
                'options' => [
                    'file' => 'user',
                    'pattern' => '{FOLDER}/{KEY:2}/{KEY}/{FILE}{EXT}',
                    'key' => 'storage_key',
                ],
            ];
        }

        return [];
    }
}
