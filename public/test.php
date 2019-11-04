<?php
use Illuminate\Contracts\Console\Kernel;
use App\User;
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

/*
 * Test entry point. Should delete this entire file on prod
 */

response()->json(User::query()->where('name', 'admin')
    ->get('email'))
    ->send();