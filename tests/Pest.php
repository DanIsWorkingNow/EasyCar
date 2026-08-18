<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closest ancestor a test can be scoped to, per directory. Binds every
| Feature test to Tests\TestCase (which boots the Laravel application via
| createApplication()) so traits like RefreshDatabase — and Eloquent itself —
| actually have a working app container. Without this file, `php artisan
| pest:install` failing silently left Pest test files running without any
| Laravel bootstrap at all: `static::$resolver` on Eloquent\Model stayed
| null, and any query threw "Call to a member function connection() on null".
|
*/

uses(TestCase::class)->in('Feature');
