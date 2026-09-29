@extends('slips.layout', [
    'title' => 'Kuitansi Pembayaran Denda',
    'signatureLeft' => 'Pustakawan',
    'signatureRightName' => null,
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
            <th>Jatuh Tempo</th>
            <td>{{ $loan->due_at?->translatedFormat('d F Y') }}</td>
        </tr>
        <tr>
            <th>Tanggal Dikembalikan</th>
            <td>{{ $loan->returned_at?->translatedFormat('d F Y') }}</td>
        </tr>
        <tr>
            <th>Lama Terlambat</th>
            <td>{{ (int) $loan->fine_days_late }} hari</td>
        </tr>
        <tr class="kuning">
            <th>Total Denda</th>
            <td class="besar">{{ $fineLabel }}</td>
        </tr>
        <tr>
            <th>Status</th>
            <td><strong>LUNAS</strong> &middot; {{ $loan->fine_paid_at?->translatedFormat('d F Y') }}</td>
        </tr>
        <tr>
            <th>Diterima Oleh</th>
            <td>{{ $loan->fineReceiver?->name ?? config('perpustakaan.librarian.name') }}</td>
        </tr>
    </table>

    <p class="keterangan">
        Telah diterima pembayaran denda keterlambatan pengembalian buku dengan rincian
        sebagai tercantum di atas. Kuitansi ini sah sebagai bukti pelunasan dan
        disimpan siswa. Terima kasih atas kerjasamanya.
    </p>
@endsection