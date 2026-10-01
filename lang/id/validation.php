<?php

/*
|--------------------------------------------------------------------------
| Pesan validasi (Bahasa Indonesia)
|--------------------------------------------------------------------------
| Hanya aturan yang benar-benar dipakai aplikasi ini yang diterjemahkan.
| Aturan lain otomatis jatuh ke fallback_locale (English), jadi tidak akan
| pernah ada teks kosong atau ":attribute" literal yang tampil ke pengguna.
*/

return [

    'accepted' => ':attribute harus disetujui.',
    'active_url' => ':attribute bukan URL yang valid.',
    'after' => ':attribute harus berisi tanggal setelah :date.',
    'after_or_equal' => ':attribute harus berisi tanggal setelah atau sama dengan :date.',
    'alpha' => ':attribute hanya boleh berisi huruf.',
    'alpha_dash' => ':attribute hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
    'alpha_num' => ':attribute hanya boleh berisi huruf dan angka.',
    'array' => ':attribute harus berupa daftar.',
    'before' => ':attribute harus berisi tanggal sebelum :date.',
    'before_or_equal' => ':attribute harus berisi tanggal sebelum atau sama dengan :date.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'current_password' => 'Kata sandi yang dimasukkan salah.',
    'date' => ':attribute bukan tanggal yang valid.',
    'date_format' => ':attribute tidak cocok dengan format :format.',
    'declined' => ':attribute harus ditolak.',
    'different' => ':attribute dan :other harus berbeda.',
    'digits' => ':attribute harus terdiri dari :digits angka.',
    'digits_between' => ':attribute harus terdiri dari :min sampai :max angka.',
    'email' => ':attribute harus berupa alamat email yang valid.',
    'ends_with' => ':attribute harus diakhiri salah satu dari: :values.',
    'exists' => ':attribute yang dipilih tidak valid.',
    'file' => ':attribute harus berupa berkas.',
    'filled' => 'Kolom :attribute wajib diisi.',
    'image' => ':attribute harus berupa gambar.',
    'in' => ':attribute yang dipilih tidak valid.',
    'integer' => ':attribute harus berupa bilangan bulat.',
    'ip' => ':attribute harus berupa alamat IP yang valid.',
    'json' => ':attribute harus berupa teks JSON yang valid.',
    'mimes' => ':attribute harus berupa berkas berjenis: :values.',
    'mimetypes' => ':attribute harus berupa berkas berjenis: :values.',
    'not_in' => ':attribute yang dipilih tidak valid.',
    'numeric' => ':attribute harus berupa angka.',
    'present' => 'Kolom :attribute wajib ada.',
    'prohibited' => 'Kolom :attribute dilarang diisi.',
    'regex' => 'Format :attribute tidak valid.',
    'required' => 'Kolom :attribute wajib diisi.',
    'required_if' => 'Kolom :attribute wajib diisi bila :other bernilai :value.',
    'required_unless' => 'Kolom :attribute wajib diisi kecuali :other bernilai :value.',
    'required_with' => 'Kolom :attribute wajib diisi bila :values ada.',
    'required_with_all' => 'Kolom :attribute wajib diisi bila :values ada.',
    'required_without' => 'Kolom :attribute wajib diisi bila :values tidak ada.',
    'required_without_all' => 'Kolom :attribute wajib diisi bila tidak ada :values.',
    'same' => ':attribute dan :other harus sama.',
    'starts_with' => ':attribute harus diawali salah satu dari: :values.',
    'string' => ':attribute harus berupa teks.',
    'timezone' => ':attribute harus berupa zona waktu yang valid.',
    'unique' => ':attribute sudah digunakan.',
    'uploaded' => 'Gagal mengunggah :attribute.',
    'url' => 'Format :attribute tidak valid.',
    'uuid' => ':attribute harus berupa UUID yang valid.',

    'max' => [
        'array' => ':attribute tidak boleh lebih dari :max item.',
        'file' => ':attribute tidak boleh lebih dari :max KB.',
        'numeric' => ':attribute tidak boleh lebih dari :max.',
        'string' => ':attribute tidak boleh lebih dari :max karakter.',
    ],

    'min' => [
        'array' => ':attribute minimal :min item.',
        'file' => ':attribute minimal :min KB.',
        'numeric' => ':attribute minimal bernilai :min.',
        'string' => ':attribute minimal :min karakter.',
    ],

    'size' => [
        'array' => ':attribute harus berisi :size item.',
        'file' => ':attribute harus :size KB.',
        'numeric' => ':attribute harus bernilai :size.',
        'string' => ':attribute harus :size karakter.',
    ],

    'gt' => [
        'numeric' => ':attribute harus lebih besar dari :value.',
        'string' => ':attribute harus lebih dari :value karakter.',
        'array' => ':attribute harus lebih dari :value item.',
        'file' => ':attribute harus lebih dari :value KB.',
    ],

    'gte' => [
        'numeric' => ':attribute harus :value atau lebih besar.',
        'string' => ':attribute harus :value karakter atau lebih.',
        'array' => ':attribute harus memiliki :value item atau lebih.',
        'file' => ':attribute harus :value KB atau lebih.',
    ],

    'lt' => [
        'numeric' => ':attribute harus kurang dari :value.',
        'string' => ':attribute harus kurang dari :value karakter.',
        'array' => ':attribute harus kurang dari :value item.',
        'file' => ':attribute harus kurang dari :value KB.',
    ],

    'lte' => [
        'numeric' => ':attribute tidak boleh lebih dari :value.',
        'string' => ':attribute tidak boleh lebih dari :value karakter.',
        'array' => ':attribute tidak boleh lebih dari :value item.',
        'file' => ':attribute tidak boleh lebih dari :value KB.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Pesan khusus per atribut
    |--------------------------------------------------------------------------
    */

    'custom' => [
        'nisn' => [
            'unique' => 'NISN ini sudah dipakai siswa lain.',
            'digits' => 'NISN harus terdiri dari 10 angka.',
        ],
        'code' => [
            'unique' => 'Kode ini sudah dipakai.',
        ],
    ],

/*
    |--------------------------------------------------------------------------
    | Nama atribut
    |--------------------------------------------------------------------------
    | Tanpa daftar ini, pesan seperti ":attribute wajib diisi" akan tampil
    | literal dengan nama field mentah, karena Laravel memakai nama field
    | apa adanya sebagai cadangan.
    */

    'attributes' => [
        'name' => 'nama',
        'email' => 'email',
        'password' => 'kata sandi',
        'password_confirmation' => 'konfirmasi kata sandi',
        'current_password' => 'kata sandi saat ini',
        'code' => 'kode',
        'title' => 'judul',
        'author' => 'penulis',
        'publisher' => 'penerbit',
        'year' => 'tahun terbit',
        'stock' => 'jumlah',
        'nisn' => 'NISN',
        'nis' => 'NIS',
        'gender' => 'jenis kelamin',
        'classroom_id' => 'kelas',
        'grade_level' => 'tingkat',
        'academic_year' => 'tahun ajaran',
        'phone' => 'nomor telepon',
        'address' => 'alamat',
        'notes' => 'catatan',
        'purpose' => 'keperluan',
        'book_id' => 'buku',
        'student_id' => 'siswa',
        'due_at' => 'tanggal jatuh tempo',
        'borrowed_at' => 'tanggal peminjaman',
        'returned_at' => 'tanggal pengembalian',
        'fine_amount' => 'nominal denda',
        'daily_rate' => 'tarif denda per hari',
        'max_per_book' => 'batas denda per buku',
        'file' => 'berkas',
        'from' => 'tanggal awal',
        'to' => 'tanggal akhir',
        'status' => 'status',
    ],

];