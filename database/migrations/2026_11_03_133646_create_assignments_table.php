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
    Schema::create('assignments', function (Blueprint $table) {
      $table->id();
      $table->string('title');
      $table->text('description')->nullable();
      $table->dateTime('started_at');
      $table->dateTime('ended_at');
      $table->boolean('allow_overtime')->default(false);
      $table->foreignId('classroom_id')->constrained('classrooms')->onDelete('cascade');
      $table->foreignId('section_id')->nullable()->constrained('sections')->onDelete('set null');
      $table->integer('order')->nullable();
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('assignments');
  }
};
