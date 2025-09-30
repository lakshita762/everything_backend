<?php

namespace App\Providers;

use App\Models\LocationShare;
use App\Models\TodoList;
use App\Policies\LocationSharePolicy;
use App\Policies\TodoListPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected $policies = [
        TodoList::class => TodoListPolicy::class,
        LocationShare::class => LocationSharePolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}