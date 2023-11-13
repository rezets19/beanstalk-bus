<?php

namespace bus\broker;

use bus\broker\commands\DeleteCommand;
use bus\broker\commands\KickCommand;
use bus\broker\exception\BuryStrategyNotFoundException;
use bus\broker\exception\NothingToDoException;
use bus\config\Provider;
use bus\message\FQMessage;
use Pheanstalk\Contract\PheanstalkManagerInterface;
use Pheanstalk\Values\Job;
use Psr\Log\LoggerInterface;
use ReflectionException;
use Throwable;

/**
 * Handle buried jobs - kick, delete or nothing
 *
 * Class BuryChecker
 * @package bus\broker
 */
class Bury
{
    private array $strategies;

    private Provider $configProvider;

    private FQMessage $factory;

    private LoggerInterface $logger;

    private FBuryStrategy $fBuryStrategy;

    public function __construct(Provider $configProvider, LoggerInterface $logger)
    {
        $this->factory = new FQMessage();
        $this->configProvider = $configProvider;
        $this->logger = $logger;
        $this->fBuryStrategy = new FBuryStrategy($this->logger);
    }

    /**
     * @param PheanstalkManagerInterface $broker
     */
    public function check(PheanstalkManagerInterface $broker): void
    {
        try {
            while (($job = $broker->peekBuried()) instanceof Job) {
                try {
                    $this->consume($job, $broker);
                } catch (NothingToDoException $e) {
                    break;
                }
            }
        } catch (Throwable $e) {
            $this->notice($e);
        }
    }

    /**
     * @param Job $job
     * @param PheanstalkManagerInterface $broker
     * @throws BuryStrategyNotFoundException
     * @throws NothingToDoException
     * @throws ReflectionException
     */
    public function consume(Job $job, PheanstalkManagerInterface $broker): void
    {
        $stats = $broker->statsJob($job);

        $message = $this->factory->fromString($job->getData());
        $config = $this->configProvider->getByJob($message->getJob());

        $command = $this->getStrategy($config->getBuryStrategy())->check($job, $stats, $config);

        if ($command instanceof KickCommand) {
            $broker->kickJob($command->getJob());
            $this->notice('Kicked id=' . $job->getId() . ' queue=' . $config->getQueue());
        }

        if ($command instanceof DeleteCommand) {
            $broker->delete($command->getJob());
            $this->notice('Deleted id=' . $job->getId() . ' queue=' . $config->getQueue());
        }
    }

    /**
     * @param string $class
     * @return IBuryStrategy
     * @throws BuryStrategyNotFoundException
     */
    private function getStrategy(string $class): IBuryStrategy
    {
        if (!isset($this->strategies[$class])) {
            $this->strategies[$class] = $this->fBuryStrategy->create($class);
        }

        return $this->strategies[$class];
    }

    /**
     * @param string $message
     */
    private function notice(string $message): void
    {
        $this->logger->notice($message);
    }
}
