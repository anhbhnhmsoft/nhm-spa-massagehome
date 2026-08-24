<?php

namespace App\Events;

use App\Models\ServiceRequestProposal;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProposalRespondedEvent
{
    use Dispatchable, SerializesModels;

    public ServiceRequestProposal $proposal;
    public string $actorRole; // 'ktv' | 'customer'
    public bool $isAccepted;

    public function __construct(ServiceRequestProposal $proposal, string $actorRole, bool $isAccepted)
    {
        $this->proposal = $proposal;
        $this->actorRole = $actorRole;
        $this->isAccepted = $isAccepted;
    }
}
