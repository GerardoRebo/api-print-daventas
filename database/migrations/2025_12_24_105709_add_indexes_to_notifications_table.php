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
        Schema::table('notifications', function (Blueprint $table) {
            // Índice para getCountNotf() y getNotifications()
            // Busca notificaciones no leídas por usuario
            $table->index(['notifiable_id', 'notifiable_type', 'read_at'], 'idx_unread_notifications');

            // Índice para getAllNotifications() - filtro por fecha
            $table->index(['notifiable_id', 'notifiable_type', 'created_at'], 'idx_notifications_by_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notifications', function (Blueprint $table) {
            $table->dropIndex('idx_unread_notifications');
            $table->dropIndex('idx_notifications_by_date');
        });
    }
};
