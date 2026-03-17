<?php

namespace Cesa\Shelf\Http\Controllers;

use Cesa\Shelf\Support\ShelfManagedFileRegistry;
use Illuminate\Database\Eloquent\SoftDeletes;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivateAttachmentDownloadController extends Controller
{
    public function __invoke(string $type, int $record, string $attribute, ?int $index = null): StreamedResponse
    {
        $modelClass = ShelfManagedFileRegistry::modelForType($type);
        abort_if($modelClass === null, 404);

        $query = $modelClass::query();

        if (in_array(SoftDeletes::class, class_uses_recursive($modelClass), true)) {
            $query->withTrashed();
        }

        $model = $query->findOrFail($record);
        abort_unless(method_exists($model, 'managedFileResponse'), 404);

        return $model->managedFileResponse($attribute, $index);
    }
}
