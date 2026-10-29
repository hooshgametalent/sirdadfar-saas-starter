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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 40)->unique();
            $table->foreignId('shipper_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('origin_city_id')->constrained('cities')->restrictOnDelete();
            $table->foreignId('destination_city_id')->constrained('cities')->restrictOnDelete();
            $table->string('origin_address', 500);
            $table->string('destination_address', 500);
            $table->string('cargo_type', 150);
            $table->unsignedInteger('weight_kg');
            $table->decimal('volume_m3', 8, 2)->nullable();
            $table->date('pickup_date');
            $table->time('pickup_window_start')->nullable();
            $table->time('pickup_window_end')->nullable();
            $table->foreignId('vehicle_type_id')->constrained()->restrictOnDelete();
            $table->json('special_requirements')->nullable();
            $table->unsignedBigInteger('offered_price');
            $table->unsignedBigInteger('final_price')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 32)->default('pending_review')->index();
            $table->foreignId('assigned_driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('accepted_offer_id')->nullable()->index();
            $table->text('cancelled_reason')->nullable();
            $table->timestamps();

            $table->index(['shipper_id', 'status']);
            $table->index(['origin_city_id', 'destination_city_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
