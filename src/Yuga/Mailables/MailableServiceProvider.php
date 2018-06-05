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
    public function load(Application $app)
    {
        $config = $app->config->load('config.Settings');
        $mailable = env('APP_MAILABLE', 'Native');
        if ($mailable == 'Native') {
            $mailableClass = YugaMailer::class;
        } else {
            $mailableClass = '\Yuga\Mailables\\'.$mailable.'\\'.$mailable;
        }
        $settings = $config->get("mailable.{$mailable}");
        $connection = $app->singleton('mailable', $mailableClass);
        $app->resolve('mailable', [
            $settings
        ]);
        $this->mailer($app, $app->make('mailable'), $settings);
    }

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