<?php

namespace App\Modules;

use App\Modules\History\HistoryModule;
use App\Modules\Legal\LegalModule;
use App\Modules\Auth\AuthModule;
use Modularize\Module;
class AppModule extends Module
{
    public function boot()
    {
        parent::boot();
    }
    public function register()
    {
        $this->provide(AuthModule::class);
        $this->provide(LegalModule::class);
        $this->provide(HistoryModule::class);
    }
}