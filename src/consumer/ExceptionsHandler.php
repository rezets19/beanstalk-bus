<?php

namespace bus\consumer;

use bus\config\Config;
use bus\factory\TagsFactory;
use bus\interfaces\APMSenderInterface;
use Pheanstalk\Contract\JobIdInterface;
use Pheanstalk\Contract\PheanstalkSubscriberInterface;
use Pheanstalk\Values\JobState;
use Pheanstalk\Values\JobStats;
use Psr\Log\LoggerInterface;
use Throwable;

class ExceptionsHandler
{
    public function __construct(
        private LoggerInterface    $logger,
        private APMSenderInterface $apm,
        private TagsFactory        $fTags,
    )
    {
    }

    /**
     * @param Throwable $t
     * @param JobStats $jobStats
     * @param PheanstalkSubscriberInterface $broker
     * @param JobIdInterface $job
     * @param Config $config
     * @return void
     * @throws Throwable
     */
    public function handle(
        Throwable                     $t,
        JobStats                      $jobStats,
        PheanstalkSubscriberInterface $broker,
        JobIdInterface                $job,
        Config                        $config
    ): void
    {
        $this->logger->notice($t->getMessage());

        if (str_contains($t->getMessage(), 'gone away')) {
            $this->logger->notice($t->getTraceAsString());
            $this->apm->metricIncrement(Consumer::METRIC_JOB_EXCEPTION_CNT, $this->fTags->create($config));

            throw $t;
        }

        if ($jobStats->reserves >= $config->getMaxRetry() + 1) {
            if (JobState::BURIED !== $jobStats->state) {
                $broker->bury($job);
                $this->apm->metricIncrement(Consumer::METRIC_JOB_BURY_CNT, $this->fTags->create($config));
            }

            $this->logger->notice('Buried id=' . $job->getId());
        } else {
            if (JobState::BURIED !== $jobStats->state) {
                $broker->release($job, $config->getPriority(), $config->getDelay());
                $this->logger->notice('Repeated id=' . $job->getId() . ', reserves=' . $jobStats->reserves);
                $this->apm->metricIncrement(Consumer::METRIC_JOB_RELEASE_CNT, $this->fTags->create($config));
            } else {
                $this->logger->notice('Ignored id=' . $job->getId());
                $this->apm->metricIncrement(Consumer::METRIC_JOB_IGNORE_CNT, $this->fTags->create($config));
            }
        }
    }
}
