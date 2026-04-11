<?php

namespace App\Models;

use App\Enums\Channel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Guide extends Model
{
    use HasFactory;

    public const MAX_TITLE_LENGTH = 100;

    protected $table = 'guide';

    protected $fillable = [
        'title',
        'channel_nr',
        'starts_at',
        'ends_at'
    ];

    protected $casts = [
        'channel_nr' => Channel::class,
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];
}
