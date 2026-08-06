<style>
    .app-footer {
        display: block;
        width: 100%;
        margin: 1.5rem 0;
        padding: 0;
        text-align: center;
        font-size: 0.75rem;
        color: #6b7280;
    }

    html.dark .app-footer {
        color: #9ca3af;
    }

    .app-footer a {
        color: var(--primary-600);
        text-decoration: none;
        font-weight: 500;
    }

    html.dark .app-footer a {
        color: var(--primary-400);
    }

    .app-footer a:hover {
        text-decoration: underline;
    }

    .app-footer.app-footer--auth-top {
        margin: 1.5rem 0 0;
    }

    .fi-sc-component:has(> .app-footer-wrapper) {
        display: flex;
        width: 100%;
        justify-content: center;
    }

    /* BARU: footer khusus halaman Sirkulasi - fixed ke dasar viewport,
       TIDAK ikut alur normal dokumen, supaya selalu terlihat tanpa
       tergantung perhitungan tinggi konten (gap: sticky di bawah).
       Latar SOLID wajib diisi supaya konten yang di-scroll di baliknya
       (jika konten membesar) tidak tembus terlihat di belakang teks
       footer. TODO: verifikasi visual - warna berikut adalah
       pendekatan warna panel Filament light/dark, sesuaikan jika masih
       ada perbedaan sedikit dengan latar body asli. */
    .app-footer.app-footer--fixed-sirkulasi {
        position: fixed;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 30;
        margin: 0;
        padding: 0.75rem 0;
        background-color: #ffffff;
        border-top: 1px solid rgba(0, 0, 0, 0.08);
    }

    html.dark .app-footer.app-footer--fixed-sirkulasi {
        background-color: #0f0f13;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }
</style>
