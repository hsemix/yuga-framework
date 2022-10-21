<?php

namespace Yuga\Providers\Composer\Console;

use Yuga\Console\Command;
use Yuga\Providers\Composer\PackageManager;

class DiscoverPackageCommand extends Command
{
    protected $name = 'package:discover';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Look for a yuga compatible package and load its services';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->comment('Installing Packages');
        $this->getYuga()->get(PackageManager::class)->install();
        $this->info('Packages have been installed successfully');
    }
}