<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
  /**
   * Run the migrations.
   */
  public function up(): void
  {
    DB::statement('ALTER TABLE assignments ALTER COLUMN started_at TYPE TIMESTAMP USING started_at::timestamp');
    DB::statement('ALTER TABLE assignments ALTER COLUMN ended_at TYPE TIMESTAMP USING ended_at::timestamp');
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    DB::statement('ALTER TABLE assignments ALTER COLUMN started_at TYPE DATE USING started_at::date');
    DB::statement('ALTER TABLE assignments ALTER COLUMN ended_at TYPE DATE USING ended_at::date');
  }
};
