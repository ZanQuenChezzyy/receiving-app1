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
        Schema::create('goods_receipt_slip_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goods_receipt_slip_id')->constrained('goods_receipt_slips')->cascadeOnDelete();
            $table->unsignedBigInteger('delivery_order_receipt_detail_id');
            $table->unsignedMediumInteger('item_no');
            $table->string('quantity', 10);
            $table->string('material_code', 20)->nullable();
            $table->text('description');
            $table->string('uoi', 5)->nullable();
            $table->timestamps();

            $table->foreign('delivery_order_receipt_detail_id', 'grs_detail_do_detail_fk')
                ->references('id')
                ->on('delivery_order_receipt_details')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_slip_details');
    }
};
