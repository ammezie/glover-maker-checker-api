<?php

namespace App\Listeners;

use App\Events\RequestCreated;
use App\Mail\RequestCreated as MailRequestCreated;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendRequestNotification
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\RequestCreated  $event
     * @return void
     */
    public function handle(RequestCreated $event)
    {
        $otherAdmins = User::where('is_admin', true)
            ->where('id', '<>', $event->request->requested_by)
            ->get();

        foreach ($otherAdmins as $admin) {
            Mail::to($admin)->send(new MailRequestCreated($event->request, $admin));
        }
    }
}
