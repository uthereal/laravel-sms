<?php

namespace Trapstats\Sms;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Stringable;

class Sms implements Stringable
{
    /**
     * Sender of the sms.
     *
     * @var string
     */
    protected string $from = '';

    /**
     * Recipients of the sms.
     *
     * @var string[]
     */
    protected array $to = [];

    /**
     * The text of the sms message.
     *
     * @var string
     */
    protected string $content = '';

    /**
     * Set the sender of the sms
     *
     * @param  string  $phoneNumber
     * @return $this
     */
    public function from(string $phoneNumber): self
    {
        $this->from = $phoneNumber;

        return $this;
    }

    /**
     * Set the recipients of the sms
     *
     * @param  string|array  $phoneNumber
     * @return $this
     */
    public function to(string|array $phoneNumber): self
    {
        $this->to = Arr::wrap($phoneNumber);

        return $this;
    }

    /**
     * Set the content of the message
     *
     * @param  string  $content
     * @return $this
     */
    public function content(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * @return string
     */
    public function getFrom(): string
    {
        return $this->from;
    }

    /**
     * @return string[]
     */
    public function getTo(): array
    {
        return $this->to;
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return Str::of('')
            ->append(
                "----- Sms Message -----\n",
                "From: {$this->from}\n",
                "To: [ ".implode(', ', $this->to)." ]\n",
                "Content: {$this->content}\n",
                "----- Sms Message -----\n"
            );
    }
}