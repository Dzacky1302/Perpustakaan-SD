@extends('reports.layout')

@section('content')
    <table class="ringkasan">
        <tr>
            <td class="label">Nomor Surat</td>
            <td>421.7/{{ str_pad((string) $student->id, 3, '0', STR_PAD_LEFT) }}/PERPUS/{{ now()->format('Y') }}</td>
        </tr>
        <tr>
            <td class="label">Nama Siswa</td>
            <td>{{ $student->name }}</td>
        </tr>
        <tr>
            <td class="label">NISN</td>
            <td>{{ $student->nisn }}</td>
        </tr>
        <tr>
            <td class="label">Kelas</td>
            <td>{{ $student->classroom?->name }} &middot; Tahun Ajaran {{ $student->classroom?->academic_year }}</td>
        </tr>
        <tr>
            <td class="label">Status Tanggungan</td>
            <td>
                @if ($isClear)
                    <span class="badge badge-ok">Tidak ada tanggungan peminjaman</span>
                @else
                    <span class="badge badge-warn">{{ $activePackageLoans->count() + $activeDailyLoans->count() }} buku belum dikembalikan</span>
                @endif
            </td>
        </tr>
    </table>

    <p style="font-size: 11px; line-height: 1.6">
        Yang bertanda tangan di bawah ini Kepala {{ config('perpustakaan.school.name') }},
        berdasarkan pemeriksaan catatan peminjaman pada aplikasi PustakaSD, menerangkan bahwa siswa
        tersebut di atas
        @if ($isClear)
            <strong>BEBAS TANGGUNGAN</strong> peminjaman buku pada perpustakaan sekolah
            dan tidak memiliki kewajiban pengembalian buku paket maupun buku koleksi.
        @else
            <strong>MASIH MEMILIKI TANGGUNGAN</strong> berupa buku yang belum dikembalikan
            sebagaimana daftar di bawah ini, sehingga yang bersangkutan belum dapat dinyatakan bebas pustaka.
        @endif
    </p>

    @if (! $isClear)
        <table class="data">
            <thead>
                <tr>
                    <th style="width: 24px">No</th>
                    <th>Judul Buku</th>
                    <th style="width: 72px">Jenis</th>
                    <th style="width: 78px">Tanggal Pinjam</th>
                    <th style="width: 78px">Jatuh Tempo</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($activePackageLoans as $index => $loan)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $loan->book?->title }}</td>
                        <td class="text-center">Buku Paket</td>
                        <td class="text-center">{{ $loan->given_at?->format('d/m/Y') }}</td>
                        <td class="text-center">{{ $loan->given_at?->copy()->endOfYear()->format('d/m/Y') }}</td>
                    </tr>
                @endforeach
                @foreach ($activeDailyLoans as $index => $loan)
                    <tr>
                        <td class="text-center">{{ $activePackageLoans->count() + $index + 1 }}</td>
                        <td>{{ $loan->book?->title }}</td>
                        <td class="text-center">Koleksi</td>
                        <td class="text-center">{{ $loan->borrowed_at?->format('d/m/Y') }}</td>
                        <td class="text-center">{{ $loan->due_at?->format('d/m/Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <table class="data">
        <thead>
            <tr>
                <th style="width: 24px">No</th>
                <th>Riwayat Peminjaman Selesai</th>
                <th style="width: 72px">Jenis</th>
                <th style="width: 78px">Tanggal Pinjam</th>
                <th style="width: 78px">Tanggal Kembali</th>
                <th style="width: 70px">Kondisi</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($history as $index => $loan)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $loan->book?->title }}</td>
                    <td class="text-center">{{ $loan->type }}</td>
                    <td class="text-center">{{ $loan->started_at?->format('d/m/Y') }}</td>
                    <td class="text-center">{{ $loan->finished_at?->format('d/m/Y') }}</td>
                    <td class="text-center">{{ $loan->condition ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center">Belum ada riwayat peminjaman yang selesai.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table style="width: 100%; margin-top: 6px">
        <tr>
            <td style="width: 55%"></td>
            <td style="text-align: center; font-size: 10px">
                Pustakawan
                <div style="height: 52px"></div>
                <div class="nama">{{ config('perpustakaan.librarian.name') }}</div>
                NIP. {{ config('perpustakaan.librarian.nip') }}
            </td>
        </tr>
    </table>
@endsection
