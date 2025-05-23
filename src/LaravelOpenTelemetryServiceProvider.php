<?php

namespace Danny50610\LaravelOpenTelemetry;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use OpenTelemetry\API\Trace\Propagation\TraceContextPropagator;
use OpenTelemetry\Contrib\Otlp\LogsExporter;
use OpenTelemetry\Contrib\Otlp\MetricExporter;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Logs\LoggerProvider;
use OpenTelemetry\SDK\Logs\Processor\SimpleLogRecordProcessor;
use OpenTelemetry\SDK\Metrics\MeterProvider;
use OpenTelemetry\SDK\Metrics\MetricReader\ExportingReader;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SDK\Sdk;
use OpenTelemetry\SDK\Trace\Sampler\AlwaysOnSampler;
use OpenTelemetry\SDK\Trace\Sampler\ParentBased;
use OpenTelemetry\SDK\Trace\SpanProcessor\SimpleSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SemConv\ResourceAttributes;

class LaravelOpenTelemetryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/open-telemetry.php', 'open-telemetry'
        );

        if (config('open-telemetry.enable')) {
            $this->initOpenTelemetry();
        }
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/open-telemetry.php' => config_path('open-telemetry.php'),
        ]);
    }

    protected function initOpenTelemetry()
    {
        $otlpProtocol = config('open-telemetry.otlp.protocol');
        $otlpEndpoint = Str::finish(config('open-telemetry.otlp.endpoint'), '/');

        $resource = ResourceInfoFactory::defaultResource()->merge(ResourceInfo::create(Attributes::create([
            ResourceAttributes::SERVICE_NAME => config('app.name'),
        ])));

        $spanExporter = new SpanExporter(
            (new OtlpHttpTransportFactory())->create($otlpEndpoint . 'v1/traces', $otlpProtocol)
        );
        
        $logExporter = new LogsExporter(
            (new OtlpHttpTransportFactory())->create($otlpEndpoint . 'v1/logs', $otlpProtocol)
        );

        $reader = new ExportingReader(
            new MetricExporter(
                (new OtlpHttpTransportFactory())->create($otlpEndpoint . 'v1/metrics', $otlpProtocol)
            )
        );
        
        $meterProvider = MeterProvider::builder()
            ->setResource($resource)
            ->addReader($reader)
            ->build();
        
        $tracerProvider = TracerProvider::builder()
            ->addSpanProcessor(
                new SimpleSpanProcessor($spanExporter)
            )
            ->setResource($resource)
            ->setSampler(new ParentBased(new AlwaysOnSampler()))
            ->build();
        
        $loggerProvider = LoggerProvider::builder()
            ->setResource($resource)
            ->addLogRecordProcessor(
                new SimpleLogRecordProcessor($logExporter)
            )
            ->build();
        
        Sdk::builder()
            ->setTracerProvider($tracerProvider)
            ->setMeterProvider($meterProvider)
            ->setLoggerProvider($loggerProvider)
            ->setPropagator(TraceContextPropagator::getInstance())
            ->setAutoShutdown(true)
            ->buildAndRegisterGlobal();
    }
}
