# Presensi Plugin

## Integration with User Resource

To enable the Schedules management on the User Resource, you need to register the `SchedulesRelationManager` in your `UserResource` class.

Since `UserResource` is typically provided by the `webkul/security` plugin (or your own implementation), you need to register the relation manager manually.

### Manual Registration

Open `plugins/webkul/security/src/Filament/Resources/UserResource.php` (or wherever your `UserResource` is located) and add the relation manager to the `getRelations` method:

```php
use Cesa\Presensi\Filament\Resources\UserResource\RelationManagers\SchedulesRelationManager;

public static function getRelations(): array
{
    return [
        SchedulesRelationManager::class,
    ];
}
```

### Auto-Injection

The `schedules` relationship is automatically injected into the `App\Models\User` model by the `PresensiServiceProvider`, so you don't need to modify the User model directly.
