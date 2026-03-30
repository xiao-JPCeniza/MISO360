<?php

namespace App\Notifications\Admin;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewUserRegisteredNotification extends Notification
{
    use Queueable;

    public function __construct(public User $newUser) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'new_user_registered',
            'title' => 'New user registered',
            'message' => $this->newUser->name.' just created an account.',
            'url' => route('admin.users.show', $this->newUser),
            'userId' => $this->newUser->id,
        ];
    }
}
