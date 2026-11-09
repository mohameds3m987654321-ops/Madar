<?php

use Spatie\Backup\Notifications\Notifiable;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification;
use Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification;
use Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumSizeInKB;

$backupDisks = array_map(
    'trim',
    explode(',', env('BACKUP_DISKS', env('APP_ENV') === 'production' ? 's3' : 'local'))
);

return [
    'backup' => [
        'name' => env('APP_NAME', 'laravel-backup'),
        'source' => [
            'files' => [
                'include' => [base_path()],
                'exclude' => [
                    base_path('.env'),
                    base_path('.env.*'),
                    base_path('.git'),
                    base_path('bootstrap/cache/config.php'),
                    base_path('node_modules'),
                    base_path('vendor'),
                    storage_path('framework/cache'),
                    storage_path('logs'),
                ],
                'follow_links' => false,
                'ignore_unreadable_directories' => false,
                'relative_path' => null,
            ],
            'databases' => [
                env('DB_CONNECTION', 'sqlite'),
            ],
        ],
        'destination' => [
            'filename_prefix' => 'return_',
            'disks' => $backupDisks,
        ],
        'temporary_directory' => storage_path('app/backup-temp'),
        'notifications' => [
            'notifications' => [
                BackupHasFailedNotification::class => ['mail'],
                UnhealthyBackupWasFoundNotification::class => ['mail'],
                CleanupHasFailedNotification::class => ['mail'],
                BackupWasSuccessfulNotification::class => ['mail'],
                HealthyBackupWasFoundNotification::class => ['mail'],
                CleanupWasSuccessfulNotification::class => ['mail'],
            ],
            'notifiable' => Notifiable::class,
            'mail' => [
                'to' => env('BACKUP_NOTIFICATION_EMAIL') ?: (env('ADMIN_EMAIL') ?: 'hello@example.com'),
                'from' => [
                    'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
                    'name' => env('MAIL_FROM_NAME', env('APP_NAME', 'Laravel')),
                ],
            ],
            'slack' => [
                'webhook_url' => env('BACKUP_SLACK_WEBHOOK_URL', ''),
                'channel' => null,
                'username' => null,
                'icon' => null,
            ],
        ],
        'monitor_backups' => [
            [
                'name' => env('APP_NAME', 'laravel-backup'),
                'disks' => $backupDisks,
                'health_checks' => [
                    MaximumAgeInDays::class => 7,
                    MaximumSizeInKB::class => 50000,
                ],
            ],
        ],
    ],
    'cleanup' => [
        'strategy' => DefaultStrategy::class,
        'tries' => 1,
        'retry_delay' => 0,
        'default_strategy' => [
            'keep_all_backups_for_days' => 7,
            'keep_daily_backups_for_days' => 16,
            'keep_weekly_backups_for_weeks' => 4,
            'keep_monthly_backups_for_months' => 12,
            'keep_yearly_backups_for_years' => 10,
            'delete_oldest_backups_when_using_more_megabytes_than' => 5000,
        ],
    ],
    'security' => [
        'password' => env('BACKUP_ARCHIVE_PASSWORD', ''),
    ],
];
