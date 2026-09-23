<?php

declare(strict_types=1);

namespace CXEngine\ExpertStatistics\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use CXEngine\ExpertStatistics\ExpertStatisticsServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use Prism\Prism\PrismServiceProvider;

/**
 * Testbench bootstrap, used only by tests that genuinely need the Laravel
 * container (config(), the Prism facade, translations) - e.g. the docs
 * assistant. Everything else in this package (PbxDataProcessor, DocsCatalog)
 * runs as plain framework-free Pest tests and doesn't need this.
 */
abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            PrismServiceProvider::class,
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            ExpertStatisticsServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }
}
