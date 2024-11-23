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
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone_number');
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->nullable();
            $table->string('identification_type')->nullable(); // passport, ID card, etc.
            $table->string('identification_number')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->text('special_requests')->nullable();
            $table->timestamps();
        });

        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->onDelete('cascade');
            $table->foreignId('guest_id')->constrained()->onDelete('cascade');
            $table->dateTime('check_in');
            $table->dateTime('check_out');
            $table->integer('number_of_guests');
            $table->decimal('total_price', 10, 2);
            $table->enum('status', ['confirmed', 'checked_in', 'checked_out', 'cancelled'])
                  ->default('confirmed');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guests');
        Schema::dropIfExists('guests');
    }
};
