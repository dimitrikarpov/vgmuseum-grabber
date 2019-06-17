<?php

namespace App\Console\Commands;

use App\Game;
use App\GamesLinks;
use App\Image;
use App\Platform;
use Illuminate\Console\Command;
use Goutte\Client;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

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

        $bar = $this->output->createProgressBar(count($links));
        $bar->start();
        foreach ($links as $link) {
            $this->grabGame($platform, "http://www.vgmuseum.com/${link}");
            $bar->advance();
        }
        $bar->finish();
    }

    private function grabGame($platform, $link)
    {
        $path = $this->extractPath($link);

        $crawler = (new Client())->request('GET', $link);
        $gameTitle = $this->extractTitle($crawler->filter('title')->extract('_text')[0]);
        $gameSlug = Str::slug("${platform} ${gameTitle}");

        $game = Game::firstOrCreate(
            ['title' => $gameTitle],
            [
                'platform_id' => Platform::whereTitle($platform)->first()->id,
                'slug' => Str::slug($gameTitle),
            ]
        );

        $images = $crawler->filter('img')->extract('src');
        foreach ($images as $filename) {
            $imagePath = Storage::putFileAs("images/${gameSlug}", $this->downloadImage("{$path}/${filename}"), $filename);

            Image::create(['game_id' => $game->id, 'file' => $imagePath]);
        }
    }

    private function downloadImage($url)
    {
        $info = pathinfo($url);
        $content = file_get_contents(urlencode($url));
        $tempFileName = "/tmp/${info['basename']}";
        file_put_contents($tempFileName, $content);

        return new UploadedFile($tempFileName, $info['basename']);
    }

    private function extractTitle($rawTitle)
    {
        return trim(substr($rawTitle, strpos($rawTitle, ':') + 1));
    }

    private function extractPath($link)
    {
       return trim(substr($link, 0, strrpos($link, '/')));
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
