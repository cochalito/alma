<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->enum('document_type', ['1', '2', '3', '4'])->nullable()->change();
            $table->string('document_number', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->enum('document_type', ['1', '2', '3', '4'])->nullable(false)->change();
            $table->string('document_number', 255)->nullable(false)->change();
        });
    }
};
