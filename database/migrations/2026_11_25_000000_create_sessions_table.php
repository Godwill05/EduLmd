<?php

use App\Support\Tables;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Table des sessions.
 * ---------------------------------------------------------------------
 * Le pilote de session « database » évite de perdre les connexions quand
 * l'application tourne derrière plusieurs instances, ou quand le système
 * de fichiers n'est pas partagé (c'est le cas en conteneur : le dossier
 * `storage/framework/sessions` disparaît au redémarrage, et tout le monde
 * est déconnecté d'un coup).
 *
 * Idempotente : si la table existe déjà — installation neuve, ou base
 * importée depuis une autre machine — la migration ne fait rien.
 */
class CreateSessionsTable extends Migration
{
    public function up()
    {
        if (Tables::existe('sessions')) {
            return;
        }

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down()
    {
        Schema::dropIfExists('sessions');
    }
}
