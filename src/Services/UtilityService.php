<?php

namespace App\Services;

use App\Enum\ScoutStatut;
use Symfony\Component\Uid\Uuid;

class UtilityService
{
    public function calculAge(string|\DateTimeInterface $dateNaissance): ?int
    {

        if ($dateNaissance instanceof \DateTimeInterface) {
            $naissance = $dateNaissance;
        } else {
            $formats = ['d/m/Y', 'Y-m-d', 'd-m-Y'];
            $naissance = null;

            foreach ($formats as $format) {
                $naissance = \DateTime::createFromFormat($format, trim($dateNaissance));
                if ($naissance !== false) break;
            }

            if (!$naissance) {
                throw new \InvalidArgumentException("Format de date invalide : '$dateNaissance'.");
            }
        }


        return (new \DateTime())->diff($naissance)->y;
    }


    public function avatar(string|\DateTimeInterface $dateNaissance, string $genre): string
    {
        $age = $this->calculAge($dateNaissance);
        $genre = strtoupper(trim($genre));

        $estMineur = $age < 18;
        $estHomme = $genre === 'HOMME';

        return $estMineur
            ? ($estHomme ? 'Garçon' : 'Fille')
            : ($estHomme ? 'Homme' : 'Femme');
    }

    public function getAvatarFile($dateNaiss, $genre): string
    {
        $type = $this->avatar($dateNaiss, $genre);
        return match ($type){
            'Garçon' => '/avatar/avatar_garcon.png',
            'Fille' => '/avatar/avatar_fille.png',
            'Homme' => '/avatar/avatar_homme.png',
            'Femme' => '/avatar/avatar_femme.png',
            default => '/avatar/garcon.png',
        };
    }



    public function annee(): string
    {
        $anneeEncours = (int) Date('Y');
        $moisEncours = (int) Date('m');

        $debutAnnee = $moisEncours > 9 ? $anneeEncours : $anneeEncours - 1;
        $finAnnee = $moisEncours > 9 ? $anneeEncours + 1 : $anneeEncours;

        return sprintf('%d-%d', $debutAnnee, $finAnnee);
    }

    /**
     * @param $str
     * @return string
     */
    public function validForm($str): string
    {
        return htmlspecialchars(stripslashes(trim($str)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public function convertSlugToUuid(string $slug): ?Uuid
    {
        if (strlen($slug) === 22 && preg_match('/^[A-Za-z0-9]+$/', $slug)) {
            return Uuid::fromBase58($slug); // WRafnMeh7EgKFaLuaZVtv9
        }
        if (Uuid::isValid($slug)) {
            return Uuid::fromString($slug);
        }
        return null;
    }
}
