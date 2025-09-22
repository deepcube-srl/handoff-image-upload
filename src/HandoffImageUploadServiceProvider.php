<?php

namespace Deepcube\HandoffImageUpload;

use Deepcube\HandoffImageUpload\Commands\CleanupTempImagesCommand;
use Deepcube\HandoffImageUpload\Commands\HandoffImageUploadCommand;
use Deepcube\HandoffImageUpload\Livewire\HandoffImageUploadComponent;
use Deepcube\HandoffImageUpload\Testing\TestsHandoffImageUpload;
use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Filesystem\Filesystem;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class HandoffImageUploadServiceProvider extends PackageServiceProvider
{
    public static string $name = 'handoff-image-upload';

    public static string $viewNamespace = 'handoff-image-upload';

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package->name(static::$name)
            ->hasCommands($this->getCommands())
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('deepcube/handoff-image-upload');
            });

        $configFileName = $package->shortName();

        if (file_exists($package->basePath("/../config/{$configFileName}.php"))) {
            $package->hasConfigFile();
        }

        if (file_exists($package->basePath('/../database/migrations'))) {
            $package->hasMigrations($this->getMigrations());
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageRegistered(): void {}

    public function packageBooted(): void
    {
        // Asset Registration
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        // Icon Registration
        FilamentIcon::register($this->getIcons());

        // Handle Stubs
        if (app()->runningInConsole()) {
            foreach (app(Filesystem::class)->files(__DIR__ . '/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/handoff-image-upload/{$file->getFilename()}"),
                ], 'handoff-image-upload-stubs');
            }
        }

        // Register Livewire components
        Livewire::component('handoff-image-upload', HandoffImageUploadComponent::class);

        // Testing
        Testable::mixin(new TestsHandoffImageUpload);

        $this->registerRoutes();
    }

    protected function getAssetPackageName(): ?string
    {
        return 'deepcube/handoff-image-upload';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [
            // AlpineComponent::make('handoff-image-upload', __DIR__ . '/../resources/dist/components/handoff-image-upload.js'),
            Css::make('handoff-image-upload-styles', __DIR__ . '/../resources/dist/handoff-image-upload.css'),
            Js::make('handoff-image-upload-scripts', __DIR__ . '/../resources/dist/handoff-image-upload.js'),
        ];
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            HandoffImageUploadCommand::class,
            CleanupTempImagesCommand::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected function getIcons(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getRoutes(): array
    {
        return [
            'web',
            'api',
        ];
    }

    /**
     * Register the package routes.
     */
    protected function registerRoutes(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getScriptData(): array
    {
        return [];
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            'create_handoff-image-upload_table',
        ];
    }
}
