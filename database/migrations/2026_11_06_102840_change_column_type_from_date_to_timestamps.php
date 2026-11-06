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
    DB::statement('ALTER TABLE sections ALTER COLUMN date TYPE TIMESTAMP USING date::timestamp');
    DB::statement('ALTER TABLE classrooms ALTER COLUMN schedule TYPE TIMESTAMP USING schedule::timestamp');
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    DB::statement('ALTER TABLE sections ALTER COLUMN date TYPE DATE USING date::date');
    DB::statement('ALTER TABLE classrooms ALTER COLUMN schedule TYPE DATE USING schedule::date');
  }
};
