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
        Schema::create('transmittal_gudang_kirims', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->unsignedBigInteger('goods_receipt_slip_id');
            $table->date('tanggal_kirim');
            $table->unsignedBigInteger('warehouse_location_id');
            $table->unsignedBigInteger('dikirim_oleh'); // user receiving
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('goods_receipt_slip_id', 'fk_tgk_grs')
                ->references('id')
                ->on('goods_receipt_slips')
                ->onDelete('cascade');
            $table->foreign('warehouse_location_id', 'fk_tgk_location')
                ->references('id')
                ->on('warehouse_locations')
                ->onDelete('restrict');
            $table->foreign('dikirim_oleh', 'fk_tgk_user_kirim')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');
            $table->foreign('created_by', 'fk_tgk_user_created')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transmittal_gudang_kirims');
    }
};
