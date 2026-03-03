<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $storagePath = realpath(base_path('storage')) ?: base_path('storage');
        
        $this->app->useStoragePath($storagePath);

        config([
            'view.compiled' => $storagePath . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'views',
            'cache.stores.file.path' => $storagePath . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'data',
        ]);
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);
        $requiredFolders = [
            storage_path('framework/views'),
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('app/public/profiles'),
        ];

        foreach ($requiredFolders as $folder) {
            if (!file_exists($folder)) {
                mkdir($folder, 0775, true);
            }
        }
    }
}