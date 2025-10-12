<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Services\InstanceImportService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/import')]
class AdminImportExcelController extends AbstractController
{
    #[Route('/', name: 'admin_import_excel_instances', methods: ['GET','POST'])]
    public function instance(Request $request, InstanceImportService $importService): Response
    {
        if (
            $request->isMethod('POST') &&
            $this->isCsrfTokenValid('instanceImported', $request->getPayload()->getString('_instanceCsrfToken'))
        ){
            $file = $request->files->get('instance_file');

            if (!$file || $file->getClientOriginalExtension() !== 'xlsx') {
                $this->addFlash("error", "Échec, Veuillez uploader un fichierExcel (.xlsx)");
                return $this->redirectToRoute('admin_import_excel_instances');
            }

            try{
                $result = $importService->import($file);
                $this->addFlash('success', "Importation terminée: {$result['imported']} instances ajoutées, {$result['skipped']} ignorées");

                if(!empty($result['errors'])){
                    foreach ($result['errors'] as $error){
                        $this->addFlash("error", $error);
                    }
                }

                return $this->redirectToRoute('admin_instance_index');
            } catch(\Throwable $e){
                $this->addFlash("error", "Erreur d'importation: {$e->getMessage()}");
            }

        }
        return $this->render('admin/instance_import.html.twig', [
            'pageTitle' => 'Importation des instances',
        ]);
    }
}
