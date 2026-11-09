<?php

namespace Tests\Feature;

use Spatie\Backup\Config\Config as BackupConfig;
use Spatie\Backup\Notifications\Notifiable;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Tests\TestCase;

class BackupAndQueueReadinessTest extends TestCase
{
    public function test_backups_exclude_environment_secrets_and_use_the_active_database_connection(): void
    {
        $excludedPaths = config('backup.backup.source.files.exclude');

        $this->assertContains(base_path('.env'), $excludedPaths);
        $this->assertContains(base_path('.env.*'), $excludedPaths);
        $this->assertContains(base_path('.git'), $excludedPaths);
        $this->assertContains(base_path('bootstrap/cache/config.php'), $excludedPaths);
        $this->assertSame(
            [env('DB_CONNECTION', 'sqlite')],
            config('backup.backup.source.databases')
        );
    }

    public function test_backup_package_configuration_is_valid_and_monitors_its_destination_disks(): void
    {
        $backupConfig = BackupConfig::fromArray(config('backup'));

        $this->assertSame(Notifiable::class, $backupConfig->notifications->notifiable);
        $this->assertSame(
            config('backup.backup.destination.disks'),
            $backupConfig->monitoredBackups->monitorBackups[0]['disks']
        );
        $this->assertSame(
            ['mail'],
            $backupConfig->notifications->notifications[
                BackupHasFailedNotification::class
            ]
        );
    }

    public function test_persistent_queue_connections_are_configured(): void
    {
        $this->assertSame('database', config('queue.connections.database.driver'));
        $this->assertSame('redis', config('queue.connections.redis.driver'));
    }
}
