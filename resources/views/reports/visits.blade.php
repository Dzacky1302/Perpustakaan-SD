@extends('reports.layout')

@section('content')
    <table class="ringkasan">
        <tr>
            <td class="label">Jumlah Kunjungan</td>
            <td>{{ number_format($summary['total'], 0, ',', '.') }} kunjungan</td>
        </tr>
        <tr>
            <td class="label">Jumlah Buku Dibaca / Dibawa</td>
            <td>{{ number_format($summary['with_book'], 0, ',', '.') }} kunjungan</td>
        </tr>
        <tr>
            <td class="label">Rata-rata Kunjungan per Hari Sekolah</td>
            <td>{{ $summary['daily_average'] }} kunjungan</td>
        </tr>
        <tr>
            <td class="label">Siswa Aktif Berkunjung</td>
            <td>{{ number_format($summary['unique_students'], 0, ',', '.') }} siswa</td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 26px">No</th>
                <th>Hari, Tanggal</th>
                <th>Nama Siswa</th>
                <th style="width: 55px">Kelas</th>
                <th style="width: 55px">Jam</th>
                <th style="width: 90px">Keperluan</th>
                <th>Buku yang Dibaca</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($visits as $index => $visit)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $visit->visit_date->translatedFormat('l, d/m/Y') }}</td>
                    <td>{{ $visit->student?->name }}</td>
                    <td class="text-center">
                        {{ $visit->effectiveClassroom()?->name ?? '—' }}
                    </td>
                    <td class="text-center">{{ substr((string) $visit->arrival_time, 0, 5) }}</td>
                    <td class="text-center">{{ ucfirst($visit->purpose) }}</td>
                    <td>{{ $visit->book?->title ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">Tidak ada data kunjungan pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p class="catatan">
        Catatan: laporan ini dihasilkan otomatis dari buku tamu digital PustakaSD
        dan dapat digunakan sebagai lampiran laporan bulanan perpustakaan sekolah.
        Kolom kelas memakai kelas pada saat kunjungan dicatat; baris bertanda
        &mdash; berasal dari data lama sebelum kelas mulai dicatat otomatis.
    </p>
@endsection
