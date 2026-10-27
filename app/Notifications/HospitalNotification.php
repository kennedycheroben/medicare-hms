<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class HospitalNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @param  array<string, mixed>  $extra
     */
    public function __construct(
        public string $category,
        public string $eventType,
        public string $title,
        public string $message,
        public string $actionUrl,
        public ?string $recordType = null,
        public ?string $recordId = null,
        public array $extra = []
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification stored in database.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'category' => $this->category,
            'event_type' => $this->eventType,
            'title' => $this->title,
            'message' => $this->message,
            'action_url' => $this->actionUrl,
            'record_type' => $this->recordType,
            'record_id' => $this->recordId,
            'extra' => $this->extra,
            'created_at' => now()->toIso8601String(),
        ];
    }
}
