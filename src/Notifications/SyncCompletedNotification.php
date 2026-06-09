<?php

namespace Mostafax\ErpIntegrationHub\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Mostafax\ErpIntegrationHub\Models\SyncLog;

class SyncCompletedNotification extends Notification
{
    public function __construct(private readonly SyncLog $log) {}

    public function via(mixed $notifiable): array
    {
        return config('erp-integration-hub.notifications.channels', ['database', 'mail']);
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('[ERP Bridge] Sync Completed — ' . $this->log->syncProfile?->name)
            ->greeting('Sync Completed Successfully')
            ->line("Profile: **{$this->log->syncProfile?->name}**")
            ->line("Records Processed: {$this->log->processed_records}")
            ->line("Success: {$this->log->success_records} | Failed: {$this->log->failed_records}")
            ->line("Duration: {$this->log->duration_formatted}")
            ->action('View Log', url('/erp-integration-hub/logs/' . $this->log->id));
    }

    public function toSlack(mixed $notifiable): SlackMessage
    {
        return (new SlackMessage())
            ->success()
            ->content("[ERP Bridge] Sync **{$this->log->syncProfile?->name}** completed. "
                . "Processed: {$this->log->processed_records}, "
                . "Failed: {$this->log->failed_records}");
    }

    public function toArray(mixed $notifiable): array
    {
        return [
            'type'       => 'sync_completed',
            'log_id'     => $this->log->id,
            'profile'    => $this->log->syncProfile?->name,
            'total'      => $this->log->processed_records,
            'success'    => $this->log->success_records,
            'failed'     => $this->log->failed_records,
            'duration'   => $this->log->duration_formatted,
        ];
    }
}
