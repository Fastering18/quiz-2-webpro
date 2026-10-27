<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:prepare {--fresh : Drop all tables and migrate again}')]
#[Description('Migrate, seed an empty database, and cache config, events, and views in one boot')]
class PrepareApplication extends Command
{
    public function handle(): int
    {
        if ($this->option('fresh')) {
            $this->call('migrate:fresh', ['--force' => true]);
        } else {
            $this->call('migrate', ['--force' => true]);
        }

        if (User::query()->count() === 0) {
            $this->info('pengguna table empty - seeding');
            $this->call('db:seed', ['--force' => true]);
        }

        $this->call('config:cache');
        $this->call('event:cache');
        $this->call('view:cache');

        return self::SUCCESS;
    }
}
