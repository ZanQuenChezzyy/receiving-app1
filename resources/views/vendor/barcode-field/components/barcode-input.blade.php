<!-- ZXing & custom scanner script -->
<script src="https://unpkg.com/@zxing/library@latest"></script>
<script src="{{ asset('vendor/barcode-field/barcode-scanner.js') }}"></script>

<div xmlns:x-filament="http://www.w3.org/1999/html">
    <div class="grid gap-y-2">
        <div class="flex items-center gap-x-3 justify-between">
            <label for="{{ $getId() }}" class="fi-fo-field-wrp-label inline-flex items-center gap-x-3">
                <span class="text-sm font-medium leading-6 text-gray-950 dark:text-white">
                    {{ $getLabel() ?? 'Input Label' }}
                    @if ($isRequired())
                        <sup class="text-danger-600 dark:text-danger-400 font-medium">*</sup>
                    @endif
                </span>
            </label>
        </div>

        {{-- Baris input + tombol di kanan --}}
        <div class="flex items-center gap-2">
            <x-filament::input.wrapper class="flex-1">
                <x-filament::input type="{{ $getType() }}" id="{{ $getId() }}" name="{{ $getName() }}"
                    dusk="filament.forms.{{ $getStatePath() }}" wire:model.live="{{ $getStatePath() }}"
                    placeholder="{{ $getPlaceholder() }}" :disabled="$isDisabled()" :autofocus="$isAutofocused()"
                    class="w-full" />
            </x-filament::input.wrapper>

            {{-- Tombol buka scanner di kanan field --}}
            <button type="button" onclick="openScannerModal('{{ $getId() }}', '{{ $getStatePath() }}')"
                aria-label="Scan Barcode" class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 bg-gray-100
                       hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 dark:border-gray-600
                       focus:outline-none focus:ring-2 focus:ring-primary-500">
                @if ($getExtraAttributes()['icon'] ?? null)
                    <x-dynamic-component :component="$getExtraAttributes()['icon']"
                        class="w-5 h-5 text-gray-600 dark:text-gray-200" />
                @else
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-gray-600 dark:text-gray-200"
                        viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                        <path d="M3 4h2v16H3V4zm4 0h2v16H7V4zm4 0h2v16h-2V4zm4 0h2v16h-2V4zm4 0h2v16h-2V4z" />
                    </svg>
                @endif
            </button>
        </div>
    </div>

    <!-- Modal Filament -->
    <x-filament::modal id="barcode-scanner-modal">
        <x-slot name="header">
            <h2 class="text-lg font-semibold">Scan Barcode</h2>
        </x-slot>

        <div class="p-4">
            <div id="scanner-container" class="relative w-full max-w-2xl mx-auto">
                <video id="scanner" autoplay playsinline class="hidden w-full h-auto rounded-lg shadow"></video>

                {{-- Overlay area sederhana (area target scan) --}}
                <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
                    <div class="w-3/5 h-2/5 rounded-lg border-2 border-white/80"></div>
                </div>
            </div>
        </div>

        <x-slot name="footer">
            <x-filament::button onclick="closeScannerModal()" color="danger">Close</x-filament::button>
        </x-slot>
    </x-filament::modal>
</div>

<script>
    (() => {
        let targetInputId = null;
        let targetStatePath = null;

        window.openScannerModal = (inputId, statePath) => {
            targetInputId = inputId;
            targetStatePath = statePath;

            // Buka modal Filament
            window.dispatchEvent(new CustomEvent('open-modal', { detail: { id: 'barcode-scanner-modal' } }));

            // Mulai scanner & pasang callback saat barcode terbaca
            window.startBarcodeScanner('scanner', (code) => {
                // Set ke input
                const el = document.getElementById(targetInputId);
                if (el) {
                    el.value = code;
                    el.dispatchEvent(new Event('input', { bubbles: true }));
                    el.dispatchEvent(new Event('change', { bubbles: true }));
                }

                // Sinkron ke Livewire
                if (window.$wire && typeof window.$wire.set === 'function') {
                    window.$wire.set(targetStatePath, code);
                }

                // Tutup modal & pastikan kamera berhenti
                window.dispatchEvent(new CustomEvent('close-modal', { detail: { id: 'barcode-scanner-modal' } }));
                window.stopBarcodeScanner();
            });
        };

        window.closeScannerModal = () => {
            window.dispatchEvent(new CustomEvent('close-modal', { detail: { id: 'barcode-scanner-modal' } }));
            window.stopBarcodeScanner();
        };
    })();
</script>