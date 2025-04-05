<?php

namespace bus\consumer;

use bus\config\Config;
use bus\factory\TagsFactory;
use bus\interfaces\APMSenderInterface;
use Pheanstalk\Contract\JobIdInterface;
use Pheanstalk\Contract\PheanstalkManagerInterface;
use Pheanstalk\Contract\PheanstalkSubscriberInterface;
use Pheanstalk\Exception\JobNotFoundException;
use Pheanstalk\Values\JobState;
use Pheanstalk\Values\JobStats;
use Psr\Log\LoggerInterface;
use Throwable;

class ExceptionsHandler
{
    public function __construct(
        private LoggerInterface    $logger,
        private APMSenderInterface $apm,
        private TagsFactory        $tagsFactory,
    )
    {
    }

    /**
     * Don't forget that $statsJob and PheanstalkManagerInterface::statsJob differs
     *
     * @throws Throwable
     */
    public function handle(
        Throwable                                                $t,
        JobStats                                                 $jobStats,
        PheanstalkSubscriberInterface|PheanstalkManagerInterface $broker,
        JobIdInterface                                           $job,
        Config                                                   $config
    ): void
    {
        $this->logger->notice($t->getMessage());

        if (str_contains($t->getMessage(), 'gone away')) {
            $this->logger->notice($t->getTraceAsString());
            $this->apm->metricIncrement(Consumer::METRIC_JOB_EXCEPTION_CNT, $this->tagsFactory->create($config));

            throw $t;
        }

        if ($jobStats->reserves >= $config->getMaxRetry() + 1) {
            try {
                if (JobState::BURIED !== $broker->statsJob($job)->state) {
                    $broker->bury($job);
                    $this->apm->metricIncrement(Consumer::METRIC_JOB_BURY_CNT, $this->tagsFactory->create($config));
                }

                $this->logger->notice(sprintf('Buried id=%s', $job->getId()));
            } catch (JobNotFoundException $e) {
                $this->logger->error(
                    sprintf('Job not found id=%s, state=%s', $job->getId(), $broker->statsJob($job)->state->value)
                );
            }
        } else {
            if (JobState::BURIED !== $broker->statsJob($job)->state) {
                $broker->release($job, $config->getPriority(), $config->getDelay());
                $this->logger->notice('Repeated id=' . $job->getId() . ', reserves=' . $jobStats->reserves);
                $this->apm->metricIncrement(Consumer::METRIC_JOB_RELEASE_CNT, $this->tagsFactory->create($config));
            } else {
                $this->logger->notice('Ignored id=' . $job->getId());
                $this->apm->metricIncrement(Consumer::METRIC_JOB_IGNORE_CNT, $this->tagsFactory->create($config));
            }
        }
    }
}
