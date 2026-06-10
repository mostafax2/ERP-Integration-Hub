<?php

declare(strict_types=1);

namespace Mostafax\ErpIntegrationHub\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Mostafax\ErpIntegrationHub\Models\DynamicsConnection;

class ConnectionLostNotification extends Notification
{
    public function __construct(
        private readonly DynamicsConnection $connection,
        private readonly string $reason = '',
    ) {}

    public function via(mixed $notifiable): array
    {
        return config('erp-integration-hub.notifications.channels', ['database', 'mail']);
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage())
            ->error()
            ->subject("[ERP Bridge] Connection Lost — {$this->connection->name}")
            ->line("The connection **{$this->connection->name}** ({$this->connection->driver_label}) is no longer reachable.")
            ->line("Reason: {$this->reason}")
            ->action('Manage Connection', url('/erp-integration-hub/connections/' . $this->connection->id));
    }

    public function toArray(mixed $notifiable): array
    {
        return [
            'type'            => 'connection_lost',
            'connection_id'   => $this->connection->id,
            'connection_name' => $this->connection->name,
            'reason'          => $this->reason,
        ];
    }
}
