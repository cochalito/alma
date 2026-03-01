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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->enum('document_type', ['1', '2', '3', '4'])->comment('1 CI, 2 DNI, 3 Pasaporte, 4 Otro');
            $table->string('document_number', 255);
            $table->string('firstname', 255);
            $table->string('lastname', 255);
            $table->string('email', 150);
            $table->string('cellphone', 50);
            $table->enum('status', ['0', '1'])->comment('0 Inactivo, 1 Activo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
