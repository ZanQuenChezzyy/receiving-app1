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
        Schema::create('material_issued_request_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('material_issued_request_id');
            $table->unsignedBigInteger('delivery_order_receipt_detail_id');
            $table->unsignedBigInteger('location_id')->nullable();
            $table->text('description');
            $table->unsignedMediumInteger('item_no');
            $table->string('stock_no', 50)->nullable();
            $table->integer('requested_qty');
            $table->integer('issued_qty');
            $table->string('uoi', 5);
            $table->timestamps();

            $table->foreign('material_issued_request_id', 'fk_mir_details_request')
                ->references('id')
                ->on('material_issued_requests')
                ->onDelete('cascade');

            $table->foreign('goods_receipt_slip_detail_id', 'fk_mir_details_grs')
                ->references('id')
                ->on('goods_receipt_slip_details')
                ->onDelete('cascade');

            $table->foreign('location_id', 'fk_mir_details_location')
                ->references('id')
                ->on('locations')
                ->onDelete('cascade');

            $table->foreign('delivery_order_receipt_detail_id', 'fk_mir_details_do_detail')
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
        Schema::dropIfExists('material_issued_request_details');
    }
};
