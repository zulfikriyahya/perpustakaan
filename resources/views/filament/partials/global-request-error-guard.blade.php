{{--
    Guard global untuk request Livewire yang gagal total (network/session
    terputus) di SEMUA halaman panel - lihat gap "Uncaught (in promise)
    Object {status: null, body: null, json: null, errors: null}".

    Shape object ini SPESIFIK untuk kegagalan fetch Livewire yang tidak
    sempat dapat response sama sekali (bukan validation error 422/500
    biasa, yang punya status terisi). Dibedakan dari AbortError (request
    dibatalkan karena debounce/request baru menyusul) yang bentuknya
    DOMException, BUKAN object literal seperti ini - jadi guard ini tidak
    akan salah menangkap pembatalan request yang normal/harmless.

    TODO: verifikasi signature terhadap versi package yang terpasang -
    shape error ini diasumsikan konsisten di livewire/livewire v4.3.5
    (lihat composer.lock). Jika Livewire di-upgrade dan bentuk error
    request-gagal berubah, fungsi terlihatSepertiRequestGagalLivewire()
    di bawah perlu disesuaikan.
--}}
<script>
    (function () {
        const RELOAD_COOLDOWN_MS = 10000;
        const KUNCI_COOLDOWN = 'perpus_last_auto_reload';

        function terlihatSepertiRequestGagalLivewire(reason) {
            if (!reason || typeof reason !== 'object') return false;
            const kunciWajib = ['status', 'body', 'json', 'errors'];
            return kunciWajib.every((k) => k in reason) && reason.status == null;
        }

        window.addEventListener('unhandledrejection', function (event) {
            if (!terlihatSepertiRequestGagalLivewire(event.reason)) {
                return;
            }

            // Redam - jangan biarkan browser mencatat sebagai uncaught
            // error yang bikin console berisik / operator panik.
            event.preventDefault();

            const sekarang = Date.now();
            const terakhir = Number(sessionStorage.getItem(KUNCI_COOLDOWN) || 0);

            if (sekarang - terakhir < RELOAD_COOLDOWN_MS) {
                // Sudah auto-reload baru-baru ini - kemungkinan server
                // memang sedang down. Hindari reload loop, biarkan diam
                // untuk kejadian kali ini.
                return;
            }

            sessionStorage.setItem(KUNCI_COOLDOWN, String(sekarang));
            window.location.reload();
        });
    })();
</script>
