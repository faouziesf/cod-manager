<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            $table->integer('standard_max_daily_attempts')->default(3);
            $table->integer('standard_max_total_attempts')->default(9);
            $table->decimal('standard_attempts_delay', 5, 2)->default(2.5); // en heures
            $table->integer('dated_max_daily_attempts')->default(2);
            $table->integer('dated_max_total_attempts')->default(5);
            $table->decimal('dated_attempts_delay', 5, 2)->default(3.5); // en heures
            $table->decimal('old_attempts_delay', 5, 2)->default(2.5); // en heures
            $table->boolean('public_registration')->default(false);
            $table->integer('trial_days')->default(15);
            $table->integer('max_managers')->default(5);
            $table->integer('max_employees')->default(20);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('settings');
    }
};