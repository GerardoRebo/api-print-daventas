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
        Schema::create('payment_intents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('organization_id')->nullable()->constrained()->onDelete('cascade');
            $table->morphs('payable'); // Crea payable_id y payable_type
            $table->string('status')->default('pending'); // pending, approved, failed
            $table->string('payment_id')->nullable(); // ID devuelto por MP
            $table->string('collection_status')->nullable(); // approved, rejected...
            $table->string('payment_type')->nullable();
            $table->string('merchant_order_id')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_intents');
    }
};
