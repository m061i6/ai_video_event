<?php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class ScriptsGenerated implements ShouldBroadcast
{
    use InteractsWithSockets, SerializesModels;

    public $ids;

    public function __construct($ids)
    {
        $this->ids = $ids;
    }

    public function broadcastOn()
    {
        return new Channel('scripts');
    }
}
