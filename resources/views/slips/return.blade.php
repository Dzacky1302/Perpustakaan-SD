@extends('slips.layout', [
    'title' => 'Surat Pengembalian Buku',
    'signatureLeft' => 'Pustakawan',
    'signatureRight' => 'Kepala Sekolah',
])

@section('content')
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
            <th>Tanggal Pinjam</th>
            <td>{{ $loan->borrowed_at?->translatedFormat('d F Y') }}</td>
        </tr>
        <tr>
            <th>Jatuh Tempo</th>
            <td>{{ $loan->due_at?->translatedFormat('d F Y') }}</td>
        </tr>
        <tr>
            <th>Tanggal Dikembalikan</th>
            <td>{{ $loan->returned_at?->translatedFormat('d F Y') }}</td>
        </tr>
        <tr>
            <th>Kondisi Buku</th>
            <td>
                @if ($loan->status === 'hilang')
                    <span class="badge badge-warn">Dinyatakan hilang</span>
                @else
                    Baik &middot; lengkap
                @endif
            </td>
        </tr>
    </table>

    @if ((int) $loan->fine_amount > 0)
        <table class="data">
            <tr>
                <th>Denda Keterlambatan</th>
                <td>
                    <span class="besar">{{ 'Rp'.number_format((int) $loan->fine_amount, 0, ',', '.') }}</span>
                    <span class="text-right" style="float: right">
                        ({{ (int) $loan->fine_days_late }} hari terlambat)
                    </span>
                </td>
            </tr>
            <tr>
                <th>Status Denda</th>
                <td>
                    @if ($loan->fine_paid_at)
                        <strong>LUNAS</strong> &middot; dibayar {{ $loan->fine_paid_at->translatedFormat('d F Y') }}
                    @else
                        <strong style="color: #b91c1c">BELUM DIBAYAR</strong>
                        &mdash; mohon dilunasi di perpustakaan
                    @endif
                </td>
            </tr>
        </table>
    @endif

    <p class="keterangan">
        Buku tersebut di atas telah dikembalikan dan diterima pustakawan dalam
        @if ($loan->status === 'hilang')
            keadaan hilang
        @else
            kondisi baik dan lengkap
        @endif
        @if ((int) $loan->fine_amount > 0 && ! $loan->fine_paid_at)
            Denda keterlambatan di atas belum dilunasi dan menjadi tanggung jawab siswa
            beserta orang tua/wali.
        @endif
    </p>
@endsection