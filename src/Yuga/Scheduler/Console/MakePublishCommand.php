<?php 

namespace Yuga\Scheduler\Console;

use Config\Autoload;
use Yuga\Console\Command;

/**
 * Enables Task Running
 */
class MakePublishCommand extends SchedulerCommand
{
	/**
	 * The Command's name
	 *
	 * @var string
	 */
	protected $name = 'scheduler:publish';

	/**
	 * the Command's short description
	 *
	 * @var string
	 */
	protected $description = 'Publish the scheduler runner.';

	/**
	 * the Command's usage
	 *
	 * @var string
	 */
	// protected $usage = 'cronjob:publish';

	/**
	 * Enables task running
	 *
	 * @param array $params
	 */
	public function runner(array $params)
	{
		$this->determineSourcePath();

        // Config
        if( CLI::prompt('Publish Config file?', [ 'y', 'n' ]) == 'y' )
        {
            $this->publishConfig();
        }
	}

    protected function publishConfig()
    {
        $path = "{$this->sourcePath}/Config/CronJob.php";

        $content = file_get_contents($path);
        $content = str_replace( 'namespace Daycry\CronJob\Config', "namespace Config", $content );
        $content = str_replace( 'extends BaseConfig', "extends \Daycry\CronJob\Config\CronJob", $content );

        $this->writeFile( "Config/CronJob.php", $content );
    }

    /**
     * Determines the current source path from which all other files are located.
     */
    protected function determineSourcePath()
    {
        $this->sourcePath = realpath(__DIR__ . '/../');

        if( $this->sourcePath == '/' || empty( $this->sourcePath ) )
        {
            CLI::error( 'Unable to determine the correct source directory. Bailing.' );
            exit();
        }
    }

    /**
     * Write a file, catching any exceptions and showing a
     * nicely formatted error.
     *
     * @param string $path
     * @param string $content
     */
    protected function writeFile( string $path, string $content )
    {
        $config = new Autoload();
        $appPath = $config->psr4[ APP_NAMESPACE ];

        $directory = dirname( $appPath . $path );

        if( !is_dir($directory ) )
        {
            mkdir( $directory );
        }

        try
        {
            write_file( $appPath . $path, $content );
        }
        catch( \Exception $e )
        {
            $this->showError( $e );
            exit();
        }

        $path = str_replace( $appPath, '', $path );

        CLI::write( CLI::color( '  created: ', 'green' ) . $path );
    }
}