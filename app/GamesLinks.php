<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class GamesLinks extends Model
{
    protected $guarded = [];

    public function platform() {
        return $this->belongsTo(Platform::class);
    }
}
