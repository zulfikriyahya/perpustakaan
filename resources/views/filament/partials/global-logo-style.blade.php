<style>
    /* Logo dark/light global - berlaku di SEMUA halaman panel (termasuk
       Dashboard), bukan cuma auth. Didaftarkan via renderHook biasa
       (bukan Vite/app.css - app.css TIDAK dimuat panel manapun di
       proyek ini, dikonfirmasi lewat curl - lihat riwayat sesi), supaya
       konsisten dengan pola chart-export-script yang sudah terbukti
       jalan. */
    .fi-logo-dark {
        display: none;
    }

    html.dark .fi-logo-light {
        display: none;
    }

    html.dark .fi-logo-dark {
        display: inline-block;
    }
</style>
