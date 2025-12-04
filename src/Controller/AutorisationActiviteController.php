<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Activite;
use App\Entity\AutorisationPointageActivite;
use App\Enum\AutorisationPointeur;
use App\Form\AutorisationActiviteType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/autorisation-activite')]
class AutorisationActiviteController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route('/{id}', name: 'app_autorisation_activite_ajouter', methods: ['GET','POST'])]
    public function ajouter(Request $request, Activite $activite): Response
    {
        $autorisation = new AutorisationPointageActivite();
        $form = $this->createForm(AutorisationActiviteType::class, $autorisation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pointeurs = $autorisation->getPointeurs();
            foreach ($pointeurs as $pointeur) {
                $pointage = new AutorisationPointageActivite();
                $pointage->setActivite($activite);
                $pointage->setScout($pointeur);
                $pointage->setRole(AutorisationPointeur::INVITE->value);
                $pointage->setCreatedAt(new \DateTimeImmutable());
                $this->entityManager->persist($pointage);

                notyf()->success("L'autorisation a été accordée à {$pointeur->getNom()} {$pointeur->getPrenom()}  avec succès. ");
            }

            $this->entityManager->flush();

            return $this->redirectToRoute('app_activite_show',['id' => $activite->getId()]);
        }

        return $this->render('activite/autorisation.html.twig',[
            'activite' => $activite,
            'autorisation' => $autorisation,
            'form' => $form
        ]);
    }
}
