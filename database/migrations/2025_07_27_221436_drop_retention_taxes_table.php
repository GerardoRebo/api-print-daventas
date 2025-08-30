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
        Schema::dropIfExists('ventaticket_retention_taxes'); // replace 'users' with your table name
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
