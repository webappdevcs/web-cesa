# Exit Clearance Request Form

## Overview

This implementation provides a modern, multi-step public form for Exit Clearance requests. The form features:

- **Multi-step sections** - Data grouped into clear steps
- **Step indicator** - Shows current step out of total
- **Required markers** - Highlights mandatory inputs
- **Custom layout** - No Filament panel wrapper, full design control
- **Mobile responsive** - Optimized for all screen sizes
- **Accessibility** - Keyboard navigation and screen reader support

## Files

### 1. Livewire Component
**File**: `plugins/cesa/exit-clearance/src/Livewire/PublicExitClearanceRequestForm.php`

This component extends `Livewire\Component` and implements `HasForms`. It maintains all the original functionality:

- Form validation
- File upload handling
- Data submission
- Email notifications
- Workflow management

### 2. Blade View
**File**: `plugins/cesa/exit-clearance/resources/views/livewire/public-exit-clearance-request-form.blade.php`

Custom Blade view with:
- Tailwind CSS styling
- Livewire + Filament form rendering
- Multi-step layout

### 3. Route
**File**: `plugins/cesa/exit-clearance/routes/web.php`

Route:
```php
Route::get('exit-clearance', PublicExitClearanceRequestForm::class)
    ->name('exit-clearance.public.form');
```

## Access the Form

Visit: `http://your-domain.com/exit-clearance`

## Key Design Principles

### 1. Multi-Step Wizard

The form is divided into 4 steps:
1. **Surat Resign** - Upload resignation letter (optional)
2. **Data Diri** - Personal details, department, dates
3. **Exit Interview** - Feedback questions
4. **Exit Clearance** - Clearance checklist items

### 2. Progress Tracking

- Step counter ("Halaman 1 dari 4")
- Clear next/previous navigation

### 3. Validation

- Required fields clearly marked with `*`
- Client-side validation before proceeding
- Server-side validation from Filament forms
- Disabled buttons when invalid

### 4. Mobile Optimization

- Responsive breakpoints (mobile-first)
- Touch-friendly targets (44px minimum)
- Full-width inputs on mobile
- Proper spacing for touch gestures

## Customization

### Colors

Primary colors used:
- Primary: `#673AB7`
- Accent: `#A52714`
- Required: `#D93025`
- Background: `#F0EBF8`
- Surface: `#FFFFFF`

To change colors, modify the Tailwind classes in the Blade view:

```blade
<div class="border-t-[10px] border-[#673AB7] ...">
<button class="bg-[#673AB7] ...">
<div class="bg-[#A52714] ...">
```

### Step Layout

To add or remove steps, update:
- `$totalSteps` in `PublicExitClearanceRequestForm`
- Step headers in the Blade view

## Technical Details

### Filament Integration

Although using a custom layout, the form still leverages Filament's power:

1. **Form Components** - Use Filament's `TextInput`, `Select`, `FileUpload`, `DatePicker`, `Textarea`
2. **Validation** - Server-side validation from Filament
3. **State Management** - Livewire handles form state
4. **File Upload** - Filament's `FileUpload` component for file handling

### Livewire State Management

```php
public int $currentStep = 1;
protected int $totalSteps = 4;
public ?array $data = [];
```

### Keyboard Shortcuts

- `Enter` - Submit form (on last step)
- `Escape` - Previous step (if supported)

## Comparison with Original Form

| Feature | Original (Filament) | Public Multi-step |
|---------|----------------------|------------------|
| Layout | All sections visible | Step-based sections |
| Progress | None | Step counter |
| Design | Filament default | Custom layout |
| Responsiveness | Filament responsive | Mobile-optimized |
| Validation | Same | Same |
| Functionality | Same | Same |

## Performance

- Minimal JavaScript overhead (Livewire only)
- No additional dependencies
- Lazy loading of steps (only visible step in DOM)
- Optimized CSS (Tailwind JIT)

## Accessibility

- ✅ Keyboard navigation support
- ✅ Screen reader friendly (ARIA labels)
- ✅ Reduced motion support
- ✅ Focus indicators
- ✅ Sufficient color contrast
- ✅ Touch-friendly targets (44px+)

## Future Enhancements

Potential improvements:

1. **Auto-save** - Save draft automatically
2. **Conditional logic** - Show/hide fields based on answers
3. **Progress persistence** - Resume from saved position
4. **Dark mode** - Support dark theme
5. **Multi-language** - Support multiple languages
6. **File preview** - Show uploaded file previews
7. **Validation feedback** - Real-time validation messages

## Troubleshooting

### Form not rendering

Check:
- Route is registered in `routes/web.php`
- Livewire component class exists
- Blade view path is correct
- Livewire scripts are loaded
- No JavaScript errors in console

### Validation not working

Verify:
- Validation rules in `PublicExitClearanceRequestForm`
- Field names match between Livewire state and form schema
- File upload configuration is correct

## Support

For issues or questions:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Check Livewire logs in browser console
3. Verify Filament component configuration
4. Test with original form for comparison

## License

This implementation follows the same license as the AureusERP project (MIT).
