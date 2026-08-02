<style>
    /* Background halaman auth - gradient tipis, sama di semua auth page */
    .fi-simple-layout {
        background: radial-gradient(circle at top, rgba(6, 182, 212, 0.08), transparent 60%);
    }

    html.dark .fi-simple-layout {
        background: radial-gradient(circle at top, rgba(6, 182, 212, 0.12), transparent 60%);
    }

    /* Card utama - overlay putih transparan utk dark (teknik yg sama
       dipakai di Transaksi Cepat, tidak bergantung variabel tema gray). */
    .fi-simple-main {
        border-radius: 20px !important;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08), 0 12px 32px rgba(0, 0, 0, 0.06) !important;
    }

    html.dark .fi-simple-main {
        background: rgba(255, 255, 255, 0.055) !important;
        border: 1px solid rgba(255, 255, 255, 0.15) !important;
        box-shadow:
            inset 0 1px 0 rgba(255, 255, 255, 0.04),
            0 20px 50px -12px rgba(0, 0, 0, 0.7) !important;
        backdrop-filter: blur(6px);
    }

    /* Input OTP - dibuat lebih hidup: besar, berjarak, mudah dibaca */
    .auth-otp-input {
        text-align: center !important;
        letter-spacing: 0.6em !important;
        font-variant-numeric: tabular-nums;
        font-size: 1.375rem !important;
        font-weight: 600;
    }

    /* Logo brand di halaman auth - pastikan center & beri jarak bawah */
    .fi-simple-layout .fi-logo,
    .fi-simple-layout .fi-brand {
        display: flex !important;
        justify-content: center !important;
        align-items: center !important;
        margin: 0 auto 1rem !important;
    }

    .fi-simple-layout .fi-logo img,
    .fi-simple-layout .fi-brand img {
        height: 2.5rem;
        width: auto;
    }
</style>
