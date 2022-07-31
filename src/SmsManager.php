<?php

namespace Trapstats\Sms;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Log\LogManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Trapstats\Sms\Contracts\Factory as SmsFactory;
use Trapstats\Sms\Contracts\Messenger as MessengerContract;
use Trapstats\Sms\Contracts\TransportContract;
use Trapstats\Sms\Transport\ArrayTransport;
use Trapstats\Sms\Transport\FailoverTransport;
use Trapstats\Sms\Transport\LogTransport;
use Trapstats\Sms\Transport\TwilioTransport;
use Twilio\Rest\Client as TwilioClient;

/**
 * @mixin \Trapstats\Sms\Messenger
 */
class SmsManager implements SmsFactory
{
    /**
     * The array of resolved messengers.
     *
     * @var array
     */
    protected array $messengers = [];

    /**
     * The registered custom driver creators.
     *
     * @var array
     */
    protected array $customCreators = [];

    /**
     * Create a new Sms manager instance.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     */
    public function __construct(
        protected Application $app,
    ) {
        //...
    }

    /**
     * @inheritDoc
     */
    public function messenger(?string $name = null): MessengerContract
    {
        $name = $name ?: $this->getDefaultDriver();

        if (!isset($this->messengers[$name])) {
            $this->messengers[$name] = $this->resolve($name);
        }

        return $this->messengers[$name];
    }

    /**
     * Resolve the given messenger.
     *
     * @param  string  $name
     * @return \Trapstats\Sms\Contracts\Messenger
     * @throws \InvalidArgumentException
     */
    protected function resolve(string $name): MessengerContract
    {
        $config = $this->getConfig($name);

        if (is_null($config)) {
            throw new InvalidArgumentException("Sms Messenger [{$name}] is not defined.");
        }

        // Once we have created the messenger instance we will set a container instance
        // on the messenger. This allows us to resolve messenger classes via containers
        // for maximum testability on said classes instead of passing Closures.
        $messenger = new Messenger(
            name: $name,
            transport: $this->createTransport($config),
            events: $this->app['events'],
            queue: $this->app->bound('queue') ? $this->app['queue'] : null,
        );

        // Next we will set the global addresses on this messenger, which allows
        // for easy unification of all "from" addresses as well as easy debugging
        // of sent messages since these will be sent to a single phone number.
        foreach (['from', 'to'] as $type) {
            $this->setGlobalAddress($messenger, $config, $type);
        }

        return $messenger;
    }

    /**
     * Create a new transport instance.
     *
     * @param  array  $config
     * @return \Trapstats\Sms\Contracts\TransportContract
     *
     * @throws \InvalidArgumentException
     */
    public function createTransport(array $config): TransportContract
    {
        $transport = $config['transport'];

        if (isset($this->customCreators[$transport])) {
            return call_user_func($this->customCreators[$transport], $config);
        }

        if (trim($transport ?? '') === '' || !method_exists($this, $method = 'create'.ucfirst($transport).'Transport')) {
            throw new InvalidArgumentException("Unsupported sms transport [{$transport}].");
        }

        return $this->{$method}($config);
    }

    /**
     * Create an instance of the Twilio SMS Transport driver.
     *
     * @param  array  $config
     * @return \Trapstats\Sms\Transport\TwilioTransport
     * @throws \Twilio\Exceptions\ConfigurationException
     */
    protected function createTwilioTransport(array $config): TwilioTransport
    {
        $config = array_merge(
            $this->app['config']->get('services.twilio', []),
            $config
        );

        $client = new TwilioClient(
            $config['account_sid'],
            $config['auth_token'],
        );

        return new TwilioTransport(
            $client, Arr::except($config, ['transport', 'account_sid', 'auth_token'])
        );
    }

    /**
     * Create an instance of the Failover Transport driver.
     *
     * @param  array  $config
     * @return \Trapstats\Sms\Transport\FailoverTransport
     */
    protected function createFailoverTransport(array $config): FailoverTransport
    {
        $transports = [];

        foreach ($config['messengers'] as $name) {
            $transports[] = $this->createTransport($this->getConfig($name));
        }

        return new FailoverTransport($transports);
    }

    /**
     * Create an instance of the Log Transport driver.
     *
     * @param  array  $config
     * @return \Trapstats\Sms\Transport\LogTransport
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function createLogTransport(array $config): LogTransport
    {
        $logger = $this->app->make(LoggerInterface::class);

        if ($logger instanceof LogManager) {
            $logger = $logger->channel(
                $config['channel'] ?? $this->app['config']->get('sms.log_channel')
            );
        }

        return new LogTransport($logger);
    }

    /**
     * Create an instance of the Array Transport Driver.
     *
     * @return \Trapstats\Sms\Transport\ArrayTransport
     */
    protected function createArrayTransport(): ArrayTransport
    {
        return new ArrayTransport;
    }

    /**
     * Set a global address on the messenger by type.
     *
     * @param  \Trapstats\Sms\Messenger  $messenger
     * @param  array  $config
     * @param  string  $type  ['from', 'to']
     * @return void
     */
    protected function setGlobalAddress(Messenger $messenger, array $config, string $type): void
    {
        $address = $config[$type] ?? $this->app['config']['sms.'.$type];

        if (is_string($address)) {
            $messenger->{'always'.Str::studly($type)}($address);
        }
    }

    /**
     * Get the sms connection configuration.
     *
     * @param  string  $name
     * @return array|null
     */
    protected function getConfig(string $name): ?array
    {
        return $this->app['config']["sms.messengers.{$name}"];
    }

    /**
     * Get the default messenger driver name.
     *
     * @return string
     */
    public function getDefaultDriver(): string
    {
        return $this->app['config']['sms.default'];
    }

    /**
     * Set the default sms driver name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultDriver(string $name): void
    {
        $this->app['config']['sms.default'] = $name;
    }

    /**
     * Disconnect the given mailer and remove from local cache.
     *
     * @param  string|null  $name
     * @return void
     */
    public function purge(string $name = null): void
    {
        $name = $name ?: $this->getDefaultDriver();

        unset($this->messengers[$name]);
    }

    /**
     * Register a custom transport creator Closure.
     *
     * @param  string  $driver
     * @param  callable  $callback
     * @return $this
     */
    public function extend(string $driver, callable $callback): self
    {
        $this->customCreators[$driver] = $callback;

        return $this;
    }

    /**
     * Forget the resolved messenger instances.
     *
     * @return $this
     */
    public function forgetMessengers(): self
    {
        $this->messengers = [];

        return $this;
    }

    /**
     * Dynamically call the default driver instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters): mixed
    {
        return $this->messenger()->$method(...$parameters);
    }
}