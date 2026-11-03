<?php

/**
 * @author Mahad Tech Solutions
 */

namespace Yuga\Queue;

use Spiral\Goridge\RPC\RPC;
use Spiral\RoadRunner\Jobs\Jobs;
use Yuga\Application\Application as App;
use Yuga\Database\Migration\PDO;
use Yuga\Interfaces\Application\Application;
use Yuga\Providers\ServiceProvider;
use Yuga\Providers\Shared\MakesCommandsTrait;
use Yuga\Queue\Console\MakeQueueFailedCommand;
use Yuga\Queue\Console\MakeQueueFailuresCommand;
use Yuga\Queue\Console\MakeQueueForgetCommand;
use Yuga\Queue\Console\MakeQueueJobCommand;
use Yuga\Queue\Console\MakeQueueMonitorCommand;
use Yuga\Queue\Console\MakeQueueReplayCommand;
use Yuga\Queue\Console\MakeQueueRestartCommand;
use Yuga\Queue\Console\MakeQueueRetryCommand;
use Yuga\Queue\Console\MakeQueueWorkCommand;
use Yuga\Queue\Console\MakeRoadRunnerConsumeCommand;
use Yuga\Queue\Console\MakeRoadRunnerTestConsumeCommand;
use Yuga\Queue\Contracts\ReplayableFailureStoreInterface;
use Yuga\Queue\Failure\DatabaseFailureStore;
use Yuga\Queue\Failure\FailureReplayer;
use Yuga\Queue\JobRegistry;
use Yuga\Queue\Jobs\ClosureJob;
use Yuga\Queue\Payload\ClosureCodec;
use Yuga\Queue\Payload\CodecRegistry;
use Yuga\Queue\Payload\JobEnvelope;
use Yuga\Queue\Payload\JsonCodec;
use Yuga\Queue\Payload\PhpSerializeCodec;
use Yuga\Queue\TerminalFailureNotifier;
use Yuga\Queue\Transport\DatabaseTransport;
use Yuga\Queue\Transport\RoadRunnerPublisher;

class QueueServiceProvider extends ServiceProvider
{
    use MakesCommandsTrait;

