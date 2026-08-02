<style>
    /* Style footer - didaftarkan SEKALI via renderHook(HEAD_END), dipakai
       bersama oleh markup footer di halaman auth (bawah frame) maupun
       Dashboard/halaman non-auth (bawah body) - lihat
       filament.partials.app-footer untuk markup-nya saja. */
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

    /* Footer di BAWAH frame form auth - beri jarak ATAS lebih besar
       supaya tidak menempel ke elemen sebelumnya (tombol aksi/form). */
    .app-footer.app-footer--auth-top {
        margin: 1.5rem 0 0;
    }

/* GAP-SPEC: fi-sc-component (parent dari fi-sc-text) tidak melebar
       penuh/tidak center secara default di dalam schema grid Login -
       berbeda dari halaman auth lain yang footernya disisipkan langsung
       sebagai HTML biasa (di luar sistem grid schema Filament). class
       app-footer-wrapper digabung LANGSUNG di elemen span.fi-sc-text yg
       sama (bukan child terpisah) via extraAttributes() pada komponen
       Text di Login::content() - selector :has() menyasar parent
       fi-sc-component yang punya child span dengan class tsb.
    */
    .fi-sc-component:has(> .app-footer-wrapper) {
        display: flex;
        width: 100%;
        justify-content: center;
    }
</style>
