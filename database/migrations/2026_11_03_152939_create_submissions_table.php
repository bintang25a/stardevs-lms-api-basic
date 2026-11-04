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
    Schema::create('submissions', function (Blueprint $table) {
      $table->id();
      $table->string('student_name');
      $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
      $table->string('answer');
      $table->text('support')->nullable();
      $table->decimal('score')->nullable();
      $table->boolean('submitted')->default(false);
      $table->foreignId('assignment_id')->constrained()->constrained('assignments')->onDelete('cascade');
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
    Schema::dropIfExists('submissions');
  }
};
