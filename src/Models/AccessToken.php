<?php

namespace Salt\Firebase\Models;

use Illuminate\Database\Eloquent\Model;

class AccessToken extends Model
{
    protected $table = 'access_tokens';

    protected $fillable = [
        'name', 'token', 'refreshed_at',
    ];

    public $timestamps = false;
}
