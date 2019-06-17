<?php

namespace App\Console\Commands;

use App\Platform;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ExportImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create export json file';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $platforms = Platform::all();

        $data = new \stdClass();
        foreach ($platforms as $platform) {
            $games = $this->getList($platform);
            $data->{$platform->title} = $games;
        }

        Storage::put('export.json', json_encode($data));
    }

    private function getList(Platform $platform)
    {
        $games = $platform->games;

        $list = [];
        foreach ($games as $game) {
            $entry = new \stdClass();
            $entry->title = $game->title;
            $entry->images = $game->images->pluck('file')->toArray();
            $list[] = $entry;
        }

        return $list;
    }
}
