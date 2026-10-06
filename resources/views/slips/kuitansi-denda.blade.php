@extends('slips.layout', [
    'title' => $isPaid ? 'Kuitansi Pelunasan Denda' : 'Kuitansi Tagihan Denda',
    'signatureLeft' => 'Pustakawan',
])

@section('content')
    @php
        $student = $receipt->dailyLoan?->student;
        $book = $receipt->dailyLoan?->book;
        $classroom = $receipt->dailyLoan?->effectiveClassroom()?->name;
    @endphp

    <table class="data">
        <tr>
            <th>Nama Siswa</th>
            <td>{{ $student?->name ?? '-' }}</td>
        </tr>
        <tr>
            <th>NISN / Kelas</th>
            <td>{{ $student?->nisn ?? '-' }} &middot; {{ $classroom ?? 'Tidak tercatat' }}</td>
        </tr>
        <tr>
            <th>Judul Buku</th>
            <td>{{ $book?->title ?? '-' }}</td>
        </tr>
        <tr>
            <th>Jatuh Tempo</th>
            <td>{{ $receipt->due_at?->translatedFormat('d F Y') ?? '-' }}</td>
        </tr>
        <tr>
            <th>Tanggal Dikembalikan</th>
            <td>{{ $receipt->returned_at?->translatedFormat('d F Y') ?? '-' }}</td>
        </tr>
        <tr>
            <th>Terlambat</th>
            <td>{{ (int) $receipt->days_late }} hari sekolah</td>
        </tr>
        <tr>
            <th>Tarif Denda</th>
            <td>{{ 'Rp'.number_format((int) $receipt->rate_per_day, 0, ',', '.') }} per hari sekolah</td>
        </tr>
        <tr class="{{ $isPaid ? 'hijau' : 'kuning' }}">
            <th>{{ $isPaid ? 'Dibayar' : 'Jumlah Denda' }}</th>
            <td class="besar">Rp{{ number_format((int) $receipt->amount, 0, ',', '.') }}</td>
        </tr>
        @if ($isPaid)
            <tr>
                <th>Tanggal Dibayar</th>
                <td>{{ $receipt->issued_at->translatedFormat('d F Y') }}</td>
            </tr>
            <tr>
                <th>Diterima Oleh</th>
                <td>{{ $receipt->issuer?->name ?? config('perpustakaan.librarian.name') }}</td>
            </tr>
        @else
            <tr>
                <th>Status</th>
                <td><strong>BELUM DIBAYAR</strong></td>
            </tr>
        @endif
    </table>

    @if ($isPaid)
        <p class="keterangan">
            Telah diterima pembayaran denda keterlambatan pengembalian buku dengan rincian
            sebagai tercantum di atas. Kuitansi ini sah sebagai bukti pelunasan dan
            disimpan siswa. Terima kasih atas kerjasamanya.
        </p>
    @else
        <p class="keterangan">
            Rincian di atas adalah tagihan denda keterlambatan yang <strong>belum dibayar</strong>.
            Kuitansi ini dibawa pulang untuk ditunjukkan kepada orang tua/wali, agar
            pembayaran dapat dilunasi di perpustakaan sekolah. Selama denda belum
            dilunasi, siswa belum dapat meminjam buku lagi.
        </p>
        <p class="keterangan">
            Pembayaran dapat dilakukan di {{ config('perpustakaan.library_name') }}
            pada hari sekolah, selama jam istirahat atau jam yang disepakati.
        </p>
    @endif

    @unless (empty($printCount))
        <p class="potong">
            Kuitansi ini telah dicetak {{ $printCount }} kali.
            Kalau bukan salinan pertama, dokumen ini bertanda SALINAN.
        </p>
    @endunless
@endsection