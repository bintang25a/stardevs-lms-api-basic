<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
  protected $fillable = [
    'title',
    'announcement',
    'classroom_id',
    'section_id',
    'order',
  ];

  public function classroom(): BelongsTo
  {
    return $this->belongsTo(Classroom::class);
  }
}
