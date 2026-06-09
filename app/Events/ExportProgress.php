<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ExportProgress implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string  $status,   // 'processing' | 'done' | 'failed'
        public ?string $url,
        public int     $userId,
    ) {}

    public function broadcastOn(): array
    {
        // Private channel per user biar tidak bentrok
        return [new Channel('export.' . $this->userId)];
    }

    public function broadcastAs(): string
    {
        return 'progress';
    }
}