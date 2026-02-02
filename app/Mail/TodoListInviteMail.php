<?php

namespace App\Mail;

use App\Models\TodoListInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TodoListInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public TodoListInvite $invite)
    {
        // Ensure relations we need are available
        $this->invite->loadMissing('list.owner');
    }

    public function build(): self
    {
        $list = $this->invite->list;
        $owner = $list?->owner;

        $subject = sprintf(
            '%s invited you to collaborate on "%s"',
            $owner?->name ?? 'A user',
            $list?->title ?? 'a todo list'
        );

        return $this->subject($subject)
            ->view('emails.todo_list_invite', [
                'invite' => $this->invite,
                'list' => $list,
                'owner' => $owner,
            ]);
    }
}

