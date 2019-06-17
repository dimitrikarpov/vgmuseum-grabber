<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Link extends Model
{
    protected $guarded = [];

    public function platform() {
        return $this->belongsTo(Platform::class);
    }
}
