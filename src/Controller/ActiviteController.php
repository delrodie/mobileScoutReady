<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Activite;
use App\Entity\AutorisationPointageActivite;
use App\Entity\Scout;
use App\Form\ActiviteType;
use App\Repository\ActiviteRepository;
use App\Repository\InstanceRepository;
use App\Repository\ScoutRepository;
use App\Services\GestionAffiche;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/activites')]
class ActiviteController extends AbstractController
{
    public function __construct(
        private readonly ScoutRepository    $scoutRepository,
        private readonly InstanceRepository $instanceRepository,
        private readonly ActiviteRepository $activiteRepository,
        private readonly GestionAffiche     $gestionAffiche,
        private readonly EntityManagerInterface $entityManager
    )
    {
    }

    #[Route('/', name:'app_activite_index')]
    public function index(): Response
    {
        return $this->render('activite/index.html.twig');
    }

    #[Route('/new', name:'app_activite_new', methods: ['GET','POST'])]
    public function new(Request $request)
    {
        $activite = new Activite();
        $form = $this->createForm(ActiviteType::class, $activite);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()){
            $reqSlug = $request->get("_slug");
            $reqInstance = $request->get("_instance");

            $scout = $this->scoutRepository->findOneBy(['slug' => $reqSlug]);
            if (!$scout){
                flash()->error("Aucun profil trouvé. Veuillez vous reconnecter!", [], "Echèc");
                return $this->redirectToRoute('app_search_phone');
            }

            $instance = $this->instanceRepository->findOneBy(['id' => (int) $reqInstance]);
            if (!$instance){
                flash()->error("Votre profil n'est associé à aucune instance. Veuillez vous reconnecter!", [],'Echèc');
            }

            // Gestion de l'affiche
            $this->gestionAffiche->media($form, $activite);
            $activite->setInstance($instance);

            // Ajout de l'operateur comme personne qui peut pointer
            $personnesAutorisees = $activite->getAutorisations()->toArray();
            $activite->getAutorisations()->clear();
            $this->addAutorisation($activite, $scout, 'CREATEUR');

            foreach ($personnesAutorisees as $personne) {
                if ($personne instanceof Scout){
                    $this->addAutorisation($activite, $personne, 'INVITE');
                }
            }

            $this->entityManager->persist($activite);
            $this->entityManager->flush();

            flash()->success("L'activité a été enregistrée avec succès!", [], "Succès");

            return $this->redirectToRoute('app_activite_index');
        }

        return $this->render('activite/new.html.twig',[
            'activite' => $activite,
            'form' => $form
        ]);
    }

    #[Route('/{id}', name: 'app_activite_show', methods: ['GET'])]
    public function show(Activite $activite)
    {
        return $this->render('activite/show.html.twig', [
            'activite' => $activite,
        ]);
    }

    /**
     * Crée et lie une entité AutorisationPointageActivite pour un scout donné.
     * @param Activite $activite
     * @param Scout $scout
     * @param string $role
     * @return void
     */
    private function addAutorisation(Activite $activite, Scout $scout, string $role): void
    {
        $autorisation = new AutorisationPointageActivite();
        $autorisation->addScout($scout);
        $autorisation->addActivite($activite);
        $autorisation->setRole($role);
        $autorisation->setCreatedAt(new \DateTimeImmutable());

        $activite->addAutorisation($autorisation);
        $this->entityManager->persist($autorisation);
    }
}
