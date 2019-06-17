<?php

namespace App\Console\Commands;

use App\GamesLinks;
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
        foreach ($links as $link) {
            $this->grabGame($platform, "http://www.vgmuseum.com/${link}");
        }
    }

    private function grabGame($platform, $link)
    {
        $path = $this->extractPath($link);

        $crawler = (new Client())->request('GET', $link);
        $title = $this->extractTitle($crawler->filter('title')->extract('_text')[0]);
        $slug = Str::slug("${platform} ${title}");

        $images = $crawler->filter('img')->extract('src');
        foreach ($images as $filename) {

            /*
$url = 'https://pay.google.com/about/static/images/social/og_image.jpg';
$info = pathinfo($url);
$contents = file_get_contents($url);
$file = '/tmp/' . $info['basename'];
file_put_contents($file, $contents);
$uploaded_file = new UploadedFile($file, $info['basename']);
dd($uploaded_file);
             */
            $url = "{$path}/${filename}";
            $info = pathinfo($url);
            $content = file_get_contents($url);
            $tempFileName = "/tmp/${info['basename']}";
            file_put_contents($tempFileName, $content);
            $uploadedFile = new UploadedFile($tempFileName, $info['basename']);
            $storedPath = Storage::putFileAs("images/${slug}", $uploadedFile, $filename);
            dd($storedPath);

//            $content = file_get_contents("{$path}/${filename}");
//            Storage::putFileAs('images', $content, $filename);
//            Storage::putFileAs('images', new File("{$path}/${filename}"), $filename);
            dd('here');
        }
        dd($images);
    }

    private function dowlnoadImage($url)
    {

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
