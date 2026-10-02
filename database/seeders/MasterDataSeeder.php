<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Category;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MasterDataSeeder extends Seeder
{
    public const ACADEMIC_YEAR = '2025/2026';

    /**
     * Guru, kelas, siswa, kategori, dan koleksi buku.
     */
    public function run(): void
    {
        $this->seedUsers();
        $classrooms = $this->seedClassrooms();
        $this->seedStudents($classrooms);
        $categories = $this->seedCategories();
        $this->seedPackageBooks($categories);
        $this->seedCollectionBooks($categories);
    }

    private function seedUsers(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@perpus-sd.test'],
            [
                'name' => 'Ibu Sri Wahyuni (Pustakawan)',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'kepsek@perpus-sd.test'],
            [
                'name' => 'Bapak Ahmad Sutrisno (Kepala Sekolah)',
                'password' => Hash::make('password'),
                'role' => 'kepsek',
            ]
        );
    }

    /**
     * @return \Illuminate\Support\Collection<int, Classroom>
     */
    private function seedClassrooms()
    {
        $teachers = [
            'Ibu Ratna Kusuma, S.Pd.',
            'Ibu Dewi Lestari, S.Pd.',
            'Bapak Hendra Gunawan, S.Pd.',
            'Ibu Yuni Astuti, S.Pd.',
            'Bapak Slamet Riyadi, S.Pd.',
            'Ibu Nur Hidayah, S.Pd.',
        ];

        $classrooms = collect();

        foreach (range(1, 6) as $grade) {
            $classrooms->push(Classroom::create([
                'name' => 'Kelas '.$grade,
                'grade_level' => $grade,
                'academic_year' => self::ACADEMIC_YEAR,
                'homeroom_teacher' => $teachers[$grade - 1],
            ]));
        }

        return $classrooms;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Classroom>  $classrooms
     */
    private function seedStudents($classrooms): void
    {
        $nisn = 1234567890;
        $male = ['Adi', 'Bagas', 'Cahyo', 'Dimas', 'Eko', 'Fajar', 'Galih', 'Hendra', 'Irfan', 'Joko', 'Kresna', 'Lufi', 'Mega', 'Nanda', 'Oscar', 'Putra'];
        $female = ['Ayu', 'Bunga', 'Citra', 'Dewi', 'Eka', 'Fitri', 'Gita', 'Hana', 'Indah', 'Jihan', 'Kartika', 'Lina', 'Maya', 'Nabila', 'Olivia', 'Putri'];
        $surnames = ['Pratama', 'Saputra', 'Wijaya', 'Nugroho', 'Ramadhan', 'Lestari', 'Anggraini', 'Maulida', 'Safitri', 'Purnama', 'Hidayat', 'Kusuma', 'Rahmawati', 'Santoso', 'Wulandari', 'Firmansyah'];

        $index = 0;

        foreach ($classrooms as $classroom) {
            foreach (range(1, 24) as $number) {
                $gender = $number % 2 === 0 ? 'P' : 'L';
                $first = $gender === 'L' ? $male[$index % 10] : $female[$index % 10];
                $last = $surnames[($index * 3) % 10];

                Student::create([
                    'classroom_id' => $classroom->id,
                    'nisn' => (string) $nisn,
                    'name' => "{$first} {$last}",
                    'gender' => $gender,
                    'is_active' => true,
                ]);

                $nisn++;
                $index++;
            }
        }
    }

    /**
     * @return \Illuminate\Support\Collection<string, Category>
     */
    private function seedCategories()
    {
        $data = [
            ['code' => 'PB', 'name' => 'Buku Paket Kurikulum Merdeka', 'color' => 'emerald'],
            ['code' => 'FIK', 'name' => 'Fiksi & Cerita Anak', 'color' => 'amber'],
            ['code' => 'REF', 'name' => 'Referensi & Ensiklopedia', 'color' => 'sky'],
            ['code' => '200', 'name' => 'Agama & Kepercayaan', 'color' => 'teal'],
            ['code' => '500', 'name' => 'Sains & Matematika', 'color' => 'violet'],
            ['code' => '900', 'name' => 'Sejarah, Geografi & Budaya', 'color' => 'rose'],
        ];

        $categories = collect();

        foreach ($data as $item) {
            $categories->put($item['code'], Category::create($item));
        }

        return $categories;
    }

    /**
     * @param  \Illuminate\Support\Collection<string, Category>  $categories
     */
    private function seedPackageBooks($categories): void
    {
        $subjects = [
            ['mapel' => 'Pendidikan Agama dan Budi Pekerti', 'grades' => [1, 2, 3, 4, 5, 6]],
            ['mapel' => 'Pendidikan Pancasila', 'grades' => [1, 2, 3, 4, 5, 6]],
            ['mapel' => 'Bahasa Indonesia', 'grades' => [1, 2, 3, 4, 5, 6]],
            ['mapel' => 'Matematika', 'grades' => [1, 2, 3, 4, 5, 6]],
            ['mapel' => 'Ilmu Pengetahuan Alam dan Sosial (IPAS)', 'grades' => [3, 4, 5, 6]],
            ['mapel' => 'Bahasa Inggris', 'grades' => [1, 2, 3, 4, 5, 6]],
            ['mapel' => 'PJOK', 'grades' => [1, 2, 3, 4, 5, 6]],
            ['mapel' => 'Seni Budaya dan Prakarya', 'grades' => [1, 2, 3, 4, 5, 6]],
        ];

        $number = 1;
        $codes = app(\App\Services\BookCodeService::class);
        $sequence = 0;

        foreach ($subjects as $subject) {
            foreach ($subject['grades'] as $grade) {
                $sequence++;

                Book::create([
                    'code' => 'BK-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
                    // ISBN data contoh sengaja dibuat dari checksum yang valid
                    // supaya form ISBN bisa dicoba tanpa langsung ditolak.
                    'isbn' => $codes->generateIsbn($sequence),
                    'title' => Str::limit("Buku Siswa {$subject['mapel']} Kelas {$grade} SD", 120, ''),
                    'author' => 'Tim Penulis Kemendikbudristek',
                    'publisher' => 'Pusat Perbukuan, Kemendikbudristek',
                    'published_year' => 2022,
                    'category_id' => $categories->get('PB')->id,
                    'book_type' => 'paket',
                    'grade_level' => $grade,
                    'shelf_location' => 'Lemari Paket '.$grade,
                    'funding_source' => 'BOS Reguler',
                    'total_copies' => 20,
                    'available_copies' => 20,
                ]);

                $number++;
            }
        }
    }

    /**
     * @param  \Illuminate\Support\Collection<string, Category>  $categories
     */
    private function seedCollectionBooks($categories): void
    {
        $collections = [
            ['title' => 'Si Kancil dan Buaya', 'author' => 'Tim Cerita Rakyat Nusantara', 'category' => 'FIK', 'shelf' => 'Rak Cerita A-1'],
            ['title' => 'Timun Mas dan Raksasa Hijau', 'author' => 'Tim Cerita Rakyat Nusantara', 'category' => 'FIK', 'shelf' => 'Rak Cerita A-1'],
            ['title' => 'Malin Kundang Anak Durhaka', 'author' => 'Tim Cerita Rakyat Nusantara', 'category' => 'FIK', 'shelf' => 'Rak Cerita A-2'],
            ['title' => 'Lutung Kasarung', 'author' => 'Tim Cerita Rakyat Nusantara', 'category' => 'FIK', 'shelf' => 'Rak Cerita A-2'],
            ['title' => 'Roro Jonggrang dan Candi Prambanan', 'author' => 'Tim Cerita Rakyat Nusantara', 'category' => 'FIK', 'shelf' => 'Rak Cerita A-3'],
            ['title' => 'Sangkuriang dan Tangkuban Perahu', 'author' => 'Tim Cerita Rakyat Nusantara', 'category' => 'FIK', 'shelf' => 'Rak Cerita A-3'],
            ['title' => 'Petualangan Kiki Si Kelinci Pemberani', 'author' => 'Ratih Kumala', 'category' => 'FIK', 'shelf' => 'Rak Cerita B-1'],
            ['title' => 'Komik Anak Sholeh: Belajar Jujur', 'author' => 'Zaky Al-Farizi', 'category' => 'FIK', 'shelf' => 'Rak Cerita B-1'],
            ['title' => 'Hafalan Doa Harian Anak Muslim', 'author' => 'Ustazah Aisyah', 'category' => '200', 'shelf' => 'Rak Agama C-1'],
            ['title' => 'Kisah 25 Nabi untuk Anak', 'author' => 'Muhammad Ridho', 'category' => '200', 'shelf' => 'Rak Agama C-1'],
            ['title' => 'Mengenal Angka dan Berhitung Cepat', 'author' => 'Diana Wijaya', 'category' => '500', 'shelf' => 'Rak Sains D-1'],
            ['title' => 'Sains Seru: Percobaan Sederhana di Rumah', 'author' => 'Budi Santoso', 'category' => '500', 'shelf' => 'Rak Sains D-1'],
            ['title' => 'Ensiklopedia Anak: Tubuh Manusia', 'author' => 'Tim Redaksi Cerdas', 'category' => 'REF', 'shelf' => 'Rak Referensi D-2'],
            ['title' => 'Ensiklopedia Anak: Tata Surya', 'author' => 'Tim Redaksi Cerdas', 'category' => 'REF', 'shelf' => 'Rak Referensi D-2'],
            ['title' => 'Kamus Bergambar Bahasa Inggris untuk SD', 'author' => 'Tim Bahasa Cerdas', 'category' => 'REF', 'shelf' => 'Rak Referensi D-3'],
            ['title' => 'Mengenal Rumah Adat Nusantara', 'author' => 'Siti Nurhaliza', 'category' => '900', 'shelf' => 'Rak Budaya E-1'],
            ['title' => 'Sejarah Kemerdekaan Indonesia untuk Anak', 'author' => 'Hendra Wijaya', 'category' => '900', 'shelf' => 'Rak Budaya E-1'],
            ['title' => 'Peta dan Provinsi di Indonesia', 'author' => 'Ani Yudhoyono', 'category' => '900', 'shelf' => 'Rak Budaya E-2'],
        ];

        $number = 100;

        foreach ($collections as $item) {
            $copies = random_int(3, 8);

            Book::create([
                'code' => 'BK-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT),
                'title' => $item['title'],
                'author' => $item['author'],
                'publisher' => 'Penerbit Cerdas Nusantara',
                'published_year' => random_int(2015, 2023),
                'category_id' => $categories->get($item['category'])->id,
                'book_type' => 'koleksi',
                'grade_level' => null,
                'shelf_location' => $item['shelf'],
                'funding_source' => 'Hibah / Pengadaan Mandiri',
                'total_copies' => $copies,
                'available_copies' => $copies,
            ]);

            $number++;
        }
    }
}
