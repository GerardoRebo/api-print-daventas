<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('telegram_configs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger('organization_id')->nullable();
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');

            $table->string('telegram_bot_token')->nullable();
            $table->string('telegram_chat_id')->nullable();
            $table->string('telegram_username')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('notify_delivery_today')->default(true)->comment('Enviar notificaciones a las 10 AM de entregas hoy');
            $table->boolean('notify_delivery_tomorrow')->default(true)->comment('Enviar notificaciones a las 7 PM de entregas mañana');

            $table->timestamps();

            $table->index('user_id');
            $table->index('organization_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_configs');
    }
};
