<?php

namespace DarkOak\Models;

/**
 * DarkOak\Models\ApiKeyScope.
 *
 * Definiert alle verfügbaren Scopes für API-Keys.
 * Scopes folgen dem Format: resource:action (z.B. server:read, server:write)
 *
 * @mixin \Eloquent
 */
class ApiKeyScope
{
    /**
     * Server-Scopes.
     */
    public const SERVER_READ = 'server:read';
    public const SERVER_WRITE = 'server:write';
    public const SERVER_POWER = 'server:power';
    public const SERVER_CONSOLE = 'server:console';

    /**
     * Node-Scopes.
     */
    public const NODE_READ = 'node:read';
    public const NODE_WRITE = 'node:write';

    /**
     * User-Scopes.
     */
    public const USER_READ = 'user:read';
    public const USER_WRITE = 'user:write';

    /**
     * Billing-Scopes.
     */
    public const BILLING_READ = 'billing:read';
    public const BILLING_WRITE = 'billing:write';

    /**
     * Allocation-Scopes.
     */
    public const ALLOCATION_READ = 'allocation:read';
    public const ALLOCATION_WRITE = 'allocation:write';

    /**
     * Database-Scopes.
     */
    public const DATABASE_READ = 'database:read';
    public const DATABASE_WRITE = 'database:write';

    /**
     * Backup-Scopes.
     */
    public const BACKUP_READ = 'backup:read';
    public const BACKUP_WRITE = 'backup:write';

    /**
     * Network-Scopes.
     */
    public const NETWORK_READ = 'network:read';
    public const NETWORK_WRITE = 'network:write';

    /**
     * File-Scopes.
     */
    public const FILE_READ = 'file:read';
    public const FILE_WRITE = 'file:write';
    public const FILE_DELETE = 'file:delete';

    /**
     * Schedule-Scopes.
     */
    public const SCHEDULE_READ = 'schedule:read';
    public const SCHEDULE_WRITE = 'schedule:write';

    /**
     * Startup-Scopes.
     */
    public const STARTUP_READ = 'startup:read';
    public const STARTUP_WRITE = 'startup:write';

    /**
     * Settings-Scopes.
     */
    public const SETTINGS_READ = 'settings:read';
    public const SETTINGS_WRITE = 'settings:write';

    /**
     * Weclapp-Scopes (externe Integration).
     */
    public const WECLAPP_READ = 'weclapp:read';
    public const WECLAPP_WRITE = 'weclapp:write';

    /**
     * Alle verfügbaren Scopes.
     */
    public const ALL_SCOPES = [
        self::SERVER_READ,
        self::SERVER_WRITE,
        self::SERVER_POWER,
        self::SERVER_CONSOLE,
        self::NODE_READ,
        self::NODE_WRITE,
        self::USER_READ,
        self::USER_WRITE,
        self::BILLING_READ,
        self::BILLING_WRITE,
        self::ALLOCATION_READ,
        self::ALLOCATION_WRITE,
        self::DATABASE_READ,
        self::DATABASE_WRITE,
        self::BACKUP_READ,
        self::BACKUP_WRITE,
        self::NETWORK_READ,
        self::NETWORK_WRITE,
        self::FILE_READ,
        self::FILE_WRITE,
        self::FILE_DELETE,
        self::SCHEDULE_READ,
        self::SCHEDULE_WRITE,
        self::STARTUP_READ,
        self::STARTUP_WRITE,
        self::SETTINGS_READ,
        self::SETTINGS_WRITE,
        self::WECLAPP_READ,
        self::WECLAPP_WRITE,
    ];

    /**
     * Scope-Gruppen für UI-Gruppierung.
     */
    public const SCOPE_GROUPS = [
        'server' => [
            'label' => 'Server',
            'scopes' => [
                self::SERVER_READ => 'Server anzeigen',
                self::SERVER_WRITE => 'Server bearbeiten',
                self::SERVER_POWER => 'Server starten/stoppen',
                self::SERVER_CONSOLE => 'Konsolenzugriff',
            ],
        ],
        'node' => [
            'label' => 'Nodes',
            'scopes' => [
                self::NODE_READ => 'Nodes anzeigen',
                self::NODE_WRITE => 'Nodes bearbeiten',
            ],
        ],
        'user' => [
            'label' => 'Benutzer',
            'scopes' => [
                self::USER_READ => 'Benutzer anzeigen',
                self::USER_WRITE => 'Benutzer bearbeiten',
            ],
        ],
        'billing' => [
            'label' => 'Billing',
            'scopes' => [
                self::BILLING_READ => 'Rechnungen anzeigen',
                self::BILLING_WRITE => 'Zahlungen durchführen',
            ],
        ],
        'allocation' => [
            'label' => 'Allocations',
            'scopes' => [
                self::ALLOCATION_READ => 'Allocations anzeigen',
                self::ALLOCATION_WRITE => 'Allocations bearbeiten',
            ],
        ],
        'database' => [
            'label' => 'Datenbanken',
            'scopes' => [
                self::DATABASE_READ => 'Datenbanken anzeigen',
                self::DATABASE_WRITE => 'Datenbanken bearbeiten',
            ],
        ],
        'backup' => [
            'label' => 'Backups',
            'scopes' => [
                self::BACKUP_READ => 'Backups anzeigen',
                self::BACKUP_WRITE => 'Backups erstellen/löschen',
            ],
        ],
        'network' => [
            'label' => 'Netzwerk',
            'scopes' => [
                self::NETWORK_READ => 'Netzwerk anzeigen',
                self::NETWORK_WRITE => 'Netzwerk bearbeiten',
            ],
        ],
        'file' => [
            'label' => 'Dateien',
            'scopes' => [
                self::FILE_READ => 'Dateien lesen',
                self::FILE_WRITE => 'Dateien hochladen',
                self::FILE_DELETE => 'Dateien löschen',
            ],
        ],
        'schedule' => [
            'label' => 'Tasks',
            'scopes' => [
                self::SCHEDULE_READ => 'Tasks anzeigen',
                self::SCHEDULE_WRITE => 'Tasks bearbeiten',
            ],
        ],
        'startup' => [
            'label' => 'Startup',
            'scopes' => [
                self::STARTUP_READ => 'Startup anzeigen',
                self::STARTUP_WRITE => 'Startup bearbeiten',
            ],
        ],
        'settings' => [
            'label' => 'Einstellungen',
            'scopes' => [
                self::SETTINGS_READ => 'Einstellungen anzeigen',
                self::SETTINGS_WRITE => 'Einstellungen bearbeiten',
            ],
        ],
        'weclapp' => [
            'label' => 'Weclapp',
            'scopes' => [
                self::WECLAPP_READ => 'Weclapp lesen',
                self::WECLAPP_WRITE => 'Weclapp schreiben',
            ],
        ],
    ];

    /**
     * Prüft, ob ein Scope gültig ist.
     */
    public static function isValid(string $scope): bool
    {
        return in_array($scope, self::ALL_SCOPES, true);
    }

    /**
     * Validiert ein Array von Scopes.
     *
     * @return array<string> Ungültige Scopes
     */
    public static function validateScopes(array $scopes): array
    {
        return array_filter($scopes, fn (string $scope) => !self::isValid($scope));
    }

    /**
     * Gibt alle Scopes zurück (für Rückwärtskompatibilität).
     */
    public static function all(): array
    {
        return self::ALL_SCOPES;
    }

    /**
     * Gibt die Scope-Gruppen zurück.
     */
    public static function groups(): array
    {
        return self::SCOPE_GROUPS;
    }
}
