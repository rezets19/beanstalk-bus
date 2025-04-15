<?php

namespace bus\message;

use bus\config\ConfigNotFoundException;
use bus\config\Provider;
use bus\exception\BrokerNotFoundException;
use bus\exception\HandlerNotFoundException;
use bus\handler\HandlerInterface;
use bus\MessageBus;
use Pheanstalk\Exception\NoImplementationException;
use Psr\Log\LoggerInterface;

/**
 * Message processor
 */
class Processor
{
    private QMessageFactory $factory;
    private Provider $configProvider;

    public function __construct(
        private MessageBus       $messageBus,
        private LoggerInterface  $logger,
        private HandlerInterface $handler,
        private Sender           $sender
    )
    {
        $this->configProvider = $this->messageBus->getConfigProvider();
        $this->factory = new QMessageFactory();
    }

    /**
     * @throws BrokerNotFoundException
     * @throws ConfigNotFoundException
     * @throws HandlerNotFoundException
     * @throws NoImplementationException
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
                    $this->sender->sendMessage($config, $m);
                }
            } elseif (1 === count($configHandlers)) {
                $this->handler->handle($message->getJob(), $configHandlers);

                return $message;
            }
        }

        return $message;
    }
}
