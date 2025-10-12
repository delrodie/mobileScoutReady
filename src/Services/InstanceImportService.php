<?php

namespace App\Services;

use App\Entity\Instance;
use App\Enum\InstanceType;
use App\Repository\InstanceRepository;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\File\UploadedFile;

readonly class InstanceImportService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private InstanceRepository     $instanceRepository,
    )
    {
    }

    public function import(UploadedFile $file): array
    {
        $spreadSheet = IOFactory::load($file->getPathname());
        $sheet = $spreadSheet->getActiveSheet();
        $rows = $sheet->toArray();

        $imported = 0;
        $skipped = 0;
        $errors = [];
        foreach ($rows as $index => $row) {
            // Ignorer la première ligne
            if ($index === 0) continue;

            [$type, $instanceParent, $nom, $sigle] = array_map('trim', $row) ;

            // Si le nom de l'instance est vide alors sauter
            if (empty($nom) || empty($type)){
                $errors[] = "Ligne " . ($index + 1) . " ignorée : champ 'type' ou 'nom' manquant.";
                $skipped++;
                continue;
            }

            // Verification de non-existence de l'instance
            if ($this->VerifInstance($type, $instanceParent, $nom, $sigle)){
                $skipped++;
                $errors[] = "Ligne " . ($index + 1) . " ignorée : l’instance '{$nom}' existe déjà.";
                continue;
            }


            try{
                $instance = new Instance();

                // Si le champ instanceParent est renseigné alors chercher l'ID du parent
                if (!empty($instanceParent)){ //dd($instanceParent);
                    if ($type === 'REGION'){
                        $parentEntity = $this->instanceRepository->findOneBy(['sigle' => $instanceParent]);
                    }else{
                        $parentEntity = $this->instanceRepository->findOneBy(['nom' => $instanceParent]);
                    }
                    $instance->setInstanceParent($parentEntity);
                }

                $instance->setNom($nom);
                $instance->setType($this->instanceTypeFormatted($type));
                $instance->setSigle($sigle);

                $this->entityManager->persist($instance);
                $this->entityManager->flush();
                $imported++;
            } catch(\Throwable $e){
                $skipped++;
                $errors[] = "Échec d'importation de l'instance {$nom} de la ligne ".($index+1)." : {$e->getMessage()}";
            }
        }


        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors
        ];
    }

    private function instanceTypeFormatted(mixed $type): ?InstanceType
    {
        return match ($type) {
            'nation', 'Nation', 'NATION' => InstanceType::NATION,
            'region', 'région', 'Région', 'REGION' => InstanceType::REGION,
            'district', 'District', 'DISTRICT' => InstanceType::DISTRICT,
            'groupe', 'Groupe', 'GROUPE' => InstanceType::GROUPE,
            default => null,
        };
    }

    private function VerifInstance(mixed $type , mixed $instanceParent = null, mixed $nom = null, mixed $sigle = null)
    {
        return match ($type) {
            'nation', 'Nation', 'NATION' => $this->instanceRepository->findOneBy(['type' => $type, 'nom' => $nom, 'sigle' => $sigle]),
            'region', 'région', 'Région', 'REGION', 'district', 'District', 'DISTRICT', 'groupe', 'Groupe', 'GROUPE'
            => $this->instanceRepository->findByQuery($instanceParent, $type, $nom),
            default => null,
        };
    }

}
