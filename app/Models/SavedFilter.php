<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavedFilter extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'filters',
        'is_shared',
    ];

    protected $casts = [
        'filters' => 'array',
        'is_shared' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}