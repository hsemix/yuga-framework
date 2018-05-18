<?php
namespace Yuga\Authenticate\Console;

use Yuga\Console\Command;
use Symfony\Component\Console\Input\InputOption;

class MakeAuthCommand extends Command
{
    use MakeAuthControllers, MakeAuthMiddleware;
    protected $name = 'make:auth';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make basic login and registration logic, views and routes';

    /**
     * The views that need to be exported.
     *
     * @var array
     */
    protected $views = [
        'auth/login.temp'           => 'auth/login.hax.php',
        'auth/register.temp'        => 'auth/register.hax.php',
        'auth/passwords/email.temp' => 'auth/passwords/email.hax.php',
        'auth/passwords/reset.temp' => 'auth/passwords/reset.hax.php',
        'layouts/app.temp'          => 'layouts/app.hax.php',
        'home.temp'                 => 'home.hax.php',
    ];

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->createDirectories();

        $this->exportViews();

        if (!$this->option('views')) {
            $this->createAuthControllers();
            file_put_contents(
                path('routes/web.php'),
                file_get_contents(__DIR__.'/temps/make/routes.temp'),
                FILE_APPEND
            );
        }

        $this->createAuthMiddleware();
        $this->info('Authentication routes and views generated successfully.');
    }

    /**
     * Create the directories for the files.
     *
     * @return void
     */
    protected function createDirectories()
    {
        if (!is_dir($directory = path('resources/views/layouts'))) {
            mkdir($directory, 0755, true);
        }

        if (!is_dir($directory = path('resources/views/auth/passwords'))) {
            mkdir($directory, 0755, true);
        }
    }

    /**
     * Export the authentication views.
     *
     * @return void
     */
    protected function exportViews()
    {
        foreach ($this->views as $key => $value) {
            if (file_exists($view = path('resources/views/'.$value)) && !$this->option('force')) {
                if (!$this->confirm("The [{$value}] view already exists. Do you want to replace it?")) {
                    continue;
                }
            }

            copy(
                __DIR__.'/temps/make/views/'.$key,
                $view
            );
        }
    }
    
    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['views', null, InputOption::VALUE_OPTIONAL, 'Only Create authentication views.', false],
            ['force', null, InputOption::VALUE_OPTIONAL, 'Overwrite existing files.', false],
        ];
    }
}
