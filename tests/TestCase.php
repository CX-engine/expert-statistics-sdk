<?php

declare(strict_types=1);

namespace CXEngine\ExpertStatistics\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use CXEngine\ExpertStatistics\Contracts\ResolvesActivePbxHost;
use CXEngine\ExpertStatistics\ExpertStatisticsServiceProvider;
use CXEngine\ExpertStatistics\Tests\Doubles\FakeActivePbxHostResolver;
use Illuminate\Support\Facades\Blade;
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

        // The real implementation is bound by the host app (bluerocktel-cx),
        // never by this package itself - no host app is present in tests,
        // so anything resolving ExpertStatisticsService (directly or via a
        // dependency) needs this stand-in. Rebind per-test for different behavior.
        $app->bind(ResolvesActivePbxHost::class, FakeActivePbxHostResolver::class);
    }

    /**
     * Registers the stand-ins for the host app's <x-pages.index> and
     * <x-menus.*> components. Always through this one resolved path: Blade
     * keys anonymous component paths by a hash of the path string and bakes
     * that hash into compiled views, which are shared across tests - two
     * spellings of the same directory would make each other's views fail.
     */
    protected function useHostComponentDoubles(): void
    {
        Blade::anonymousComponentPath((string) realpath(__DIR__.'/Doubles/views/components'));
    }
}
