<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->date('slot_date');
            $table->time('slot_time');
            $table->integer('max_bookings')->default(5);
            $table->integer('current_bookings')->default(0);
            $table->boolean('is_available')->default(true);
            $table->timestamps();

            $table->unique(['store_id', 'slot_date', 'slot_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_slots');
    }
};
