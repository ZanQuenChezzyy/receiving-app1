// public/vendor/barcode-field/barcode-scanner.js
(function () {
    const { BrowserMultiFormatReader, NotFoundException } = window.ZXing || {};
    let reader = null;
    let controls = null;
    let videoEl = null;
    let onDetectedCb = null;
    let isActive = false;

    function pickRearOrFirst(devices) {
        if (!devices || !devices.length) return null;
        const rear = devices.find(d => (d.label || '').toLowerCase().includes('back') || (d.label || '').toLowerCase().includes('rear'));
        return (rear || devices[0]).deviceId;
    }

    async function startBarcodeScanner(videoElementId, onDetected) {
        if (!window.ZXing || !BrowserMultiFormatReader) {
            console.error('ZXing library not found. Ensure @zxing/library is loaded.');
            return;
        }

        // Simpan referensi
        videoEl = document.getElementById(videoElementId);
        onDetectedCb = typeof onDetected === 'function' ? onDetected : null;

        if (!videoEl) {
            console.error('Video element not found:', videoElementId);
            return;
        }

        // Tampilkan video
        videoEl.classList.remove('hidden');
        videoEl.style.display = '';

        // Inisialisasi reader jika perlu
        if (!reader) reader = new BrowserMultiFormatReader();

        try {
            const devices = await reader.getVideoInputDevices();
            const deviceId = pickRearOrFirst(devices);

            // Mulai decode — Biarkan ZXing yang buka stream (JANGAN panggil getUserMedia manual)
            controls = reader.decodeFromVideoDevice(deviceId, videoEl, (result, err) => {
                if (result) {
                    try {
                        const text = result.getText ? result.getText() : (result.text || '');
                        onDetectedCb && onDetectedCb(text);
                    } catch (e) {
                        console.error('Error handling detected code:', e);
                    }
                } else if (err && !(err instanceof NotFoundException)) {
                    console.error('ZXing error:', err);
                }
            });

            isActive = true;

            // Simpan stream dari video untuk berjaga-jaga saat stop
            setTimeout(() => {
                try { /* no-op: videoEl.srcObject akan di-set oleh ZXing */ } catch (_) { }
            }, 100);

        } catch (e) {
            console.error('Failed to start scanner:', e);
        }
    }

    function stopBarcodeScanner() {
        // Stop control ZXing
        try { controls && typeof controls.stop === 'function' && controls.stop(); } catch (_) { }

        // Reset reader ZXing
        try { reader && typeof reader.reset === 'function' && reader.reset(); } catch (_) { }

        // Hentikan semua track dari stream video
        try {
            if (videoEl && videoEl.srcObject && typeof videoEl.srcObject.getTracks === 'function') {
                videoEl.srcObject.getTracks().forEach(t => t.stop());
            }
            if (videoEl) {
                try { videoEl.pause && videoEl.pause(); } catch (_) { }
                videoEl.srcObject = null;
                videoEl.classList.add('hidden');
                videoEl.style.display = 'none';
            }
        } catch (e) {
            console.warn('Failed to fully stop camera:', e);
        }

        // Bersihkan state
        controls = null;
        isActive = false;
    }

    // Helper ambil id modal dari event (Filament kirim { id: '...' })
    function getModalIdFromEvent(e) {
        if (!e || typeof e.detail === 'undefined') return undefined;
        if (typeof e.detail === 'string') return e.detail;
        return e.detail && e.detail.id ? e.detail.id : undefined;
    }

    // Listener global: buka/tutup modal
    window.addEventListener('open-modal', (e) => {
        const id = getModalIdFromEvent(e);
        if (id === 'barcode-scanner-modal') {
            // startBarcodeScanner akan dipanggil dari Blade agar tahu target input + callback
            // (lihat helper openScannerModal() di Blade)
        }
    });

    window.addEventListener('close-modal', (e) => {
        const id = getModalIdFromEvent(e);
        if (id === 'barcode-scanner-modal') {
            stopBarcodeScanner();
        }
    });

    // Tambahan safeguard: kalau user tekan ESC, biasanya modal tertutup.
    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') stopBarcodeScanner();
    });

    // Ekspor ke global
    window.startBarcodeScanner = startBarcodeScanner;
    window.stopBarcodeScanner = stopBarcodeScanner;
})();
