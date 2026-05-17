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
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->string('attachable_type'); // driver_profiles, vehicles, service_payments
            $table->unsignedBigInteger('attachable_id');
            $table->unsignedBigInteger('attachment_type_id')->nullable(); // riferimento a driver_attachment_types o vehicle_attachment_types
            $table->string('file_name');
            $table->string('file_path');
            $table->integer('file_size')->nullable();
            $table->string('mime_type')->nullable();
            $table->date('expiry_date')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['attachable_type', 'attachable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
