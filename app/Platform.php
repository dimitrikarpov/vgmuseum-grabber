<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Platform extends Model
{
    protected $guarded = [];

    public function games()
    {
        return $this->hasMany(Game::class);
    }

    public function links()
    {
        return $this->hasMany(GamesLinks::class);
    }
}
