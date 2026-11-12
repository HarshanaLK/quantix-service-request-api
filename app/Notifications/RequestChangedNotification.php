<?php

namespace App\Notifications;

use App\Models\ServiceRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RequestChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    public function __construct(public ServiceRequest $request, public string $from, public string $to) {}
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Service request updated')->greeting('Hello ' . $notifiable->name)->line('Request #' . $this->request->id . ' status changed from ' . $this->from . ' to ' . $this->to . '.')->line('Title: ' . $this->request->title);
    }
    public function toArray(object $notifiable): array
    {
        return ['request_id' => $this->request->id, 'from' => $this->from, 'to' => $this->to, 'title' => $this->request->title];
    }
}
