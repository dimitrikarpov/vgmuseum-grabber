<?php

namespace App\Console\Commands;

use App\Game;
use App\Link;
use App\Image;
use App\Platform;
use Illuminate\Console\Command;
use Goutte\Client;
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
    protected $signature = 'grab {platform : specify platform zx,nes,snes,smd} 
                                 {--truncate : truncate links table}
                                 {--links : grab games links table}';

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
            Link::query()->truncate();
        }

        if ($this->option('links')) {
            $this->storeLinks(Platform::whereTitle('zx')->first(), 'http://www.vgmuseum.com/zx_b.html');
            $this->storeLinks(Platform::whereTitle('nes')->first(), 'http://www.vgmuseum.com/nes_b.html');
            $this->storeLinks(Platform::whereTitle('snes')->first(), 'http://www.vgmuseum.com/snes_b.html');
            $this->storeLinks(Platform::whereTitle('smd')->first(), 'http://www.vgmuseum.com/genesis_b.html');
        }

        switch ($this->argument('platform')) {
            case 'zx':
                $this->grabGames(Platform::whereTitle('zx')->first());
                break;
            case 'nes':
                $this->grabGames(Platform::whereTitle('nes')->first());
                break;
            case 'snes':
                $this->grabGames(Platform::whereTitle('snes')->first());
                break;
            case 'smd':
                $this->grabGames(Platform::whereTitle('smd')->first());
                break;
        }
    }

    private function grabGames(Platform $platform)
    {
        $links = $platform->links;

        $bar = $this->output->createProgressBar($links->count());
        $bar->start();
        foreach ($links as $link) {
            $this->grabGame($platform, $link);
            $bar->advance();
        }
        $bar->finish();
    }

    private function grabGame(Platform $platform, Link $link)
    {
        $basePath = "http://www.vgmuseum.com";
        $path = $this->extractPath("{$basePath}/{$link->url}");

        $gameTitle = $link->title;
        $gameSlug = Str::slug("{$platform->title} {$gameTitle}");

        $game = Game::whereTitle($gameTitle)->where('platform_id', $platform->id)->first();
        if (!$game) {
            $game = Game::create([
                'title' => $gameTitle,
                'platform_id' => $platform->id,
            ]);
        }

        $crawler = (new Client())->request('GET', "{$basePath}/{$link->url}");
        $images = $crawler->filter('img')->extract('src');

        if ($this->isGrabbed($game, $images)) {
            return true;
        }

        foreach ($images as $filename) {
            $imagePath = Storage::putFileAs("images/${gameSlug}", $this->downloadImage("{$path}/${filename}"), $filename);

            Image::create(['game_id' => $game->id, 'file' => $imagePath]);
        }
    }

    private function isGrabbed(Game $game, array $images)
    {
        if ($game->images()->count() === 0) {
            return false;
        } elseif ($game->images->count() !== count($images)) {
            $game->images->each(function($image) {
                $image->delete();
            });

            $this->line("{$game->title} [{$game->platform->title}] PARTIALLY grabbed");
            return false;
        } else {
            $this->line("{$game->title} [{$game->platform->title}] already grabbed");
            return true;
        }
    }

    private function extractPath($link)
    {
        return trim(substr($link, 0, strrpos($link, '/')));
    }

    private function downloadImage($url)
    {
        $info = pathinfo($url);
        $content = $this->getContentByCurl($url);

        $tempFileName = "/tmp/${info['basename']}";
        file_put_contents($tempFileName, $content);

        return new UploadedFile($tempFileName, $info['basename']);
    }

    private function getContentByCurl($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        $data = curl_exec($curl);
        curl_close($curl);

        return $data;
    }

    private function storeLinks(Platform $platform, $url)
    {
        $crawler = (new Client())->request('GET', $url);
        $output = $crawler->filter("li > a")->extract(['_text', 'href']);

        $array = [];
        foreach ($output as $item) {
            if ($item[0] === '' || $item[1] === '#top') {
                continue;
            }

            $array[$item[0]] = $item[1];
        }

        $this->line("Getting links for {$platform->title}");
        $bar = $this->output->createProgressBar(count($array));
        $bar->start();

        foreach ($array as $title => $url) {
            Link::create([
                'platform_id' => $platform->id,
                'url' => $url,
                'title' => $title,
            ]);
            $bar->advance();
        }

        $bar->finish();
    }
}
