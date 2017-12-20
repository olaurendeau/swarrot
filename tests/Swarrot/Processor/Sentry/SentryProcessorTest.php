<?php

namespace Swarrot\Tests\Processor\Ack;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Swarrot\Broker\Message;
use Swarrot\Processor\ProcessorInterface;
use Raven_Client;
use Swarrot\Processor\Sentry\SentryProcessor;

class SentryProcessorTest extends TestCase
{
    public function test_it_should_return_void_when_no_exception_is_thrown()
    {
        $processor       = $this->prophesize(ProcessorInterface::class);
        $sentryClient    = $this->prophesize(Raven_Client::class);

        $message = new Message('my_body', [], 1);
        $processor = new SentryProcessor($processor->reveal(), $sentryClient->reveal());

        $sentryClient->captureException(Argument::cetera())->shouldNotBeCalled();

        $this->assertNull($processor->process($message, []));
    }

    public function test_it_should_capture_when_an_exception_is_thrown()
    {
        $processor       = $this->prophesize(ProcessorInterface::class);
        $sentryClient    = $this->prophesize(Raven_Client::class);

        $message = new Message('my_body', [], 1);
        $options = [
            'queue' => 'my_queue',
        ];

        $processor->process(Argument::exact($message), Argument::exact($options))->willThrow('\BadMethodCallException');
        $processor = new SentryProcessor($processor->reveal(), $sentryClient->reveal());

        $sentryData = [
            'tags' => [
                'routing_key' => '',
                'queue' => 'my_queue',
            ],
            'extra' => [
                'message' => 'my_body',
            ]
        ];

        $sentryClient->captureException(Argument::type('\BadMethodCallException'), Argument::exact($sentryData))->shouldBeCalled();
        $this->expectException('\BadMethodCallException');
        $this->assertNull($processor->process($message, $options));
    }
}
