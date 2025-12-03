<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AutorisationPointageReunion;
use App\Entity\Reunion;
use App\Enum\AutorisationPointeur;
use App\Form\AutorisationReunionType;
use App\Repository\FonctionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/autorisation-reunion')]
class AutorisationReunionController extends AbstractController
{
    public function __construct(
        private readonly FonctionRepository $fonctionRepository, private readonly EntityManagerInterface $entityManager
    )
    {
    }

    #[Route('/{id}', name: 'app_autorisation_reunion_ajouter', methods: ['GET','POST'])]
    public function ajouter(Request $request, Reunion $reunion): Response
    {
        $fonction = $this->fonctionRepository->findOneByScoutCode($reunion?->getCreatedBy());
        $auteur = [
            'id' => $fonction?->getScout()->getId(),
            'nom' => $fonction?->getScout()->getNom(). ' '. $fonction?->getScout()->getPrenom(),
            'poste' => $fonction?->getDetailPoste()
        ];


        $autorisation = new AutorisationPointageReunion();
        $form = $this->createForm(AutorisationReunionType::class, $autorisation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pointeurs = $autorisation->getPointeurs();
            foreach ($pointeurs as $pointeur){
                $autorisationPointage = new AutorisationPointageReunion();
                $autorisationPointage->setReunion($reunion);
                $autorisationPointage->setScout($pointeur);
                $autorisationPointage->setRole(AutorisationPointeur::INVITE->value);
                $autorisationPointage->setCreatedAt(new \DateTimeImmutable());
                $this->entityManager->persist($autorisationPointage);
            }

            $this->entityManager->flush();
            notyf()->success("Les autorisations ont été effectuées avec succès!");

            return $this->redirectToRoute('app_reunion_show',['id' => $reunion->getId()]);
        }

        return $this->render('reunion/autorisation.html.twig', [
            'autorisation' => $autorisation,
            'reunion' => $reunion,
            'form' => $form,
            'auteur_nom' => $auteur['nom'],
            'auteur_poste' => $auteur['poste'],
        ]);
    }
}
