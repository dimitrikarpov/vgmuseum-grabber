<?php

namespace App\Console\Commands;

use App\Platform;
use Illuminate\Console\Command;

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
    protected $description = 'Command description';

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
        $export = new \stdClass();
//        $platforms = Platform::all();
//        dd($export);
        $this->getList(Platform::whereTitle('nes')->first());
    }

    private function getList(Platform $platform)
    {

    }
}
