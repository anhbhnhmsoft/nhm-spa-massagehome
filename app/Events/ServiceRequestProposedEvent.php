<?php

namespace App\Events;

use App\Models\ServiceRequestProposal;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ServiceRequestProposedEvent
{
    use Dispatchable, SerializesModels;

    public ServiceRequestProposal $proposal;

    public function __construct(ServiceRequestProposal $proposal)
    {
        $this->proposal = $proposal;
    }
}
