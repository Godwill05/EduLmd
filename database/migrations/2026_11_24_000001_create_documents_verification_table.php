<?php

use App\Support\Tables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registre des documents émis, pour la vérification publique.
 * ---------------------------------------------------------------------
 * Chaque attestation ou certificat imprimé reçoit un code opaque
 * (ex. « A7K2-9XQ4-B3M8 ») imprimé en QR code sur le document. Un tiers
 * scanne le code et arrive sur une page publique qui affiche :
 *
 *   - le titulaire, le cycle, la mention, la date de délivrance ;
 *   - l'état du document : valide, révoqué ;
 *   - l'établissement émetteur.
 *
 * Le code est aléatoire et non devinable : il ne révèle pas le matricule,
 * et il est impossible d'énumérer les documents en incrémentant un
 * identifiant. Le matricule reste en base pour l'usage interne, mais n'est
 * jamais exposé sur la page publique.
 *
 * Idempotente : la migration ne crée la table que si elle manque.
 */
class CreateDocumentsVerificationTable extends Migration
{
    public function up()
    {
        if (Tables::existe('documents_verification')) {
            return;
        }

        Schema::create('documents_verification', function (Blueprint $table) {
            $table->id();

            /* Code public imprimé en QR. Unique évidemment : c'est la clé
               de recherche de la page de vérification. */
            $table->string('code', 20)->unique();

            /* attestation | certificat — sert à afficher le bon intitulé. */
            $table->string('type_document', 20);

            /* Le numéro de référence humain déjà présent sur le document
               (« N° 2026/0123/IUSAA/DA »). Il est affiché pour que le
               vérificateur puisse le comparer à celui du papier. */
            $table->string('reference', 100)->nullable();

            $table->string('matricule', 50)->index();
            $table->string('nom_complet', 150);
            $table->string('date_nais', 20)->nullable();
            $table->string('lieu_nais', 100)->nullable();

            $table->string('cycle', 100)->nullable();
            $table->string('mention', 100)->nullable();
            $table->string('specialite', 150)->nullable();
            $table->string('session_etude', 50)->nullable();

            $table->string('lieu_delivrance', 100)->nullable();
            $table->string('date_delivrance', 20)->nullable();

            /* Établissement au moment de l'émission : figé ici pour que la
               vérification reste exacte même si l'en-tête change plus tard. */
            $table->string('etablissement', 200)->nullable();

            /* Valide par défaut. Une révocation (fraude, erreur) laisse une
               trace motivée au lieu de supprimer la ligne : le QR scanné
               doit continuer à répondre « révoqué », pas « inconnu ». */
            $table->string('statut', 20)->default('valide');
            $table->string('motif_revocation', 255)->nullable();
            $table->timestamp('revoque_le')->nullable();

            /* Qui a imprimé, et combien de fois. Un document réimprimé
               plusieurs fois garde le même code : c'est le même diplôme. */
            $table->string('emis_par', 100)->nullable();
            $table->unsignedInteger('nb_impressions')->default(1);

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('documents_verification');
    }
}
