@extends('slips.layout', [
    'title' => 'Surat Peminjaman Buku',
    'signatureLeft' => 'Pustakawan',
    'signatureRight' => 'Kepala Sekolah',
    'verificationLabel' => 'Nomor Slip (diketik saat pengembalian)',
    'verificationCode' => $slipNumber ?? null,
])

@section('content')
    @php
        $copy = $loan->bookCopy ?? null;
    @endphp

    <table class="data">
        <tr>
            <th>Nama Siswa</th>
            <td>{{ $student?->name }}</td>
        </tr>
        <tr>
            <th>NISN / Kelas</th>
            <td>{{ $student?->nisn }} &middot; {{ $classroomName ?? 'Tidak tercatat' }}</td>
        </tr>
        <tr>
            <th>Judul Buku</th>
            <td>{{ $book?->title }}</td>
        </tr>
        <tr>
            <th>Kode Buku</th>
            <td>{{ $book?->code ?? '-' }}</td>
        </tr>
        <tr>
            <th>No. Registrasi</th>
            <td>{{ $copy?->accession_number ?? 'Belum dicatat' }}</td>
        </tr>
        <tr>
            <th>Barcode Eksemplar</th>
            <td>{{ $copy?->barcode ?? 'Belum ditempel' }}</td>
        </tr>
        <tr>
            <th>Tanggal Pinjam</th>
            <td>{{ $loan->borrowed_at?->translatedFormat('d F Y') }}</td>
        </tr>
        <tr>
            <th>Jatuh Tempo</th>
            <td>{{ $loan->due_at?->translatedFormat('d F Y') }}</td>
        </tr>
        <tr>
            <th>Lama Pinjam</th>
            <td>
                @if ($loanDays !== null)
                    {{ $loanDays }} hari
                @else
                    -
                @endif
            </td>
        </tr>
    </table>

    <p class="keterangan">
        Saya menyatakan benar telah menerima buku tersebut di atas dalam keadaan baik dan
        lengkap. Saya bersedia mengembalikan buku paling lambat pada tanggal jatuh tempo.
        Bila buku tidak dikembalikan sesuai jadwal, saya bersedia membayar denda sesuai
        ketentuan perpustakaan sekolah
        ({{ 'Rp'.number_format((int) config('perpustakaan.fine.daily_rate'), 0, ',', '.') }} per hari sekolah,
        maksimal {{ 'Rp'.number_format((int) config('perpustakaan.fine.max_per_book'), 0, ',', '.') }} per buku).
        Sabtu, Minggu, dan hari libur nasional tidak dihitung sebagai keterlambatan.
    </p>
@endsection