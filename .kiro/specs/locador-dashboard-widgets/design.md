# Design Document: Locador Dashboard Widgets

## Overview

This design document specifies the implementation of two dashboard widgets for the Locador (Landlord) Filament panel: a Properties Count Widget and a Leads Widget. These widgets will provide landlords with quick visibility into key metrics on their dashboard.

The Properties Count Widget will display the actual count of properties owned by the authenticated landlord, while the Leads Widget will initially display placeholder data (0) until the Lead model is implemented in a future iteration.

### Key Design Decisions

1. **Widget Type Selection**: Use Filament's `StatsOverviewWidget` base class, which provides a clean, card-based UI for displaying statistical information with labels and numeric values.

2. **Auto-Discovery Pattern**: Leverage Filament's existing `discoverWidgets()` configuration in the LocadorPanelProvider to automatically register widgets without manual configuration.

3. **Query Efficiency**: Use Laravel's `count()` method directly on the query builder to avoid loading full model instances, ensuring optimal performance.

4. **Authentication Context**: Utilize Filament's built-in authentication context (`auth()->id()`) to filter properties by the currently logged-in landlord.

5. **Placeholder Strategy**: Implement the Leads Widget with hardcoded placeholder data and clear TODO comments to facilitate future implementation when the Lead model is created.

## Architecture

### Component Structure

```
app/Filament/Locador/Widgets/
├── PropertiesOverview.php    # Properties count widget
└── LeadsOverview.php          # Leads placeholder widget
```

### Class Hierarchy

```
Filament\Widgets\StatsOverviewWidget (Base Class)
    ├── PropertiesOverview
    └── LeadsOverview
```

### Data Flow

1. **Dashboard Load**: User navigates to /locador dashboard
2. **Widget Discovery**: Filament auto-discovers widgets in app/Filament/Locador/Widgets
3. **Widget Rendering**: Each widget's `getStats()` method is called
4. **Data Retrieval**: 
   - PropertiesOverview queries Property model filtered by user_id
   - LeadsOverview returns hardcoded placeholder value
5. **Display**: Widgets render as stat cards on the dashboard

### Integration Points

- **Filament Panel System**: Widgets integrate with the Locador panel via auto-discovery
- **Authentication System**: Widgets access the authenticated user via `auth()->id()`
- **Property Model**: PropertiesOverview queries the Property model
- **Future Lead Model**: LeadsOverview prepared for future integration (placeholder for now)

## Components and Interfaces

### PropertiesOverview Widget

**Purpose**: Display the count of properties owned by the authenticated landlord.

**Class**: `App\Filament\Locador\Widgets\PropertiesOverview`

**Base Class**: `Filament\Widgets\StatsOverviewWidget`

**Key Methods**:
- `getStats()`: Returns an array of Stat objects to display

**Dependencies**:
- `App\Models\Property`: For querying property count
- `Filament\Widgets\StatsOverviewWidget\Stat`: For creating stat cards
- `auth()`: For accessing authenticated user ID

**Implementation Pattern**:
```php
protected function getStats(): array
{
    return [
        Stat::make('Properties', Property::where('user_id', auth()->id())->count())
            ->description('Total properties in your portfolio')
            ->icon('heroicon-o-home'),
    ];
}
```

### LeadsOverview Widget

**Purpose**: Display a placeholder for future lead tracking functionality.

**Class**: `App\Filament\Locador\Widgets\LeadsOverview`

**Base Class**: `Filament\Widgets\StatsOverviewWidget`

**Key Methods**:
- `getStats()`: Returns an array of Stat objects with placeholder data

**Dependencies**:
- `Filament\Widgets\StatsOverviewWidget\Stat`: For creating stat cards

**Implementation Pattern**:
```php
protected function getStats(): array
{
    // TODO: Replace with actual lead count when Lead model is implemented
    // Query should be: Lead::where('user_id', auth()->id())->count()
    return [
        Stat::make('Leads', 0)
            ->description('Placeholder - Lead model not yet implemented')
            ->icon('heroicon-o-user-group'),
    ];
}
```

### Widget Auto-Discovery

**Mechanism**: The LocadorPanelProvider already includes:
```php
->discoverWidgets(in: app_path('Filament/Locador/Widgets'), for: 'App\\Filament\\Locador\\Widgets')
```

This configuration automatically discovers and registers any widget classes placed in the `app/Filament/Locador/Widgets` directory.

**Registration Flow**:
1. Filament scans the Widgets directory
2. Finds classes extending Widget base classes
3. Automatically registers them for display on the dashboard
4. No manual registration required

## Data Models

### Property Model (Existing)

**Table**: `properties`

**Relevant Fields**:
- `id`: Primary key
- `user_id`: Foreign key to users table (landlord owner)
- `title`: Property title
- `description`: Property description
- `price`: Property price
- `location`: Property location
- `details`: JSON field for additional details
- `photos`: JSON field for property photos

**Relationship**:
- `belongsTo(User::class)`: Each property belongs to one user (landlord)

**Query Pattern for Widget**:
```php
Property::where('user_id', auth()->id())->count()
```

### Lead Model (Future)

**Status**: Not yet implemented

**Expected Structure**:
- `id`: Primary key
- `user_id`: Foreign key to users table (landlord receiving the lead)
- `name`: Lead contact name
- `email`: Lead contact email
- `phone`: Lead contact phone
- `property_id`: Foreign key to properties table (optional)
- `message`: Lead message/inquiry
- `status`: Lead status (new, contacted, converted, etc.)
- `created_at`: Timestamp
- `updated_at`: Timestamp

**Expected Relationship**:
- `belongsTo(User::class)`: Each lead belongs to one landlord
- `belongsTo(Property::class)`: Each lead may be associated with a property

**Future Query Pattern**:
```php
Lead::where('user_id', auth()->id())->count()
```

## 