<?php

namespace App\Events;

use App\Models\SessionMessage;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionMessageReceived
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public SessionMessage $message,
    ) {}
}
