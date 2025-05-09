<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('country')->default('Tunisie');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('regions');
    }
};