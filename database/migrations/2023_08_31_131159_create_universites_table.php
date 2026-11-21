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
if (! \App\Support\Tables::existe('universites')) {
                Schema::create('universites', function (Blueprint $table) {
                $table->id();
                $table->string('nom');
                $table->string('adressePostale');
                $table->string('telephone');
                $table->string('email');
                $table->string('siteWeb');
                $table->string('observations');
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
        Schema::dropIfExists('universites');
    }
};
