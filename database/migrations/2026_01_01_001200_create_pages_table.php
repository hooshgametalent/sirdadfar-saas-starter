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
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('type', 20)->default('static')->index();
            $table->foreignId('origin_city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->foreignId('destination_city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->longText('body');
            $table->string('status', 20)->default('draft')->index();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
