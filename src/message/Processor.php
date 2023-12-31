<?php

namespace bus\message;

use bus\config;
use bus\config\Provider;
use bus\exception;
use bus\exception\HandlerNotFoundException;
use bus\handler\IHandler;
use bus\MessageBus;
use Psr\Log\LoggerInterface;
use ReflectionException;

/**
 * AMQP message processor
 *
 * Class Processor
 * @package bus
 */
class Processor
{
    /**
     * @var QMessageFactory
     */
    private QMessageFactory $factory;

    /**
     * @var Provider
     */
    private Provider $configProvider;

    public function __construct(
        private MessageBus      $messageBus,
        private LoggerInterface $logger,
        private IHandler        $handler
    )
    {
        $this->configProvider = $this->messageBus->getConfigProvider();
        $this->factory = new QMessageFactory();
    }

    /**
     * @param QMessage $message
     * @return QMessage
     * @throws config\ConfigNotFoundException
     * @throws HandlerNotFoundException
     * @throws exception\BrokerNotFoundException
     * @throws ReflectionException
     */
    public function process(QMessage $message): QMessage
    {
        $config = $this->configProvider->getByJob($message->getJob());
        $configHandlers = $config->getHandlers();

        if (!empty($message->getHandler())) {
            // Received message from queue with exact handler
            // Search for handler in config array and execute one
            foreach ($configHandlers as $item) {
                if ($item[0] === $message->getHandler()[0] && $item[1] === $message->getHandler()[1]) {
                    $this->handler->handle($message->getJob(), [$message->getHandler()]);

                    return $message;
                }
            }

            throw new HandlerNotFoundException($message->getHandler()[0], $message->getHandler()[1]);
        } else {
            if (count($configHandlers) > 1) {
                // Add new jobs from handler array
                foreach ($configHandlers as $item) {
                    $m = $this->factory->create($message->getJob(), $config);
                    $m->addHandler($item);
                    // First start with zero delay
                    $m->setDelay(0);
                    $this->messageBus->sendMessage($config, $m);
                }
            } elseif (1 === count($configHandlers)) {
                $this->handler->handle($message->getJob(), $configHandlers);

                return $message;
            }
        }

        return $message;
    }
}
