<?php

namespace Oadsoft\Crmcore;

use Illuminate\Support\ServiceProvider;

class CrmcoreServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/project.php' => config_path('project.php'),
        ]);
    }
}