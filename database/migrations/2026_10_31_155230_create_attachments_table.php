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
      $table->string('file_name')->nullable();
      $table->string('file_path')->nullable();
      $table->string('file_url')->nullable();
      $table->string('class_model');
      $table->string('class_id');
      $table->enum('type', ['material', 'assignment', 'submission']);
      $table->timestamps();
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
