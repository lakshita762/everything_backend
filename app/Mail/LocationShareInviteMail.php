<?php

namespace App\Mail;

use App\Models\LocationShare;
use App\Models\LocationShareParticipant;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LocationShareInviteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public LocationShare $share, public LocationShareParticipant $participant)
    {
        // Ensure relations we need are available
        $this->share->loadMissing('owner');
    }

    public function build(): self
    {
        $owner = $this->share->owner;
        $subject = sprintf(
            '%s invited you to a location share%s',
            $owner?->name ?? 'A user',
            $this->share->name ? (': "' . $this->share->name . '"') : ''
        );

        return $this->subject($subject)
            ->view('emails.location_share_invite', [
                'share' => $this->share,
                'participant' => $this->participant,
                'owner' => $owner,
            ]);
    }
}

