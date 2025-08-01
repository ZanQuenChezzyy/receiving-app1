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
        Schema::create('return_delivery_to_vendor_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('return_delivery_to_vendor_id')->constrained('return_delivery_to_vendors')->cascadeOnDelete()->name('fk_rdtv_details_vendor');
            $table->unsignedMediumInteger('item_no');
            $table->string('quantity', 10);
            $table->string('material_code', 20)->nullable();
            $table->text('description');
            $table->string('uoi', 5)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('return_delivery_to_vendor_details');
    }
};
