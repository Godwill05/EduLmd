<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paramètres système modifiables depuis l'interface (« À propos »).
 *
 * Une ligne = une constante : clé technique stable, libellé affiché,
 * valeur libre et groupe d'affichage. On stocke tout en texte et on
 * convertit à la lecture, ce qui évite de figer les types dans le schéma
 * alors que ces valeurs changeront souvent.
 */
class CreateParametresSystemeTable extends Migration
{
    public function up()
    {
if (! \App\Support\Tables::existe('parametres_systeme')) {
                Schema::create('parametres_systeme', function (Blueprint $table) {
                $table->id();
                $table->string('cle', 100)->unique();
                $table->string('libelle', 150);
                $table->text('valeur')->nullable();
                $table->string('groupe', 60)->default('etablissement');
                $table->string('type', 30)->default('texte');
                $table->string('aide', 200)->nullable();
                $table->unsignedInteger('ordre')->default(0);
                $table->timestamps();

                $table->index('groupe');
            });
        }

        /* Défensif : sur une base déjà initialisée (dump SQL importé),
           les valeurs peuvent exister avant la première exécution. */
        if (\Illuminate\Support\Facades\DB::table('parametres_systeme')->count() === 0) {
            $this->semerLesValeursParDefaut();
        }
    }

    public function down()
    {
        Schema::dropIfExists('parametres_systeme');
    }

    /**
     * Les valeurs par défaut reprennent celles qui étaient codées en dur
     * dans la page « À propos », plus les champs de l'établissement dont
     * les impressions ont besoin.
     */
    private function semerLesValeursParDefaut(): void
    {
        $maintenant = now();

        /* Les coordonnées réelles sont déjà en base dans la table `ufr`
           (import du dump SQL) : on reprend ces valeurs plutôt que des
           exemples fictifs, sinon la page « À propos » afficherait de
           fausses coordonnées. */
        $ufr = \Illuminate\Support\Facades\DB::table('ufr')->first();
        $tel = $ufr->Tel ?? '';
        $mail = $ufr->mail ?? '';
        $site = $ufr->Site_web ?? '';
        $ville = $ufr->ville ?? '';
        $adresse = $ufr->Adres_Post ?? '';

        $parametres = [
            // Groupe : établissement
            ['etablissement.nom',      'Nom de l\'établissement',                $ufr->Ecol_Ufr ?? 'EduLMD', 'etablissement', 'texte', 'Apparaît en en-tête des documents imprimés.', 1],
            ['etablissement.sigle',    'Sigle',                                  'EduLMD', 'etablissement', 'texte', 'Nom court utilisé dans les titres.', 2],
            ['etablissement.adresse',  'Adresse',                                $adresse, 'etablissement', 'texte', null, 3],
            ['etablissement.ville',    'Ville',                                  $ville, 'etablissement', 'texte', null, 4],
            ['etablissement.pays',     'Pays',                                   'Bénin', 'etablissement', 'texte', null, 5],
            ['etablissement.devise',   'Devise',                                 'FCFA', 'etablissement', 'texte', 'Affichée à côté des montants.', 6],

            // Groupe : contacts
            ['contact.telephone_1',    'Téléphone principal',                    $tel, 'contact', 'tel', null, 10],
            ['contact.telephone_2',    'Téléphone secondaire',                   '', 'contact', 'tel', null, 11],
            ['contact.telephone_3',    'Téléphone 3',                            '', 'contact', 'tel', null, 12],
            ['contact.email_1',        'E-mail principal',                       $mail, 'contact', 'email', null, 13],
            ['contact.email_2',        'E-mail secondaire',                      '', 'contact', 'email', null, 14],
            ['contact.email_3',        'E-mail 3',                               '', 'contact', 'email', null, 15],
            ['contact.site_web',       'Site web',                               $site, 'contact', 'url', null, 16],

            // Groupe : direction
            ['direction.recteur',      'Recteur / Directeur',                    $ufr->directeur ?? '', 'direction', 'texte', 'Signataire des diplômes et attestations.', 20],
            ['direction.doyen',        'Doyen',                                  '', 'direction', 'texte', null, 21],
            ['direction.rh',           'Responsable des ressources humaines',    $ufr->directeurAdjoint ?? '', 'direction', 'texte', null, 22],
            ['direction.pdg',          'Président-directeur général',            '', 'direction', 'texte', null, 23],
            ['direction.secretariat',  'Secrétariat académique',                 '', 'direction', 'texte', null, 24],

            // Groupe : académique
            ['academique.annee_en_cours', 'Année académique en cours', '2025-2026', 'academique', 'texte', 'Utilisée par défaut quand un écran ne précise pas l\'année.', 30],
            ['academique.note_eliminatoire', 'Note éliminatoire', '0', 'academique', 'nombre', 'Toute note inférieure ou égale est éliminatoire.', 31],
            ['academique.note_max',     'Note maximale',                         '20', 'academique', 'nombre', null, 32],
            ['academique.credits_grade', 'Crédits requis pour un grade',         '180', 'academique', 'nombre', null, 33],
        ];

        $lignes = [];
        foreach ($parametres as [$cle, $libelle, $valeur, $groupe, $type, $aide, $ordre]) {
            $lignes[] = [
                'cle' => $cle,
                'libelle' => $libelle,
                'valeur' => $valeur,
                'groupe' => $groupe,
                'type' => $type,
                'aide' => $aide,
                'ordre' => $ordre,
                'created_at' => $maintenant,
                'updated_at' => $maintenant,
            ];
        }

        \Illuminate\Support\Facades\DB::table('parametres_systeme')->insert($lignes);
    }
}
