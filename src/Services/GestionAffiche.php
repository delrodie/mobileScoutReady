<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\AsciiSlugger;

class GestionAffiche
{
    private $mediaAffiche;
    public function __construct(
        $afficheDirectory
    )
    {
        $this->mediaAffiche = $afficheDirectory;
    }

    /**
     * @param $form
     * @param object $entity
     * @return void
     */
    public function media($form, object $entity): void
    {
        // Gestion des médias
        $mediaFile = $form->get('affiche')->getData();
        if ($mediaFile){
            $media = $this->upload($mediaFile);

//            if ($entity->getAffiche()){
//                $this->removeUpload($entity->getAffiche());
//            }

            $entity->setAffiche($media);
        }
    }

    /**
     * @param UploadedFile $file
     * @param $media
     * @return string
     */
    public function upload(UploadedFile $file): string
    {
        // Initialisation du slug
        $slugify = new AsciiSlugger();

        $originalFileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugify->slug(strtolower($originalFileName));
        $newFilename = $safeFilename.'-'.Time().'.'.$file->guessExtension();

        // Deplacement du fichier dans le repertoire dedié
        try {
            $file->move($this->mediaAffiche, $newFilename);
        }catch (FileException $e){

        }

        return $newFilename;
    }

    /**
     * Suppression de l'ancien media sur le server
     *
     * @param $ancienMedia
     * @param null $media
     * @return bool
     */
    public function removeUpload($ancienMedia): bool
    {
        unlink($this->mediaAffiche.'/'.$ancienMedia);

        return true;
    }
}
