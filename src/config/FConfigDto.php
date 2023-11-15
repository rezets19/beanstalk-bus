<?php

namespace bus\config;

class FConfigDto
{
    public function fromResult(array $result, string $class): ConfigDto
    {
        $dto = new ConfigDto();
        $dto->setClass($class);
        $dto->setAsync((bool)$result['async']);
        $dto->setHandlers($result['handlers']);

        $config = $result['queue_config'];

        if (isset($config['name'])) {
            $dto->setQueue($config['name']);
        }

        if (isset($config['driver'])) {
            $dto->setDriver($config['driver']);
        }

        if (isset($config['delay'])) {
            $dto->setDelay((int)$config['delay']);
        }

        if (isset($config['ttr'])) {
            $dto->setTtr((int)$config['ttr']);
        }

        if (isset($config['maxRetry'])) {
            $dto->setMaxRetry((int)$config['maxRetry']);
        }

        if (isset($config['maxAge'])) {
            $dto->setMaxAge((int)$config['maxAge']);
        }

        if (isset($config['maxKicks'])) {
            $dto->setMaxKicks((int)$config['maxKicks']);
        }

        if (isset($config['buryStrategy'])) {
            $dto->setBuryStrategy($config['buryStrategy']);
        }

        if (isset($result['exceptions']['fatal'])) {
            $dto->setFatal($result['exceptions']['fatal']);
        }

        return $dto;
    }
}
