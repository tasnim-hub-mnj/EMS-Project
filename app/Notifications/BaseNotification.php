<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class BaseNotification extends Notification
{
    use Queueable;

    protected string $title;
    protected string $body;
    protected string $type;
    protected array $data;
    protected ?string $actionUrl;

    public function __construct(string $title, string $body, string $type, array $data = [], ?string $actionUrl = null)
    {
        $this->title = $title;
        $this->body = $body;
        $this->type = $type;
        $this->data = $data;
        $this->actionUrl = $actionUrl;
    }

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'type' => $this->type,
            'data' => $this->data,
            'action_url' => $this->actionUrl,
        ];
    }
}

