<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Identitas sekolah (dipakai pada kop laporan PDF & Excel)
    |--------------------------------------------------------------------------
    */
    'school' => [
        'office' => env('SEKOLAH_DINAS', 'Dinas Pendidikan dan Kebudayaan Kota Nusantara'),
        'name' => env('SEKOLAH_NAMA', 'SD Negeri 1 Nusantara'),
        'npsn' => env('SEKOLAH_NPSN', '20123456'),
        'address' => env('SEKOLAH_ALAMAT', 'Jl. Pendidikan No. 1, Kecamatan Nusantara, Kota Nusantara 40123'),
        'phone' => env('SEKOLAH_TELEPON', '(022) 123456'),
        'email' => env('SEKOLAH_EMAIL', 'info@sdn1nusantara.sch.id'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Pejabat penanda tangan pada lembar laporan & surat bebas pustaka
    |--------------------------------------------------------------------------
    */
    'headmaster' => [
        'name' => env('SEKOLAH_KEPSEK', 'Ahmad Sutrisno, S.Pd., M.M.'),
        'nip' => env('SEKOLAH_KEPSEK_NIP', '19700101 199001 1 001'),
    ],

    'librarian' => [
        'name' => env('SEKOLAH_PUSTAKAWAN', 'Sri Wahyuni, S.Pd.'),
        'nip' => env('SEKOLAH_PUSTAKAWAN_NIP', '19850202 200801 2 002'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Identitas perpustakaan & tahun ajaran aktif
    |--------------------------------------------------------------------------
    */
    'library_name' => env('PERPUS_NAMA_PERPUSTAKAAN', 'Perpustakaan Sekolah'),

    'current_academic_year' => env('PERPUS_TAHUN_AJARAN', '2025/2026'),

    /*
    |--------------------------------------------------------------------------
    | Awalan nomor slip cetak (surat peminjaman / pengembalian / denda)
    |--------------------------------------------------------------------------
    | Nomor slip dibuat berurutan per hari, contoh: SP/2026/03/0007.
    */
    'slip' => [
        'loan_prefix' => env('PERPUS_SLIP_PINJAM', 'SP'),
        'return_prefix' => env('PERPUS_SLIP_KEMBALI', 'SK'),
        'fine_prefix' => env('PERPUS_SLIP_DENDA', 'SD'),
        // Kuitansi denda: KT = bukti utang, KP = bukti pelunasan.
        'bill_receipt_prefix' => env('PERPUS_SLIP_TAGIHAN', 'KT'),
        'paid_receipt_prefix' => env('PERPUS_SLIP_LUNAS', 'KP'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Aturan peminjaman
    |--------------------------------------------------------------------------
    */
    'loan_days' => (int) env('PERPUS_LOAN_DAYS', 7),
    'max_loans_per_student' => (int) env('PERPUS_MAX_LOANS', 2),

    /*
    |--------------------------------------------------------------------------
    | Denda keterlambatan — dihitung per HARI SEKOLAH
    |--------------------------------------------------------------------------
    | Hanya hari Senin–Jumat yang dihitung. Sabtu/Minggu dan hari libur
    | nasional TIDAK menambah denda, sehingga buku yang jatuh tempo Jumat
    | lalu dikembalikan Senin tidak incurs denda sama sekali.
    |
     | Tarif sengaja kecil karena tujuan utama denda ini adalah mendidik,
     | bukan menambah pendapatan: uang saku siswa SD masih terbatas.
    |
    | PENTING: daftar hari libur WAJIB ditambah setiap tahun karena tanggal
    | hijriah (Idul Fitri, Nyepi, Maulid, dst) bergeser 1–2 hari.
    | Sumber: SKB 3 Menteri tentang Hari Libur Nasional & Cuti Bersama.
    */
    'fine' => [
        'daily_rate' => (int) env('PERPUS_DENDA_PER_HARI', 500),
        'max_per_book' => (int) env('PERPUS_DENDA_MAKS', 10000),
        'skip_weekends' => (bool) env('PERPUS_DENDA_SKIP_AKHIR_PEKAN', true),
        'block_borrowing' => (bool) env('PERPUS_DENDA_BLOKIR', true),

        // Cuti bersama: kantor pemerintah tutup. Sekolah sering masih
        // mengajar pada hari itu, jadi bisa dimatikan lewat .env.
        'count_joint_leave' => (bool) env('PERPUS_HITUNG_CUTI_BERSAMA', true),

        /*
         * Hari libur nasional per tahun, format YYYY-MM-DD.
         * 2025 - SKB 3 Menteri No. 1017/2024
         * 2026 - SKB 3 Menteri No. 1497/2025
         */
        'holidays' => [
            2025 => [
                '2025-01-01', // Tahun Baru Masehi
                '2025-01-27', // Isra Mikraj
                '2025-01-29', // Tahun Baru Imlek
                '2025-03-29', // Hari Suci Nyepi
                '2025-03-31', // Idul Fitri
                '2025-04-01', // Idul Fitri
                '2025-04-18', // Wafat Yesus Kristus
                '2025-04-20', // Kebangkitan Yesus Kristus (Paskah)
                '2025-05-01', // Hari Buruh Internasional
                '2025-05-12', // Hari Raya Waisak
                '2025-05-29', // Kenaikan Yesus Kristus
                '2025-06-01', // Hari Lahir Pancasila
                '2025-06-06', // Idul Adha
                '2025-06-27', // Tahun Baru Islam (1 Muharam)
                '2025-08-17', // Proklamasi Kemerdekaan RI
                '2025-09-05', // Maulid Nabi Muhammad
                '2025-12-25', // Kelahiran Yesus Kristus
            ],

            2026 => [
                '2026-01-01', // Tahun Baru Masehi
                '2026-01-16', // Isra Mikraj
                '2026-02-17', // Tahun Baru Imlek
                '2026-03-19', // Hari Suci Nyepi
                '2026-03-21', // Idul Fitri
                '2026-03-22', // Idul Fitri
                '2026-04-03', // Wafat Yesus Kristus
                '2026-04-05', // Kebangkitan Yesus Kristus (Paskah)
                '2026-05-01', // Hari Buruh Internasional
                '2026-05-14', // Kenaikan Yesus Kristus
                '2026-05-27', // Idul Adha
                '2026-05-31', // Hari Raya Waisak
                '2026-06-01', // Hari Lahir Pancasila
                '2026-06-16', // Tahun Baru Islam (1 Muharam)
                '2026-08-17', // Proklamasi Kemerdekaan RI
                '2026-08-25', // Maulid Nabi Muhammad
                '2026-12-25', // Kelahiran Yesus Kristus
            ],
        ],

        /*
         * Cuti bersama per tahun, format YYYY-MM-DD.
         * Diperlakukan sebagai hari sekolah tutup hanya bila
         * 'count_joint_leave' di atas bernilai true.
         */
        'joint_leaves' => [
            2025 => [
                '2025-01-28', // Imlek
                '2025-03-28', // Nyepi
                '2025-04-02', // Idul Fitri
                '2025-04-03', // Idul Fitri
                '2025-04-04', // Idul Fitri
                '2025-04-07', // Idul Fitri
                '2025-05-13', // Waisak
                '2025-05-30', // Kenaikan Yesus Kristus
                '2025-06-09', // Idul Adha
                '2025-12-26', // Natal
            ],

            2026 => [
                '2026-02-16', // Imlek
                '2026-03-18', // Nyepi
                '2026-03-20', // Idul Fitri
                '2026-03-23', // Idul Fitri
                '2026-03-24', // Idul Fitri
                '2026-05-15', // Kenaikan Yesus Kristus
                '2026-05-28', // Idul Adha
                '2026-12-24', // Natal
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pembatasan percobaan login (rate limiting)
    |--------------------------------------------------------------------------
    | Dua lapis, sengaja dipisah:
    |
    |   login.max_attempts  — kunci per email+IP. Menghentikan tebak-tebakan
    |                         password untuk satu akun tertentu.
    |   login.max_attempts_per_ip — kunci per IP saja. Tanpa ini, penyerang
    |                         bisa mengganti email tiap percobaan dan
    |                         otomatis lolos dari batas per-akun.
    |
    | Nilai di sini adalah jaring pengaman kedua; throttle di level route
    | (lihat routes/auth.php) yang menghentikan permintaan bahkan sebelum
    | password sempat dibandingkan.
    */
    'login' => [
        'max_attempts' => (int) env('PERPUS_LOGIN_MAX_ATTEMPTS', 5),
        'decay_seconds' => (int) env('PERPUS_LOGIN_LOCKOUT_DETIK', 60),
        'max_attempts_per_ip' => (int) env('PERPUS_LOGIN_MAX_PER_IP', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cadangan otomatis (backup) database SQLite
    |--------------------------------------------------------------------------
    | Dipakai oleh perintah `php artisan perpustakaan:backup`.
    */
    'backup' => [
        'path' => env('PERPUS_BACKUP_PATH', storage_path('app/backups')),
        'keep' => (int) env('PERPUS_BACKUP_KEEP', 14),
        'schedule' => env('PERPUS_BACKUP_JADWAL', 'daily'),
        'time' => env('PERPUS_BACKUP_WAKTU', '23:00'),
    ],

];
