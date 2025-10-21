<?php

namespace App\Service;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

class GestionQrCode
{
    private string $qrCodeDirectory;
    public function __construct(
        string $qrCodeDirectory,
    )
    {
        $this->qrCodeDirectory = $qrCodeDirectory;
    }

    /**
     * Generation du qrCode du scout
     * @param string $code
     * @return string
     */
    public function qrCodeGenerator(string $qrCodeToken, string $code): string
    {
        $builder = new Builder(
            writer: new PngWriter(),
            data: $qrCodeToken,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 350,
            margin: 25,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
//            labelText: 'ScoutReady',
            logoPath: __DIR__.'/../../public/icon/icon-bw.png',
            logoResizeToWidth: 60,
            logoPunchoutBackground: false
        );

        $result = $builder->build();
        $filename = $code.'.png';
        $path = $this->qrCodeDirectory.'/'.$filename;
        $result->saveToFile($path);

        return $filename;
    }
}
