<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone1');
            $table->string('phone2')->nullable();
            $table->string('country')->default('Tunisie');
            $table->string('region');
            $table->string('city')->nullable();
            $table->text('address')->nullable();
            $table->enum('status', ['standard', 'confirmed', 'canceled', 'dated', 'old'])->default('standard');
            $table->decimal('total_price', 10, 3);
            $table->decimal('confirmed_price', 10, 3)->nullable();
            $table->integer('attempts')->default(0);
            $table->integer('daily_attempts')->default(0);
            $table->date('scheduled_date')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
};