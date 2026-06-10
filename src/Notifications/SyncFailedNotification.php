<?php

declare(strict_types=1);

namespace Mostafax\ErpIntegrationHub\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Mostafax\ErpIntegrationHub\Models\SyncLog;

class SyncFailedNotification extends Notification
{
    public function __construct(private readonly SyncLog $log) {}

    public function via(mixed $notifiable): array
    {
        return config('erp-integration-hub.notifications.channels', ['database', 'mail']);
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage())
            ->error()
            ->subject('[ERP Bridge] Sync FAILED — ' . $this->log->syncProfile?->name)
            ->greeting('Sync Failed!')
            ->line("Profile: **{$this->log->syncProfile?->name}**")
            ->line("Error: {$this->log->message}")
            ->line("Failed Records: {$this->log->failed_records}")
            ->action('View Log & Retry', url('/erp-integration-hub/logs/' . $this->log->id));
    }

    public function toArray(mixed $notifiable): array
    {
        return [
            'type'    => 'sync_failed',
            'log_id'  => $this->log->id,
            'profile' => $this->log->syncProfile?->name,
            'error'   => $this->log->message,
            'failed'  => $this->log->failed_records,
        ];
    }
}
