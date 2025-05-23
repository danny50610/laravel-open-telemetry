<?php

namespace Danny50610\LaravelOpenTelemetry\Logger;

use Illuminate\Log\ParsesLogConfiguration;
use Illuminate\Support\Facades\App;
use Monolog\Logger;
use OpenTelemetry\API\Globals;

class OpenTelemetryLogger
{
    use ParsesLogConfiguration;

    public function __invoke(array $config): Logger
    {
        $handler = new \OpenTelemetry\Contrib\Logs\Monolog\Handler(
            Globals::loggerProvider(),
            $this->level($config),
        );

        return new Logger(
            $this->parseChannel($config),
            [
                $handler,
            ]
        );
    }

    protected function getFallbackChannelName()
    {
        return App::bound('env') ? App::environment() : 'production';
    }
}