<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
if (! \App\Support\Tables::existe('previsions')) {
                Schema::create('previsions', function (Blueprint $table) {
                $table->id();
                $table->string('MATRICULE',25)->collation('latin1_swedish_ci');
                $table->integer('Id_Annee');
                $table->string('ID_FILIERE',25)->collation('latin1_swedish_ci');
                $table->foreign(['MATRICULE','Id_Annee','ID_FILIERE'])->references(['MATRICULE','Id_Annee','ID_FILIERE'])->on('inscriptions');
                $table->integer('montant_a_payer');
                $table->integer('reste_a_payer');
                $table->boolean('paye')->default(0);
                $table->date('echeance');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('previsions');
    }
};
