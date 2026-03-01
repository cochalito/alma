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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->mediumText('description')->nullable();
            $table->enum('category', ['1', '2', '3', '4'])->comment('1 Bebidas, 2 Snaks, 3 Articulos de baño, 4 Otros');
            $table->bigInteger('stock');
            $table->double('price');
            $table->enum('status', ['1', '2'])->comment('1 Activo, 2 Inactivo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
