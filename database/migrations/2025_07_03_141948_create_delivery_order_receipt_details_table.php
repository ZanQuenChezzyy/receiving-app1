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
        Schema::create('delivery_order_receipt_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_receipt_id')->constrained('delivery_order_receipts')->cascadeOnDelete();
            $table->unsignedTinyInteger('item_no');
            $table->string('quantity', 10);
            $table->string('material_code')->nullable();
            $table->text('description');
            $table->string('uoi', 5)->nullable();
            $table->boolean('is_different_location')->default(false);
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_order_receipt_details');
    }
};
