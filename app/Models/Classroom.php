<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classroom extends Model
{
  use HasFactory;

  protected $fillable = [
    'name',
    'code',
    'description',
    'schedule',
  ];

  public function users()
  {
    return $this->belongsToMany(User::class, 'classroom_user')
      ->withPivot('role')
      ->withTimestamps();
  }

  public function assistants()
  {
    return $this->belongsToMany(User::class, 'classroom_user')
      ->wherePivot('role', 'asisten')
      ->withTimestamps();
  }

  public function students()
  {
    return $this->belongsToMany(User::class, 'classroom_user')
      ->wherePivot('role', 'praktikan')
      ->withTimestamps();
  }

  public function sections(): HasMany
  {
    return $this->hasMany(Section::class);
  }
}
