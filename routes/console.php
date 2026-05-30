<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('truckfund:seed-demo', function () {
    (new \Database\Seeders\DatabaseSeeder)->run();

    $this->info('Demo data seeded.');
    $this->line('  admin@truckfund.test / password');
    $this->line('  manager@truckfund.test / password');
    $this->line('  tl@truckfund.test / password');
    $this->line('  sales@truckfund.test / password');
    $this->line('  sales2@truckfund.test / password');
})->purpose('Seed demo users and catalog (no Faker)');
