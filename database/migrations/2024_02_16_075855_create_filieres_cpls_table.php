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
if (! \App\Support\Tables::existe('filiereCpls')) {
                Schema::create('filiereCpls', function (Blueprint $table) {
                $table->id();
                $table->string('nom');
                $table->string('annee1',25)->collation('latin1_swedish_ci');
                $table->string('annee2',25)->collation('latin1_swedish_ci');
                $table->string('annee3',25)->collation('latin1_swedish_ci')->nullable();
                $table->foreign('annee1')->references('Code_Annee_Etude')->on('annee_etude');
                $table->foreign('annee2')->references('Code_Annee_Etude')->on('annee_etude');
                $table->foreign('annee3')->references('Code_Annee_Etude')->on('annee_etude')->nullable();
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
        Schema::dropIfExists('filiereCpls');
    }
};
