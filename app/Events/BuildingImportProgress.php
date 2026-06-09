<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BuildingImportProgress implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $processed;
    public int $total;
    public string $status;

    public function __construct(int $processed, int $total, string $status = 'processing')
    {
        $this->processed = $processed;
        $this->total = $total;
        $this->status = $status;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('building-import'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'progress';
    }
}