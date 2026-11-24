// Satu sumber keperluan kunjungan.
//
// Portal siswa memakai daftar ini untuk pilihan yang bisa dikirim, dashboard
// memakai daftar yang sama untuk legenda dan warna badge. Dengan begitu tidak
// ada lagi entri legenda yang tidak pernah bisa dipilih siswa.
export const VISIT_PURPOSES = [
    {
        value: 'membaca',
        label: 'Membaca di Tempat',
        shortLabel: 'Membaca',
        description: 'Buku tetap di perpustakaan',
        tone: 'emerald',
    },
    {
        value: 'pinjam',
        label: 'Meminjam Buku',
        shortLabel: 'Meminjam',
        description: 'Bawa pulang ke rumah',
        tone: 'sky',
    },
];

export const PURPOSE_META = Object.fromEntries(
    VISIT_PURPOSES.map(({ value, shortLabel, tone }) => [value, { label: shortLabel, tone }]),
);
