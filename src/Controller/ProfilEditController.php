<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\InfosComplementaire;
use App\Entity\Scout;
use App\Enum\Branche;
use App\Enum\StageBaseNiveau1;
use App\Enum\StageFormation;
use App\Form\ProfilEditCivilType;
use App\Repository\InfosComplementaireRepository;
use App\Repository\ScoutRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/profil-edit')]
class ProfilEditController extends AbstractController
{
    public function __construct(
        private readonly ScoutRepository $scoutRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly InfosComplementaireRepository $complementaireRepository
    )
    {
    }

    #[Route('/{slug}', name: 'app_profil_edit_civile', methods: ['GET','POST'])]
    public function civile(Request $request, $slug): Response
    {
        $scout = $this->scoutRepository->findOneBy(['slug' => $slug]);

        $form = $this->createForm(ProfilEditCivilType::class, $scout);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            notyf()->success("Les informations civiles ont été modifiées avec succès!");

            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'status' => 'success',
                    'redirect' => $this->generateUrl('app_profil_civil'),
                    'data' => [
                        'id' => $scout->getId(),
                        'nom' => $scout->getNom(),
                        'prenom' => $scout->getPrenom(),
                        'telephone' => $scout->getTelephone(),
                        'email' => $scout->getEmail(),
                        'slug' => $scout->getSlug(),
                        'dateNaissance' => $scout->getDateNaissance(),
                        'sexe' => $scout->getSexe(),
                        'phoneParent' => $scout->isPhoneParent(),
                        'matricule' => $scout->getMatricule(),
                        'code' => $scout->getCode(),
                        'qrCodeToken' => $scout->getQrCodeToken(),
                        'qrCodeFile' => $scout->getQrCodeFile(),
                        'photo' => $scout->getPhoto(),
                    ]
                ]);
            }

            return $this->redirectToRoute('app_profil_civil');
        }

        return $this->render('profil/edit_civil.html.twig',[
            'form' => $form,
            'scout' => $scout
        ]);
    }

    #[Route('/{slug}/infos-complementaires', name: 'app_profiledit_infos_complementaires')]
    public function infosComplementaires(Request $request, $slug): Response
    {
        $scout = $this->scoutRepository->findOneBy(['slug' => $slug]);
        if (!$scout){
            notyf()->error("Echèc! Votre profil n'a pas été trouvé! Veuillez vous reconnecter");
            return $this->json([
                'status' => 'error',
                'message' => "Echèc! Votre profil n'a pas été trouvé! Vuillez vous reconnecter."
            ], Response::HTTP_NOT_FOUND);
        }

        $complementaire = $this->complementaireRepository->findOneBy(['scout' => $scout]);
        if (!$complementaire){
            $complementaire = new InfosComplementaire();
            $complementaire->setCreatedAt(new \DateTimeImmutable());
        }

        // Traitement du formulaire
        if ($request->isMethod('POST')){
            $complementaire->setBranche($request->request->get('_complementBranche'));

            $isFormation = $request->request->get('_complementIsFormation') === 'on';
            $complementaire->setFormation($isFormation);

            if ($isFormation){
                $complementaire->setStageBaseNiveau1($request->request->get('_complementBaseNiveau1'));
                $complementaire->setAnneeBaseNiveau1((int) $request->request->get('_complementBaseNiveau1Annee'));

                $complementaire->setStageBaseNiveau2($request->request->get('_complementBaseNiveau2'));
                $complementaire->setAnneeBaseNiveau2((int) $request->request->get('_complementBaseNiveau2Annee'));

                $complementaire->setStageAvanceNiveau1($request->request->get('_complementAvanceNiveau1'));
                $complementaire->setAnneeAvanceNiveau1((int) $request->request->get('_complementAvanceNiveau1Annee'));

                $complementaire->setStageAvanceNiveau2($request->request->get('_complementAvanceNiveau2'));
                $complementaire->setAnneeAvanceNiveau2((int) $request->request->get('_complementAvanceNiveau2Annee'));

                $complementaire->setStageAvanceNiveau3($request->request->get('_complementAvanceNiveau3'));
                $complementaire->setAnneeAvanceNiveau3((int) $request->request->get('_complementAvanceNiveau3Annee'));

                $complementaire->setStageAvanceNiveau4($request->request->get('_complementAvanceNiveau4'));
                $complementaire->setAnneeAvanceNiveau4((int) $request->request->get('_complementAvanceNiveau4Annee'));
            }else{
                $complementaire
                    ->setStageBaseNiveau1(null)->setAnneeBaseNiveau1(null)
                    ->setStageBaseNiveau2(null)->setAnneeBaseNiveau2(null)
                    ->setStageAvanceNiveau1(null)->setAnneeAvanceNiveau1(null)
                    ->setStageAvanceNiveau2(null)->setAnneeAvanceNiveau2(null)
                    ->setStageAvanceNiveau3(null)->setAnneeAvanceNiveau3(null)
                    ->setStageAvanceNiveau4(null)->setAnneeAvanceNiveau4(null)
                ;
            }

            $complementaire->setScout($scout);
            $complementaire->setUpdatedAt(new \DateTimeImmutable());

            $this->entityManager->persist($complementaire);
            $this->entityManager->flush();

            if ($request->isXmlHttpRequest()){
                return $this->json([
                    'status' => 'success',
                    'message' => 'Informations enregistrées sur le serveur',
                    'data' => [
                        'profil_infocomplementaire' => [
                            'id' => $complementaire->getId(),
                            'scout_id' => $scout->getId(),
                            'branche' => $complementaire->getBranche(),
                            'isFormation' => $complementaire->isFormation(),
                            'stageBaseNiveau1' => $complementaire->getStageBaseNiveau1(),
                            'anneeBaseNiveau1' => $complementaire->getAnneeBaseNiveau1(),
                            'stageBaseNiveau2' => $complementaire->getStageBaseNiveau2(),
                            'anneeBaseNiveau2' => $complementaire->getAnneeBaseNiveau2(),
                            'stageAvanceNiveau1' => $complementaire->getStageAvanceNiveau1(),
                            'anneeAvanceNiveau1' => $complementaire->getAnneeAvanceNiveau1(),
                            'stageAvanceNiveau2' => $complementaire->getStageAvanceNiveau2(),
                            'anneeAvanceNiveau2' => $complementaire->getAnneeAvanceNiveau2(),
                            'stageAvanceNiveau3' => $complementaire->getStageAvanceNiveau3(),
                            'anneeAvanceNiveau3' => $complementaire->getAnneeAvanceNiveau3(),
                            'stageAvanceNiveau4' => $complementaire->getStageAvanceNiveau4(),
                            'anneeAvanceNiveau4' => $complementaire->getAnneeAvanceNiveau4(),
                            'updatedAt' => $complementaire->getUpdatedAt(),
                        ]
                    ],
                    'redirect' => $this->generateUrl('app_profil_infos_complementaires_adulte')
                ]);
            }
        }

        return $this->render('profil/infos_complementaires_edit.html.twig',[
            'scout' => $scout,
            'complementaire' => $complementaire,
            'branches' => Branche::cases(),
            'stage_base_niveau1' => StageFormation::stageBaseNiveau1(),
            'stage_base_niveau2' => StageFormation::stageBaseNiveau2(),
            'stage_avance_niveau1' => StageFormation::stageAvanceNiveau1(),
            'stage_avance_niveau2' => StageFormation::stageAvanceNiveau2(),
            'stage_avance_niveau3'=> StageFormation::stageAvanceNiveau3(),
            'stage_avance_niveau4' => StageFormation::stageAvanceNiveau4()
        ]);
    }
}
