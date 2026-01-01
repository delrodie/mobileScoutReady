<?php

namespace App\Twig\Components;

use App\Entity\Activite;
use App\Repository\ParticiperRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('ParticipantActivite', template:'components/ParticipantActivite.html.twig')]
final class ParticipantActivite
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public ?string $query = null;

    #[LiveProp]
    public Activite $activite;

    #[LiveProp(writable: true)]
    public int $limit = 10;

    public function __construct(
        private readonly ParticiperRepository $participerRepository,
    )
    {
    }

    public function getParticipants(): array
    {

        return $this->participerRepository->findPresenceByActiviteAndRecherche(
            $this->activite->getId(),
            $this->query,
            $this->limit,
            0
        );

    }

    public function getTotalParticipants(): int
    {
        return $this->participerRepository->countPresenceByActiviteAndRecherche(
            $this->activite->getId(),
            $this->query
        );
    }
}
