<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class OrderStatusNotification extends Notification
{
    use Queueable;

    // 1. تعريف متغيرات عامة لاستقبال البيانات الديناميكية
    public $title;
    public $body;


    public function __construct($title, $body)
    {
        $this->title = $title;
        $this->body = $body;
    }

    public function via($notifiable)
    {
        return [FcmChannel::class];
    }


    public function toFcm($notifiable): FcmMessage
    {
        $message = (new FcmMessage(
            notification: new FcmNotification(
                title: $this->title, // استخدام العنوان المتغير
                body: $this->body    // استخدام النص المتغير
            )
        ));

        // إذا أردت تمرير الـ id بشكل اختياري ضمن البيانات

        return $message;
    }
}
