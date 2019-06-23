# VGMUSEUM grabber

Grab images from [VGMUSEUM](http://www.vgmuseum.com)

## Requirements
* php 7.2
* curl php extension
* sqlite php extension

## Installation
* clone this repository and `cd` in to it
* copy environment file `cp .env.example .env`
* create db file `touch database/database.sqlite`
* install composer dependencies `composer install`
* seed the db `php artisan migrate:fresh --seed`

## Usage
* run `php artisan grab nes --links` to grab *nes* games images. also it can accept *zx*, *snes*, *smd*
* run `php artisan export` to create *export.json* file which contains game names, image paths organized by platform

images directory: `storage/app/images` and json file in `storage/app/games.json`