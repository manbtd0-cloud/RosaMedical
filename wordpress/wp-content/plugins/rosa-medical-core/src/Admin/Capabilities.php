<?php

declare(strict_types=1);

namespace RosaMedical\Core\Admin;

final class Capabilities
{
    public const MANAGE_CONTENT = 'rosa_manage_content';
    public const ROLE = 'rosa_content_manager';
    private const VERSION = '1';

    /** @var list<string> */
    private const FORBIDDEN_CAPABILITIES = [
        'activate_plugins',
        'delete_plugins',
        'edit_plugins',
        'install_plugins',
        'update_plugins',
        'delete_themes',
        'edit_themes',
        'install_themes',
        'switch_themes',
        'update_themes',
        'update_core',
    ];

    public static function ensure(): void
    {
        $role = get_role(self::ROLE);
        $shopManager = get_role('shop_manager');
        $shopManagerHasProducts = $shopManager && ! empty($shopManager->capabilities['edit_products']);
        $roleHasProducts = $role && ! empty($role->capabilities['edit_products']);
        $versionCurrent = (string) get_option('rosa_capabilities_version', '') === self::VERSION;

        if ($versionCurrent
            && $role
            && ! empty($role->capabilities[self::MANAGE_CONTENT])
            && (! $shopManagerHasProducts || $roleHasProducts)) {
            return;
        }

        self::install();
        update_option('rosa_capabilities_version', self::VERSION, false);
    }

    public static function install(): void
    {
        $caps = [
            'read' => true,
            'upload_files' => true,
            self::MANAGE_CONTENT => true,
        ];

        foreach (['editor', 'shop_manager'] as $sourceName) {
            $source = get_role($sourceName);
            if (! $source) {
                continue;
            }
            foreach ($source->capabilities as $cap => $grant) {
                if ($grant && ! in_array($cap, self::FORBIDDEN_CAPABILITIES, true)) {
                    $caps[$cap] = true;
                }
            }
        }

        $role = get_role(self::ROLE);
        if (! $role) {
            $role = add_role(self::ROLE, 'Rosa Content Manager', $caps);
        }

        if ($role) {
            foreach ($caps as $cap => $grant) {
                if ($grant) {
                    $role->add_cap($cap);
                }
            }
            foreach (self::FORBIDDEN_CAPABILITIES as $cap) {
                $role->remove_cap($cap);
            }
        }

        $admin = get_role('administrator');
        if ($admin) {
            $admin->add_cap(self::MANAGE_CONTENT);
        }
    }

    public static function settingsCapability(): string
    {
        return self::MANAGE_CONTENT;
    }
}
