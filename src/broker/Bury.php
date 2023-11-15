<?php

namespace bus\broker;

use bus\broker\commands\DeleteCommand;
use bus\broker\commands\KickCommand;
use bus\broker\exception\BuryStrategyNotFoundException;
use bus\broker\exception\NothingToDoException;
use bus\config\ConfigNotFoundException;
use bus\config\Provider;
use bus\message\QMessageFactory;
use Pheanstalk\Contract\PheanstalkManagerInterface;
use Pheanstalk\Contract\PheanstalkSubscriberInterface;
use Pheanstalk\Pheanstalk;
use Pheanstalk\Values\Job;
use Psr\Log\LoggerInterface;
use ReflectionException;
use Throwable;

/**
 * Handle buried jobs - kick, delete or do nothing
 *
 * Class BuryChecker
 * @package bus\broker
 */
class Bury
{
    private array $strategies;

    private QMessageFactory $factory;

    private BuryStrategyFactory $fBuryStrategy;

    public function __construct(private Provider $configProvider, private LoggerInterface $logger)
    {
        $this->factory = new QMessageFactory();
        $this->fBuryStrategy = new BuryStrategyFactory($this->logger);
    }

    /**
     * @param Pheanstalk $broker
     */
    public function check(Pheanstalk $broker): void
    {
        try {
            while (($job = $broker->peekBuried()) instanceof Job) {
                try {
                    $this->consume($job, $broker, $broker);
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
     * @param PheanstalkManagerInterface $manager
     * @param PheanstalkSubscriberInterface $subscriber
     * @throws BuryStrategyNotFoundException
     * @throws ConfigNotFoundException
     * @throws NothingToDoException
     * @throws ReflectionException
     */
    public function consume(Job $job, PheanstalkManagerInterface $manager, PheanstalkSubscriberInterface $subscriber): void
    {
        $stats = $manager->statsJob($job);

        $message = $this->factory->fromString($job->getData());
        $config = $this->configProvider->getByJob($message->getJob());

        $command = $this->getStrategy($config->getBuryStrategy())->check($job, $stats, $config);

        if ($command instanceof KickCommand) {
            $manager->kickJob($command->getJob());
            $this->notice('Kicked id=' . $job->getId() . ' queue=' . $config->getQueue());
        }

        if ($command instanceof DeleteCommand) {
            $subscriber->delete($command->getJob());
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
