<?php

namespace App\Events\User\Banking;

use App\Models\LinkedBankAccount;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessBankAccountConnected implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, Queueable, InteractsWithQueue, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public array $payload,
        public LinkedBankAccount $account,
    ) {
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
