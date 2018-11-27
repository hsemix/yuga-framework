<?php
/**
 * @author Mahad Tech Solutions
 */
namespace Yuga\Mailables;

use Yuga\Providers\ServiceProvider;
use Yuga\Mailables\Native\YugaMailer;
use Yuga\Interfaces\Application\Application;

class MailableServiceProvider extends ServiceProvider
{
    /**
     * Register a service to the application
     * 
     * @param \Yuga\Interfaces\Application\Application
     * 
     * @return mixed
     */
    public function load(Application $app)
    {
        $config = $app->config->load('config.Settings');
        $mailable = env('APP_MAILABLE', 'Native');
        $settings = $config->get("mailable.{$mailable}");
        if ($mailable == 'Native') {
            $mailableClass = YugaMailer::class;
            $settings['smtp']['protocol'] = $settings['type'];
            $settings = $settings['smtp'];
        } else {
            $mailableClass = '\Yuga\Mailables\\'.$mailable.'\\'.$mailable;
        }
        
        $connection = $app->singleton('mailable', $mailableClass);
        $app->resolve('mailable', [
            $settings
        ]);
        $this->mailer($app, $app->make('mailable'), $settings);
    }

    /**
     * Set the mailer used and return a new singleton instance of that mailer
     * 
     * @param \Yuga\Interfaces\Application\Application $app
     * @param object $mailable
     * @param array $settings
     * 
     * @return \Yuga\Mailables\Mailer $mailer
     */
    protected function mailer(Application $app, $mailable, $settings)
    {
        return $app->singleton('mailer', function() use ($mailable, $settings) {
            $mailer = new Mailer;
            $mailer->setArgs($settings);
            $mailer->setMailable($mailable);
            return $mailer;
        });
    }
}