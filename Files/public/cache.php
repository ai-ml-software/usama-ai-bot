<?php

require_once realpath(__DIR__ . '/../vendor/autoload.php');
$app = require_once realpath(__DIR__ . '/../bootstrap/app.php');

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

// Run artisan commands
Artisan::call('optimize:clear');
echo nl2br(Artisan::output());

Artisan::call('cache:clear');
echo nl2br(Artisan::output());

Artisan::call('config:clear');
echo nl2br(Artisan::output());

Artisan::call('route:clear');
echo nl2br(Artisan::output());

Artisan::call('view:clear');
echo nl2br(Artisan::output());

Artisan::call('event:clear');
echo nl2br(Artisan::output());

echo "<strong>All Laravel caches cleared via Artisan.</strong>";
