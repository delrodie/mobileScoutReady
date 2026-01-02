<?php

namespace App\Twig\Components;

use App\Entity\Reunion;
use App\Repository\AssisterRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('ParticipantReunion', template: 'components/ParticipantReunion.html.twig')]
final class ParticipantReunion
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public ?string $query = null;

    #[LiveProp]
    public Reunion $reunion;

    #[LiveProp(writable: true)]
    public int $limit = 10;

    public function __construct(
        private readonly AssisterRepository $assisterRepository
    )
    {
    }

    public function getParticipants()
    {
        return $this->assisterRepository->findPresenceByReunion(
            $this->reunion->getId(),
            $this->query,
            $this->limit,
            0
        );
    }

    public function getTotalParticipants()
    {
        return $this->assisterRepository->countPresenceByReunion(
            $this->reunion->getId(),
            $this->query,
        );
    }
}
