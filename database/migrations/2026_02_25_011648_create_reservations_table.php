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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id');
            $table->foreignId('departament_id');
            $table->foreignId('customer_id');
            $table->string('location', 10)->default('LA PAZ');
            $table->dateTime('check_in');
            $table->dateTime('check_out');
            $table->double('total_stay_cost');
            $table->double('total_extra_cost');
            $table->mediumText('requests')->nullable();
            $table->mediumText('comments')->nullable();
            $table->enum('status', ['1', '2', '3', '4'])->comment('1 Confirmado, 2 Check In, 3 Check Out, 4 Cancelado');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
