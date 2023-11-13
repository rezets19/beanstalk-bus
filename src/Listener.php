<?php

namespace bus;

use bus\broker\Bury;
use bus\broker\FBroker;
use bus\common\Restarter;
use bus\config\ConfigNotFoundException;
use bus\config\Provider;
use bus\exception\BrokerNotFoundException;
use bus\exception\HandlerNotFoundException;
use bus\factory\FTags;
use bus\interfaces\APMSenderInterface;
use bus\message\FQMessage;
use bus\message\Processor;
use Exception;
use InvalidArgumentException;
use Pheanstalk\Exception\DeadlineSoonException;
use Pheanstalk\Pheanstalk;
use Pheanstalk\Values\Job;
use Pheanstalk\Values\TubeName;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Main background process
 *
 * Class Listener
 * @package bus
 */
class Listener
{
    public const DEADLINE_MICRO_SECONDS = 500000;

    /**
     * Seconds
     */
    public const RESERVE_TIMEOUT = 60;

    public const METRIC_JOB_PICK_CNT = 'beanstalk_job_pick_cnt';
    public const METRIC_JOB_EXECUTE_CNT = 'beanstalk_job_execute_cnt';
    public const METRIC_JOB_EXCEPTION_CNT = 'beanstalk_job_exception_cnt';
    public const METRIC_JOB_BURY_CNT = 'beanstalk_job_bury_cnt';
    public const METRIC_JOB_RELEASE_CNT = 'beanstalk_job_release_cnt';
    public const METRIC_JOB_IGNORE_CNT = 'beanstalk_job_ignore_cnt';

    private string $queue;

    private Provider $configProvider;

    private FBroker $fbroker;

    private Processor $processor;

    private LoggerInterface $logger;

    private $console;

    private FQMessage $messageFactory;

    private Bury $bury;

    private Restarter $restarter;

    private APMSenderInterface $apm;

    private FTags $fTags;

    public function __construct(
        string $queue,
        LoggerInterface $logger,
        Provider $configProvider,
        Processor $processor,
        FBroker $fbroker,
        FQMessage $messageFactory,
        Restarter $restarter,
        Bury $bury,
        APMSenderInterface $apm,
        FTags $fTags
    ) {
        $this->queue = $queue;
        $this->logger = $logger;
        $this->configProvider = $configProvider;
        $this->processor = $processor;
        $this->fbroker = $fbroker;
        $this->messageFactory = $messageFactory;
        $this->restarter = $restarter;
        $this->bury = $bury;
        $this->apm = $apm;
        $this->fTags = $fTags;

        $this->validate();
    }

    /**
     * @return void
     */
    public function listen(): void
    {
        $broker = $this->fbroker->get(FBroker::DRIVER_BEANSTALK);

        $queueName = new TubeName($this->queue);

        $broker->useTube($queueName);
        $broker->watch($queueName);

        while (true) {
            $this->consume($broker);
        }
    }

    /**
     * @param Pheanstalk $broker
     * @throws HandlerNotFoundException
     * @throws Throwable
     * @throws \ReflectionException
     * @throws ConfigNotFoundException
     * @throws BrokerNotFoundException
     */
    public function consume(Pheanstalk $broker): void
    {
        if ($this->restarter->restart()) {
            $this->notice('Restart attempt received, shutting down');

            throw new Exception('restart');
        }

        $this->bury->check($broker);

        try {
            //$this->console->warningWithBold('Watching tube=' . $this->queue);

            $job = $broker->reserveWithTimeout(self::RESERVE_TIMEOUT);

            if ($job instanceof Job) {
                $stats = $broker->statsJob($job);
                $message = $this->messageFactory->fromString($job->getData());
                $config = $this->configProvider->getByJob($message->getJob());

                $this->apm->metricIncrement(self::METRIC_JOB_PICK_CNT, $this->fTags->create($config));

                /*$this->console->warningWithBold(
                    'Consumed id=' . $job->getId() . ', critical=' . (int)$config->isCritical()
                );*/

                if ($config->isCritical()) {
                    $broker->bury($job);
                }

                try {
                    $this->processor->process($message);
                    $broker->delete($job);
                    $this->apm->metricIncrement(self::METRIC_JOB_EXECUTE_CNT, $this->fTags->create($config));
                } catch (HandlerNotFoundException $e) {
                    $broker->delete($job);
                    $this->notice($e->getMessage());
                } catch (Throwable $t) {

                    //TODO: add exceptions check form list

                    $this->notice($t->getMessage());

                    if (strpos($t->getMessage(), 'gone away') !== false) {
                        $this->notice($t->getTraceAsString());
                        $this->apm->metricIncrement(self::METRIC_JOB_EXCEPTION_CNT, $this->fTags->create($config));

                        throw $t;
                    }

                    if ($stats->reserves >= $config->getMaxRetry() + 1) {
                        if ('buried' !== $broker->statsJob($job)->state->value) {
                            $broker->bury($job);
                            $this->apm->metricIncrement(self::METRIC_JOB_BURY_CNT, $this->fTags->create($config));
                        }

                        $this->notice('Buried id=' . $job->getId());
                    } else {
                        if ('buried' !== $broker->statsJob($job)['state']) {
                            $broker->release($job, $config->getPriority(), $config->getDelay());
                            $this->notice('Repeated id=' . $job->getId() . ', reserves=' . $stats->reserves);
                            $this->apm->metricIncrement(self::METRIC_JOB_RELEASE_CNT, $this->fTags->create($config));
                        } else {
                            $this->notice('Ignored id=' . $job->getId());
                            $this->apm->metricIncrement(self::METRIC_JOB_IGNORE_CNT, $this->fTags->create($config));
                        }
                    }
                }
            }
        } catch (DeadlineSoonException $e) {
            $this->notice('Deadline soon: ' . $e->getMessage());
            usleep(self::DEADLINE_MICRO_SECONDS);
        }
    }

    private function notice(string $message): void
    {
        $this->logger->notice($message);
        //$this->console->warning($message);
    }

    private function validate(): void
    {
        if (empty($this->queue)) {
            throw new InvalidArgumentException('Queue is empty');
        }
    }
}
