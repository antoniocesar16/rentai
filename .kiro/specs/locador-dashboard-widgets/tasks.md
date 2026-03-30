# Implementation Plan: Locador Dashboard Widgets

## Overview

This implementation plan covers the creation of two dashboard widgets for the Locador (Landlord) Filament panel: a Properties Count Widget that displays the actual count of properties owned by the authenticated landlord, and a Leads Widget that displays placeholder data until the Lead model is implemented. Both widgets will use Filament's StatsOverviewWidget base class and will be auto-discovered by the panel.

## Tasks

- [x] 1. Create Widgets directory structure
  - Create the app/Filament/Locador/Widgets directory if it doesn't exist
  - _Requirements: 1.5, 2.4, 3.1_

- [ ] 2. Implement Properties Count Widget
  - [x] 2.1 Create PropertiesOverview widget class
    - Create app/Filament/Locador/Widgets/PropertiesOverview.php
    - Extend Filament\Widgets\StatsOverviewWidget
    - Implement getStats() method to query Property model filtered by authenticated user
    - Use Property::where('user_id', auth()->id())->count() for efficient counting
    - Add appropriate label, description, and icon (heroicon-o-home)
    - _Requirements: 1.1, 1.2, 1.5, 4.1, 4.3, 5.1, 5.2, 5.3_
  
  - [ ]* 2.2 Write unit tests for PropertiesOverview widget
    - Test that widget returns correct count for authenticated user
    - Test that widget returns 0 when user has no properties
    - Test that widget only counts properties belonging to authenticated user
    - _Requirements: 5.1, 5.3_

- [ ] 3. Implement Leads Widget
  - [x] 3.1 Create LeadsOverview widget class
    - Create app/Filament/Locador/Widgets/LeadsOverview.php
    - Extend Filament\Widgets\StatsOverviewWidget
    - Implement getStats() method returning hardcoded value of 0
    - Add TODO comment indicating Lead model needs to be created
    - Add appropriate label, description, and icon (heroicon-o-user-group)
    - _Requirements: 2.1, 2.2, 2.4, 2.5, 4.2, 4.4_
  
  - [ ]* 3.2 Write unit tests for LeadsOverview widget
    - Test that widget returns placeholder value of 0
    - Test that widget renders correctly
    - _Requirements: 2.1_

- [x] 4. Verify widget auto-discovery and registration
  - Verify that LocadorPanelProvider has discoverWidgets() configuration
  - Confirm widgets appear on the dashboard at /locador
  - Test that both widgets display correctly side by side
  - _Requirements: 3.1, 3.2, 3.3, 1.3, 2.3_

- [-] 5. Checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- The Widgets directory will be auto-discovered by Filament's panel configuration
- The LeadsOverview widget is intentionally a placeholder until the Lead model is implemented
- Performance requirement (5.4) of 500ms response time should be validated during manual testing