    protected $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function load(Application $app)
    {
        $app->singleton('queue.manager', function () {
            $manager = new QueueManager(config('queue_transport')->getAll());

            $manager->extend('database', function (array $settings) {
                // $pdo = new PDO(
                //     $settings['dsn'],
                //     $settings['username'] ?? null,
                //     $settings['password'] ?? null,
                //     [
                //         PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                //         PDO::ATTR_EMULATE_PREPARES => false,
                //     ]
                // );

                $pdo = PDO::getInstance()->getConnection();

                return new DatabaseTransport(
                    $pdo,
                    $settings['table'] ?? 'queue_messages'
                );
            });

            $manager->extend('roadrunner', function (array $settings) {
                if (!class_exists(Jobs::class)) {
                    throw new \RuntimeException(
                        'Install spiral/roadrunner-jobs to use the RoadRunner connection.'
                    );
                }

                $rpc = $settings['rpc'] ?? null;
                $pipelines = $settings['pipelines'] ?? null;

                if (!is_string($rpc) || trim($rpc) === '') {
                    throw new \InvalidArgumentException(
                        'The RoadRunner connection requires an RPC address.'
                    );
                }

                if (!is_array($pipelines)) {
                    throw new \InvalidArgumentException(
                        'The RoadRunner connection requires pipeline mappings.'
                    );
                }

                return new \Yuga\Queue\Transport\RoadRunnerPublisher(
                    new Jobs(
                        RPC::create($rpc)
                    ),
                    $pipelines
                );
            });

            return $manager;
        });

        $app->singleton('queue.jobs', function () {
            return new JobRegistry();
        });

        $app->singleton('queue.codecs', function () {
            $registry = new CodecRegistry();

            $registry->register(new JsonCodec());
            $registry->register(new PhpSerializeCodec(
                app('queue.jobs'),
                config('queue_transport')['php_allowed_classes'] ?? []
            ));

            $registry->register(
                new ClosureCodec()
            );

            return $registry;
        });

        $app->singleton('queue.handlers', function () {

            $handlers = new HandlerRegistry(function (object $job, ?JobContext $context = null): mixed {
                $method = new \ReflectionMethod($job, 'run');
                $arguments = [];

                foreach ($method->getParameters() as $parameter) {
                    if ($parameter->getName() === 'context') {
                        $arguments['context'] = $context;
                        break;
                    }
                }

                return app()->call([$job, 'run'], $arguments);
            });

            $handlers->register(
                'yuga.closure.v1',
                function (
                    mixed $payload,
                    JobEnvelope $envelope,
                    ?JobContext $context = null
                ): void {
                    if (
                        $envelope->encoding !== 'opis-closure-v4'
                        || !$payload instanceof ClosureJob
                    ) {
                        throw new \UnexpectedValueException(
                            'Invalid closure job type or encoding.'
                        );
                    }

                    $payload->run($context);
                }
            );

            return $handlers;
        });

        $app->singleton('queue.dispatcher', function () {
            return new Dispatcher(
                app('queue.manager'),
                app('queue.codecs')
            );
        });

        $app->singleton(
            'queue.reporter',
            fn() => new LifecycleReporter()
        );

        $app->singleton('queue.restart', fn() => new WorkerRestartStore(
            storage('framework/queue.restart')
        ));

        $app->singleton('queue.executor', fn() => new JobExecutor(
            app('queue.codecs'),
            app('queue.handlers')
        ));

        $app->singleton('queue.failures', function () {
            $settings = config('queue_transport')['connections']['database'];

            // $pdo = new \PDO(
            //     $settings['dsn'],
            //     $settings['username'] ?? null,
            //     $settings['password'] ?? null,
            //     [
            //         \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            //         \PDO::ATTR_EMULATE_PREPARES => false,
            //     ]
            // );

            $pdo = PDO::getInstance()->getConnection();

            return new DatabaseFailureStore($pdo);
        });

        $app->singleton('queue.replayers', function () {
            $store = app('queue.failures');

            if (!$store instanceof ReplayableFailureStoreInterface) {
                throw new \RuntimeException(
                    'The configured failure store does not support replay.'
                );
            }

            $publisher = app('queue.manager')->connection('roadrunner');

            return [
                'fntyuga-roadrunner-test' =>
                new FailureReplayer(
                    $store,
                    $publisher,
                    'fntyuga-roadrunner-test'
                ),
            ];
        });


        $app->singleton('queue.failure-notifier', fn() =>
        new TerminalFailureNotifier(
            app('queue.reporter')
        ));



        if ($app->runningInConsole()) {
            $app->singleton(
                'command.queue.work',
                fn() => new MakeQueueWorkCommand()
            );

            $app->singleton(
                'command.queue.failed',
                fn() => new MakeQueueFailedCommand()
            );

            $app->singleton(
                'command.queue.retry',
                fn() => new MakeQueueRetryCommand()
            );

            $app->singleton(
                'command.queue.forget',
                fn() => new MakeQueueForgetCommand()
            );

            $app->singleton(
                'command.queue.restart',
                fn() => new MakeQueueRestartCommand()
            );

            $app->singleton(
                'command.queue.monitor',
                fn() => new MakeQueueMonitorCommand()
            );

            $app->singleton('command.queue.make', fn() =>
            new MakeQueueJobCommand());

            $app->singleton('command.queue.test', fn() =>
            new MakeRoadRunnerTestConsumeCommand());

            $app->singleton('command.queue.failures', fn() =>
            new MakeQueueFailuresCommand());

            $app->singleton('command.queue.replay', fn() =>
            new MakeQueueReplayCommand());

            $app->singleton('command.queue.roadrunner.consume', fn() =>
            new MakeRoadRunnerConsumeCommand());

            $app->singleton('command.queue.serve', fn() =>
            new \Yuga\Queue\Console\MakeQueueServeCommand());

            $this->commands(
                'command.queue.work',
                'command.queue.failed',
                'command.queue.retry',
                'command.queue.forget',
                'command.queue.restart',
                'command.queue.monitor',
                'command.queue.make',
                'command.queue.test',
                'command.queue.failures',
                'command.queue.replay',
                'command.queue.roadrunner.consume',
                'command.queue.serve',
            );
        }
    }

    public function boot()
    {

        app('queue.jobs')->discover(
            config('queue_transport')['discovery']['roots'] ?? []
        );
        app('queue.handlers')->register(
            'test.console',
            function (array $data): void {
                echo $data['message'] . PHP_EOL;
            }
        );

        app('queue.handlers')->register(
            'notifications.send',
            function (
                array $data,
                \Yuga\Queue\Payload\JobEnvelope $envelope
            ): void {
                if (!isset($data['recipient'], $data['message'])) {
                    throw new \InvalidArgumentException(
                        'Recipient and message are required.'
                    );
                }

                echo "Sending to {$data['recipient']}: {$data['message']}"
                    . PHP_EOL;
            }
        );

        app('queue.reporter')->listen(
            function (string $event, array $context): void {
                $exception = $context['exception'] ?? null;

                $entry = [
                    'event' => $event,
                    'job_id' => $context['job_id'],
                    'type' => $context['type'],
                    'attempt' => $context['attempt'],
                ];

                if ($exception instanceof \Throwable) {
                    $entry['exception'] = $exception::class;
                    $entry['message'] = $exception->getMessage();
                }

                // Each failed attempt is recorded, including those being retried.
                error_log(json_encode($entry, JSON_THROW_ON_ERROR));
            }
        );

        app('queue.handlers')->register(
            'test.roadrunner.failure',
            static function (array $data): void {
                throw new \RuntimeException('Intentional RoadRunner test failure');
            }
        );
    }
}
