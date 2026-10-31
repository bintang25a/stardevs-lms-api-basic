<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  public function up(): void
  {
    Schema::create('classroom_user', function (Blueprint $table) {
      $table->id();
      $table->foreignId('classroom_id')->constrained('classrooms')->onDelete('cascade');
      $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
      $table->enum('role', ['asisten', 'praktikan']);
      $table->timestamps();

      $table->unique(['classroom_id', 'user_id', 'role']);
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('classroom_user');
  }
};
