<?php 

namespace Yuga\Queue\Console;

use Yuga\Console\Command;
use Nette\PhpGenerator\PhpFile;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Queue Table creation.
 */
class MakeQueueJobCommand extends Command
{
	/**
	 * The Command's name
	 *
	 * @var string
	 */
	protected $name = 'make:job';
    
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new Job class';

	/**
	 * Jobs Dir
	 * 
	 * @var string
	 */
	protected $path = 'app/Jobs';

	/**
	 * Creates a queue table
	 */
    public function handle()
    {
        $name = $this->argument('name');

        $this->createDirectories();
        $this->processJobCreation($name);
        $this->info('Job Created Successfully.');
    }


	/**
     * Process The Job Creation 
     * 
     * @param string $handler
     * @param string $method
     * @param string $eventName
     * 
     * @return void
     */
    protected function processJobCreation(string $name)
    {
        file_put_contents(
            path($this->path . '/' . trim($name) . '.php'),
            $this->generateJob($name)
        );
        // file_put_contents(
        //     path('config/AppCommands.php'),
        //     $this->processAppConfigFiles($name)
        // );
    }

    /**
     * Generate Command logic
     * 
     * @param string $name
     * 
     * @return void
     */
    protected function generateJob($name)
    {
        $file = new PhpFile;
        $file->addComment('This file was auto-generated.');

        $namespace = $file->addNamespace(env('APP_NAMESPACE', 'App'). '\\Jobs');

		$namespace->addUse('Yuga\Queue\DispatchableTrait');
        $namespace->addUse('Yuga\Interfaces\Queue\JobInterface');

        $class = $namespace->addClass(trim($name));

        $class->addImplement('Yuga\Interfaces\Queue\JobInterface');
		$class->addTrait('Yuga\Queue\DispatchableTrait');

		$classConstructor = $class->addMethod('__construct')->setBody('//');
		$classConstructor->addComment('Create a new job instance.')->addComment('@return void');
		
        $classRunMethod = $class->addMethod('run')->setBody('//');
		$classRunMethod->addComment('Run the job.')->addComment('@return void');
        
        return $file;
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return [
            ['name', InputArgument::REQUIRED, 'The name of the class'],
        ];
    }
}