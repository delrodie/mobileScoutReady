<?php

namespace App\EventListener;

use App\Entity\Scout;
use App\Service\GestionQrCode;
use App\Services\ScoutService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Mapping\PrePersist;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

#[AsEntityListener(event: 'prePersist', method: 'prePersist', entity: Scout::class)]
#[AsEntityListener(event: 'postPersist', method: 'postPersist', entity: Scout::class)]
final class ScoutListener
{
    public function __construct(
        private readonly GestionQrCode $qrCode,
        private readonly ScoutService $scoutService,
        private readonly EntityManagerInterface $entityManager
    )
    {
    }

    public function postPersist(Scout $scout, PostPersistEventArgs $args): void
    {
        $code = $this->scoutService->generateCode($scout->getStatut());

        // Sauvegarde du code
        if (empty($scout->getCode())){
            $scout->setCode($code);
        }

        // Generation du qrCodeFile
         if( empty($scout->getQrCodeFile())){
             $scout->setQrCodeFile($this->qrCode->qrCodeGenerator($scout->getQrCodeToken(), $code));
         }
    }
}
