<?php

namespace App\Console\Commands;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Console\Command;

class SeedDemoCommand extends Command
{
    protected $signature = 'truckfund:seed-demo';

    protected $description = 'Seed the two admin accounts and the heavy truck catalog (no Faker — safe in production Docker)';

    public function handle(): int
    {
        $this->call('db:seed', [
            '--class' => DatabaseSeeder::class,
            '--force' => true,
        ]);

        $this->info('Seeding complete. Admin accounts:');

        foreach (config('truckfund.seed_admins', []) as $admin) {
            $this->line('  '.$admin['email'].' ('.$admin['full_name'].')');
        }

        $this->line('Passwords come from config/truckfund.php — override them with TRUCKFUND_SEED_*_PASSWORD.');

        return self::SUCCESS;
    }
}
