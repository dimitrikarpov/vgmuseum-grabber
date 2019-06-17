<?php

namespace App\Console\Commands;

use App\GamesLinks;
use App\Platform;
use Illuminate\Console\Command;
use Goutte\Client;

class GrabImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'grab 
                            {--truncate : Truncate Links table before grabbing }';

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
        if ($this->option('truncate')) {
            GamesLinks::query()->truncate();
        }

//        $this->storeLinks('nes', 'http://www.vgmuseum.com/nes_b.html');
        $this->grabGames('nes');

    }

    private function grabGames($platform)
    {
        $links = Platform::where('title', $platform)->first()->links()->pluck('url');
        foreach ($links as $link) {
            $this->grabGame($platform, $link);
        }
    }

    private function grabGame($platform, $link)
    {
        $url = "www.vgmuseum.com/${link}";
        dd($url);
    }

    private function storeLinks($platform, $url)
    {
        $crawler = (new Client())->request('GET', $url);
        $output = $crawler->filter("li > a")->extract(['_text', 'href']);

        $array = [];
        foreach($output as $item) {
            if ($item[0] === '' || $item[1] === '#top'){
                continue;
            }

            $array[$item[0]] = $item[1];
        }

        $platform_id = Platform::where('title', $platform)->first()->id;

        $this->line("Getting links for ${platform}");
        $bar = $this->output->createProgressBar(count($array));
        $bar->start();

        foreach ($array as $title => $url) {
            GamesLinks::create([
                'platform_id' => $platform_id,
                'url' => $url
            ]);
            $bar->advance();
        }

        $bar->finish();
    }

}
