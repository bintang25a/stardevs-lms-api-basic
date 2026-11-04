<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Assignment extends Model
{
  protected $fillable = [
    'title',
    'description',
    'started_at',
    'ended_at',
    'allow_overtime',
    'classroom_id',
    'section_id',
    'order',
  ];

  public function classroom(): BelongsTo
  {
    return $this->belongsTo(Classroom::class);
  }
}
