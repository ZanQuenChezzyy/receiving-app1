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
        Schema::create('purchase_order_terbits', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_order_item', 20)->nullable();
            $table->string('purchase_order_no', 12);
            $table->unsignedMediumInteger('item_no');
            $table->string('material_code', 20)->nullable();
            $table->text('description');
            $table->string('quantity', 10)->default(0);
            $table->string('uoi', 5);
            $table->string('vendor_id', 20)->nullable();
            $table->string('vendor_id_name', 100);
            $table->date('date_created');
            $table->date('delivery_date')->nullable();
            $table->string('status', 2)->nullable();
            $table->string('incoterm', 100)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_terbits');
    }
};
