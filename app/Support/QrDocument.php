<?php

namespace App\Support;

/**
 * Impression du QR code de vérification sur un PDF Html2Pdf.
 * ---------------------------------------------------------------------
 * Pourquoi pas dans le HTML ? Html2Pdf ne sait pas dessiner de code-barres.
 * En revanche la classe qu'il utilise en interne (`Spipu\Html2Pdf\MyPdf`)
 * hérite de TCPDF, qui sait le faire. On récupère donc l'objet TCPDF sous
 * le HTML déjà rendu, et on pose le QR à des coordonnées absolues.
 *
 * Avantage : le QR ne participe pas à la mise en page. Il ne peut pas
 * faire déborder une attestation sur une deuxième page, contrairement à
 * une image ou un bloc ajouté au flux.
 *
 * Coordonnées en millimètres, origine en haut à gauche de la page.
 */
class QrDocument
{
    /** Côté du carré, en millimètres. 22 mm reste lisible par un téléphone
     *  d'entrée de gamme, sans empiéter sur le pied de page. */
    private const TAILLE = 22;

    /**
     * Pose un QR code sur chacune des pages du document.
     *
     * @param  object  $html2pdf  instance de Spipu\Html2Pdf\Html2Pdf, après writeHTML()
     * @param  string  $code      code de vérification (A7K2-9XQ4-B3M8)
     * @param  array<int, array{x: float, y: float}>  $positions  une position par page
     * @return bool  false si le QR n'a pas pu être posé (le PDF reste valable)
     */
    public static function poser($html2pdf, string $code, array $positions): bool
    {
        if ($code === '' || empty($positions)) {
            return false;
        }

        try {
            $tcpdf = $html2pdf->pdf;

            // L'adresse complète : un tiers qui scanne doit tomber directement
            // sur la bonne page, sans avoir à recopier le code à la main.
            $url = DocumentsVerifiables::url($code);

            $nbPages = $tcpdf->getNumPages();

            foreach ($positions as $index => $position) {
                $noPage = $index + 1;

                if ($noPage > $nbPages) {
                    break;
                }

                $tcpdf->setPage($noPage);

                /* fgcolor : vert du cadre, pour que le QR s'accorde au
                   document (les lecteurs lisent indifféremment une teinte
                   sombre sur fond blanc). */
                $style = [
                    'border' => false,
                    'padding' => 0,
                    'fgcolor' => [46, 125, 83],
                    'bgcolor' => false,
                ];

                $tcpdf->write2DBarcode(
                    $url,
                    'QRCODE,M',
                    (float) $position['x'],
                    (float) $position['y'],
                    self::TAILLE,
                    self::TAILLE,
                    $style,
                    'N'
                );
            }

            return true;
        } catch (\Throwable $e) {
            /* Un QR manquant ne doit pas empêcher la remise du diplôme :
               le code reste lisible en clair sous le document. */
            \Illuminate\Support\Facades\Log::warning('QR code non posé', [
                'code' => $code,
                'erreur' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /** Côté du QR en millimètres (exposé pour caler les marges du HTML). */
    public static function taille(): int
    {
        return self::TAILLE;
    }
}
