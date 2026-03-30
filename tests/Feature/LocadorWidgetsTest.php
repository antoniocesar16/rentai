<?php

use App\Filament\Locador\Widgets\PropertiesOverview;
use App\Filament\Locador\Widgets\LeadsOverview;

test('properties overview widget class exists and extends correct base class', function () {
    expect(class_exists(PropertiesOverview::class))->toBeTrue();
    expect(is_subclass_of(PropertiesOverview::class, 'Filament\Widgets\StatsOverviewWidget'))->toBeTrue();
});

test('leads overview widget class exists and extends correct base class', function () {
    expect(class_exists(LeadsOverview::class))->toBeTrue();
    expect(is_subclass_of(LeadsOverview::class, 'Filament\Widgets\StatsOverviewWidget'))->toBeTrue();
});

test('properties overview widget has getStats method', function () {
    expect(method_exists(PropertiesOverview::class, 'getStats'))->toBeTrue();
});

test('leads overview widget has getStats method', function () {
    expect(method_exists(LeadsOverview::class, 'getStats'))->toBeTrue();
});
