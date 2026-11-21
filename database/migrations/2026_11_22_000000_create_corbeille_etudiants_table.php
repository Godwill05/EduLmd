<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Corbeille pour les fiches étudiantes.
 *
 * Supprimer une fiche étudiante touche 8 tables (inscriptions, paiements,
 * prévisions, recap_annuel, recap_sem, recap_ue, repartir, obtenir via les
 * inscriptions). Une suppression directe est irréversible et peut détruire
 * des notes ou des paiements.
 *
 * Cette table conserve donc une copie JSON complète de tout ce qui a été
 * retiré, ce qui rend la suppression réversible : on peut restaurer la fiche
 * telle qu'elle était, avec son parcours, ses notes et ses paiements.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('corbeille_etudiants', function (Blueprint $table) {
            $table->id();
            $table->string('MATRICULE', 50)->index();
            $table->string('nom_complet')->nullable();
            $table->string('motif')->nullable();
            $table->enum('type', ['doublon', 'manuel'])->default('manuel');
            $table->string('supprime_par')->nullable();
            $table->json('donnees');                 // copie complète, restaurable
            $table->unsignedInteger('nb_inscriptions')->default(0);
            $table->unsignedInteger('nb_paiements')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('corbeille_etudiants');
    }
};
