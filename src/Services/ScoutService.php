<?php

namespace App\Services;

use App\Repository\ScoutRepository;

class ScoutService
{
    public function __construct(
        private readonly ScoutRepository $scoutRepository
    )
    {
    }

    public function generateCode(?string $statut): string
    {
        //CF2504182554-A4 SC2504186547-3C
        $prefix = $statut === 'ADULTE' ? 'CF' : 'SC';
        do{
            $variable = str_pad((int)random_int(0, 9999), 4, '0', STR_PAD_LEFT);
//            $unique = strtoupper(bin2hex($variable));
            $base = $prefix.date('ymd').$variable;
            $checksum = strtoupper(substr(hash('crc32b', $base), 0, 2));
            $code = $base.'-'.$checksum;
        }while($this->scoutRepository->findOneBy(['code' => $code]));

        return $code;
    }
}
