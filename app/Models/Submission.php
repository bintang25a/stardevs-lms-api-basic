<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Submission extends Model
{
  protected $fillable = [
    'student_name',
    'student_id',
    'answer',
    'support',
    'score',
    'submitted',
    'classroom_id',
    'assignment_id',
    'section_id',
    'order',
  ];

  public function classroom(): BelongsTo
  {
    return $this->belongsTo(Classroom::class);
  }

  public function assignment(): BelongsTo
  {
    return $this->belongsTo(Assignment::class);
  }
}
