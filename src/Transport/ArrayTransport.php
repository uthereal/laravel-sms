<?php

namespace Trapstats\Sms\Transport;

use Illuminate\Support\Collection;
use Trapstats\Sms\Contracts\TransportContract;
use Trapstats\Sms\Sms;
use Trapstats\Sms\SentMessage;

class ArrayTransport implements TransportContract
{
    /**
     * The collection of Messages.
     *
     * @var \Illuminate\Support\Collection
     */
    protected Collection $messages;

    /**
     * Create a new array transport instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->messages = new Collection();
    }

    /**
     * {@inheritdoc}
     */
    public function send(Sms $message): ?SentMessage
    {
        return $this->messages[] = new SentMessage($message);
    }

    /**
     * Retrieve the collection of messages.
     *
     * @return \Illuminate\Support\Collection
     */
    public function messages(): Collection
    {
        return $this->messages;
    }

    /**
     * Clear the messages from the local collection.
     *
     * @return \Illuminate\Support\Collection
     */
    public function flush(): Collection
    {
        return $this->messages = new Collection;
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return 'array';
    }
}