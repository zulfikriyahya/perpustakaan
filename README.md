use App\Models\RewardLog;
use App\Models\User;
use App\Models\Reward;

// Ambil satu user & reward asli yang sudah ada di DB untuk relasi valid.
// Ganti query ini kalau butuh user/reward tertentu.
$user = User::query()->whereNotNull('nama')->first();
$reward = Reward::query()->first();

if (! $user || ! $reward) {
    echo "Butuh minimal 1 User dan 1 Reward di database untuk uji coba ini.\n";
} else {
    // Instance in-memory, TIDAK di-save() - jadi tidak menyentuh data asli.
    $log = new RewardLog();
    $log->id = (string) \Illuminate\Support\Str::uuid();
    $log->tanggal_didapat = now();
    $log->setRelation('user', $user);
    $log->setRelation('reward', $reward);

    // Opsional: paksa nama panjang untuk uji kasus terburuk (overflow risk)
    // $user->nama = 'Muhammad Alif Ramadhansyah Putra Wicaksono';
    // $reward->deskripsi = str_repeat('Deskripsi reward yang cukup panjang untuk menguji apakah layout tetap muat dalam satu halaman A4 portrait. ', 3);

    $path = app(\App\Services\SertifikatService::class)->generateUntukReward($log);

    echo $path ? "Berhasil: storage/app/public/{$path}\n" : "Gagal generate, cek log Laravel.\n";
}
