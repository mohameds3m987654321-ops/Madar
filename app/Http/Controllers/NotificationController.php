<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class NotificationController extends Controller
{
    public function open(string $notification): RedirectResponse
    {
        $notification = auth()->user()->notifications()->findOrFail($notification);
        $notification->markAsRead();

        $url = $notification->data['url'] ?? null;
        if (!is_string($url) || !str_starts_with($url, '/') || str_starts_with($url, '//')) {
            return redirect()->route('users.profile');
        }

        return redirect($url);
    }
}
