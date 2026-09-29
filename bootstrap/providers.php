<?php

use App\Providers\AppServiceProvider;
use Intervention\Image\Laravel\ServiceProvider as ImageServiceProvider;
use Intervention\Image\Laravel\Facades\Image;

return [
    AppServiceProvider::class,
    ImageServiceProvider::class,
];

$image = Image::decode($upload)->resize(300, 200);
