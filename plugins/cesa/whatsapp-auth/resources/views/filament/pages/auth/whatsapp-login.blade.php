<x-filament-panels::page.simple>
    <form
        id="form"
        wire:submit="submit"
        class="grid fi-form gap-y-6"
    >
        <div class="flex flex-col gap-8">
            {{ $this->form }}

            <x-filament::actions
                :actions="$this->getCachedFormActions()"
                :full-width="$this->hasFullWidthFormActions()"
            />
        </div>
    </form>

    <div class="mt-4 text-center">
        {{ $this->loginAction }}
    </div>
</x-filament-panels::page.simple>
