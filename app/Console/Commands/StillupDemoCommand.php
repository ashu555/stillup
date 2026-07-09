<?php

namespace App\Console\Commands;

use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;

class StillupDemoCommand extends Command
{
    protected $signature = 'stillup:demo
                            {--force : Allow running outside the local environment}';

    protected $description = 'Seed Stillup demo data (local environment only)';

    public function handle(): int
    {
        if (! app()->environment('local') && ! $this->option('force')) {
            $this->error('stillup:demo only runs in the local environment. Pass --force to override.');

            return self::FAILURE;
        }

        $this->call('db:seed', [
            '--class' => DemoSeeder::class,
            '--force' => true,
        ]);

        $this->newLine();
        $this->info('Demo ready.');
        $this->line('Login: demo@stillup.test / password');
        $this->line('App: '.config('app.url'));
        $this->line('Status: '.rtrim((string) config('app.url'), '/').'/status/production');
        $this->line('Mailhog: http://localhost:8025');

        return self::SUCCESS;
    }
}
