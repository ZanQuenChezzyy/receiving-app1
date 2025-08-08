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
        Schema::create('approval_vp_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approval_vp_id')->constrained('approval_vps')->cascadeOnDelete();
            $table->string('code', 50);
            $table->unsignedTinyInteger('document_type')->comment('105: Goods Receipt Slip, 124: Return Delivery to Vendor');
            $table->foreignId('goods_receipt_slip_id')->nullable()->constrained('goods_receipt_slips')->cascadeOnDelete();
            $table->foreignId('return_delivery_to_vendor_id')->nullable()->constrained('return_delivery_to_vendors')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_vp_details');
    }
};
