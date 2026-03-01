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
        Schema::create('departament', function (Blueprint $table) {
            $table->id();
            $table->string('location', 10);
            $table->string('code', 10);
            $table->enum('status', ['0', '1'])->comment('0 No Disponible, 1 Disponible');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('departament');
    }
};
