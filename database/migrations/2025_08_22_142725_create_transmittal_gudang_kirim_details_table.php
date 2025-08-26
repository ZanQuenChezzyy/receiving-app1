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
        Schema::create('transmittal_gudang_kirim_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('transmittal_gudang_kirim_id');
            $table->unsignedBigInteger('goods_receipt_slip_detail_id');
            $table->tinyInteger('item_no');
            $table->integer('quantity');
            $table->string('material_code', 20)->nullable();
            $table->text('description');
            $table->string('uoi', 5);
            $table->timestamps();

            $table->foreign('transmittal_gudang_kirim_id', 'fk_tgkd_tgk')
                ->references('id')
                ->on('transmittal_gudang_kirims')
                ->onDelete('cascade');

            $table->foreign('goods_receipt_slip_detail_id', 'fk_tgkd_grsd')
                ->references('id')
                ->on('goods_receipt_slip_details')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transmittal_gudang_kirim_details');
    }
};
