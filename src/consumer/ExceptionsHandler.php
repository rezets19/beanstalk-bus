<?php

namespace bus\consumer;

use bus\factory\TagsFactory;
use bus\interfaces\APMSenderInterface;
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
     * @param $stats
     * @param $broker
     * @param $job
     * @return void
     * @throws Throwable
     */
    public function handle(Throwable $t, $stats, $broker, $job, $config): void
    {
        $this->logger->notice($t->getMessage());

        if (strpos($t->getMessage(), 'gone away') !== false) {
            $this->logger->notice($t->getTraceAsString());
            $this->apm->metricIncrement(Consumer::METRIC_JOB_EXCEPTION_CNT, $this->fTags->create($config));

            throw $t;
        }

        if ($stats->reserves >= $config->getMaxRetry() + 1) {
            if ('buried' !== $broker->statsJob($job)->state->value) {
                $broker->bury($job);
                $this->apm->metricIncrement(Consumer::METRIC_JOB_BURY_CNT, $this->fTags->create($config));
            }

            $this->logger->notice('Buried id=' . $job->getId());
        } else {
            if ('buried' !== $broker->statsJob($job)['state']) {
                $broker->release($job, $config->getPriority(), $config->getDelay());
                $this->logger->notice('Repeated id=' . $job->getId() . ', reserves=' . $stats->reserves);
                $this->apm->metricIncrement(Consumer::METRIC_JOB_RELEASE_CNT, $this->fTags->create($config));
            } else {
                $this->logger->notice('Ignored id=' . $job->getId());
                $this->apm->metricIncrement(Consumer::METRIC_JOB_IGNORE_CNT, $this->fTags->create($config));
            }
        }
    }
}
