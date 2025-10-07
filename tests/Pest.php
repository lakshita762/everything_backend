<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->in('Feature');

uses(RefreshDatabase::class)->in('Feature');
