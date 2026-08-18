<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MapNotification extends BaseNotification
{
    public function __construct(string $title, string $body, array $data = [], ?string $actionUrl = null)
    {
        parent::__construct($title, $body, 'map', $data, $actionUrl);
    }
}
