<?php

namespace bus\consumer;

use bus\common\Arrays;
use bus\config\ConfigNotFoundException;
use bus\config\Provider;
use bus\exception\BrokerNotFoundException;
use bus\exception\HandlerNotFoundException;
use bus\factory\TagsFactory;
use bus\interfaces\APMSenderInterface;
use bus\message\QMessageFactory;
use bus\message\Processor;
use Pheanstalk\Contract\PheanstalkManagerInterface;
use Pheanstalk\Contract\PheanstalkPublisherInterface;
use Pheanstalk\Contract\PheanstalkSubscriberInterface;
use Pheanstalk\Exception\DeadlineSoonException;
use Psr\Log\LoggerInterface;
use ReflectionException;
use Throwable;

class Consumer
{
    public const DEADLINE_MICRO_SECONDS = 500000;
    public const RESERVE_TIMEOUT = 60;
    public const METRIC_JOB_PICK_CNT = 'beanstalk_job_pick_cnt';
    public const METRIC_JOB_EXECUTE_CNT = 'beanstalk_job_execute_cnt';
    public const METRIC_JOB_EXCEPTION_CNT = 'beanstalk_job_exception_cnt';
    public const METRIC_JOB_BURY_CNT = 'beanstalk_job_bury_cnt';
    public const METRIC_JOB_RELEASE_CNT = 'beanstalk_job_release_cnt';
    public const METRIC_JOB_IGNORE_CNT = 'beanstalk_job_ignore_cnt';

    public function __construct(
        private LoggerInterface    $logger,
        private QMessageFactory    $messageFactory,
        private Provider           $configProvider,
        private APMSenderInterface $apm,
        private TagsFactory        $tagsFactory,
        private Processor          $processor,
        private Arrays             $arrays,
        private ExceptionsHandler  $exceptionsHandler
    ) {
    }

    /**
     * @throws HandlerNotFoundException
     * @throws Throwable
     * @throws ReflectionException
     * @throws ConfigNotFoundException
     * @throws BrokerNotFoundException
     */
    public function consume(
        string $queueName,
        PheanstalkManagerInterface|PheanstalkPublisherInterface|PheanstalkSubscriberInterface $broker
    ): void {
        try {
            $this->logger->notice(sprintf('Watching tube=%s', $queueName));

            $job = $broker->reserveWithTimeout(self::RESERVE_TIMEOUT);

            if ($job === null) {
                return;
            }

            $stats = $broker->statsJob($job);
            $message = $this->messageFactory->fromString($job->getData());
            $config = $this->configProvider->getByJob($message->getJob());

            $this->apm->metricIncrement(self::METRIC_JOB_PICK_CNT, $this->tagsFactory->create($config));

            $this->logger->notice(
                sprintf('Consumed id=%s %s', $job->getId(), ($config->isCritical() ? ', critical' : ''))
            );

            if ($config->isCritical()) {
                $broker->bury($job);
            }

            try {
                $this->processor->process($message);
                $broker->delete($job);
                $this->apm->metricIncrement(self::METRIC_JOB_EXECUTE_CNT, $this->tagsFactory->create($config));
            } catch (HandlerNotFoundException $e) {
                $broker->delete($job);
                $this->logger->notice($e->getMessage());
            } catch (Throwable $t) {
                if ($this->arrays->classExist($t, $config->getFatal())) {
                    $broker->delete($job);
                    $this->logger->notice(sprintf('Fatal error, job deleted=%s', $job->getId()));
                    $this->logger->notice($t->getMessage());

                    return;
                }

                $this->exceptionsHandler->handle($t, $stats, $broker, $job, $config);
            }

        } catch (DeadlineSoonException $e) {
            $this->logger->notice(sprintf('Deadline soon: %s', $e->getMessage()));
            usleep(self::DEADLINE_MICRO_SECONDS);
        }
    }
}
