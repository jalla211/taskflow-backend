<?php

namespace App\Events;

use App\Models\User;
use App\Models\Comment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserMentioned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $mentionedUser;
    public $comment;
    public $mentionedBy;

    public function __construct(User $mentionedUser, Comment $comment, User $mentionedBy)
    {
        $this->mentionedUser = $mentionedUser;
        $this->comment = $comment;
        $this->mentionedBy = $mentionedBy;
    }
}