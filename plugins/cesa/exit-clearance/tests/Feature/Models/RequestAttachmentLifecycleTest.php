<?php

namespace Cesa\ExitClearance\Tests\Feature\Models;

use Cesa\ExitClearance\Models\Request;
use Cesa\ExitClearance\Tests\ExitClearanceTestCase;
use Illuminate\Support\Facades\Storage;

class RequestAttachmentLifecycleTest extends ExitClearanceTestCase
{
    public function test_resignation_letter_is_renamed_replaced_and_removed(): void
    {
        Storage::fake('local');

        config()->set('filesystems.default', 'local');
        config()->set('filament.default_filesystem_disk', 'local');

        Storage::disk('local')->put('resignation-letters/tmp-letter-a.pdf', 'letter-a');

        $request = Request::query()->create([
            'name'                   => 'Jane Doe',
            'email'                  => 'jane@example.com',
            'resignation_letter_url' => 'resignation-letters/tmp-letter-a.pdf',
        ])->fresh();

        $this->assertNotNull($request);
        $this->assertMatchesRegularExpression(
            '#^resignation-letters/EXC-00001-[a-z0-9]{6}\.pdf$#',
            (string) $request->resignation_letter_url
        );

        $firstStoredPath = (string) $request->resignation_letter_url;

        Storage::disk('local')->assertMissing('resignation-letters/tmp-letter-a.pdf');
        Storage::disk('local')->assertExists($firstStoredPath);

        Storage::disk('local')->put('resignation-letters/tmp-letter-b.pdf', 'letter-b');

        $request->update([
            'resignation_letter_url' => 'resignation-letters/tmp-letter-b.pdf',
        ]);

        $request->refresh();

        $this->assertNotSame($firstStoredPath, $request->resignation_letter_url);
        Storage::disk('local')->assertMissing('resignation-letters/tmp-letter-b.pdf');
        Storage::disk('local')->assertMissing($firstStoredPath);
        Storage::disk('local')->assertExists((string) $request->resignation_letter_url);

        $secondStoredPath = (string) $request->resignation_letter_url;

        $request->update([
            'resignation_letter_url' => null,
        ]);

        $request->refresh();

        $this->assertNull($request->resignation_letter_url);
        Storage::disk('local')->assertMissing($secondStoredPath);
    }

    public function test_soft_delete_keeps_resignation_letter_until_force_delete(): void
    {
        Storage::fake('local');

        config()->set('filesystems.default', 'local');
        config()->set('filament.default_filesystem_disk', 'local');

        Storage::disk('local')->put('resignation-letters/tmp-letter.pdf', 'letter');

        $request = Request::query()->create([
            'name'                   => 'John Doe',
            'email'                  => 'john@example.com',
            'resignation_letter_url' => 'resignation-letters/tmp-letter.pdf',
        ])->fresh();

        $storedPath = (string) $request->resignation_letter_url;

        $request->delete();

        Storage::disk('local')->assertExists($storedPath);

        $request->forceDelete();

        Storage::disk('local')->assertMissing($storedPath);
    }
}
