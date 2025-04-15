<?php

namespace bus\broker;

use bus\broker\commands\DeleteCommandInterface;
use bus\broker\commands\KickCommandInterface;
use bus\broker\exception\BuryStrategyNotFoundException;
use bus\broker\exception\NothingToDoException;
use bus\config\ConfigNotFoundException;
use bus\config\Provider;
use bus\message\QMessageFactory;
use Pheanstalk\Contract\PheanstalkManagerInterface;
use Pheanstalk\Contract\PheanstalkPublisherInterface;
use Pheanstalk\Contract\PheanstalkSubscriberInterface;
use Pheanstalk\Values\Job;
use Psr\Log\LoggerInterface;
use ReflectionException;
use Throwable;

/**
 * Handle buried jobs - kick, delete or do nothing
 */
class Bury
{
    private array $strategies;

    private QMessageFactory $factory;

    private BuryStrategyFactory $fBuryStrategy;

    public function __construct(private Provider $configProvider, private LoggerInterface $logger)
    {
        $this->factory = new QMessageFactory();
        $this->fBuryStrategy = new BuryStrategyFactory();
    }

    public function check(PheanstalkManagerInterface|PheanstalkPublisherInterface|PheanstalkSubscriberInterface $broker): void
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
        $jobStats = $manager->statsJob($job);

        $message = $this->factory->fromString($job->getData());
        $config = $this->configProvider->getByJob($message->getJob());

        $command = $this->getStrategy($config->getBuryStrategy())->check($job, $jobStats, $config);

        if ($command instanceof KickCommandInterface) {
            $manager->kickJob($command->getJob());
            $this->notice(
                sprintf(
                    'Kicked id=%s queue=%s reason=%s',
                    $job->getId(),
                    $config->getQueue(),
                    $command->getReason()
                )
            );
        }

        if ($command instanceof DeleteCommandInterface) {
            $subscriber->delete($command->getJob());
            $this->notice(
                sprintf(
                    'Deleted id=%s queue=%s reason=%s',
                    $job->getId(),
                    $config->getQueue(),
                    $command->getReason()
                )
            );
        }
    }

    /**
     * @param string $class
     * @return BuryStrategyInterface
     * @throws BuryStrategyNotFoundException
     */
    private function getStrategy(string $class): BuryStrategyInterface
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
