<?php
use App\Platform;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // $this->call(UsersTableSeeder::class);
        Platform::create(['title' => 'zx']);
        Platform::create(['title' => 'nes']);
        Platform::create(['title' => 'snes']);
        Platform::create(['title' => 'smd']);
    }
}
