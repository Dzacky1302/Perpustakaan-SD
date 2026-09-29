@extends('reports.layout')

@section('content')
    <table class="ringkasan">
        <tr>
            <td class="label">Jumlah Data</td>
            <td>{{ number_format($summary['count'], 0, ',', '.') }} buku</td>
        </tr>
        <tr>
            <td class="label">Total Denda Periode Ini</td>
            <td><strong>Rp{{ number_format($summary['total'], 0, ',', '.') }}</strong></td>
        </tr>
        <tr>
            <td class="label">Denda Belum Dibayar (Keseluruhan)</td>
            <td>Rp{{ number_format($summary['unpaid'], 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">Denda yang Sudah Diterima (Keseluruhan)</td>
            <td>Rp{{ number_format($summary['collected'], 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td class="label">Ketentuan Denda</td>
            <td>{{ $summary['rate'] }} per hari keterlambatan, maksimal {{ $summary['max'] }} per buku</td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 24px">No</th>
                <th>Nama Siswa</th>
                <th style="width: 48px">Kelas</th>
                <th>Judul Buku</th>
                <th style="width: 62px">Tempo</th>
                <th style="width: 62px">Kembali</th>
                <th style="width: 40px">Hari</th>
                <th style="width: 62px">Denda</th>
                <th style="width: 68px">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($loans as $index => $loan)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $loan->student?->name }}</td>
                    <td class="text-center">{{ $loan->effectiveClassroom()?->name ?? '-' }}</td>
                    <td>{{ $loan->book?->title }}</td>
                    <td class="text-center">{{ $loan->due_at?->format('d/m/Y') }}</td>
                    <td class="text-center">{{ $loan->returned_at?->format('d/m/Y') ?? '-' }}</td>
                    <td class="text-center">{{ (int) $loan->fine_days_late }}</td>
                    <td class="text-right">Rp{{ number_format((int) $loan->fine_amount, 0, ',', '.') }}</td>
                    <td class="text-center">
                        @if ($loan->fine_paid_at)
                            <span class="badge badge-ok">Lunas</span>
                        @else
                            <span class="badge badge-warn">Belum</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">Tidak ada denda keterlambatan pada laporan ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p class="catatan">
        Denda dihitung otomatis saat buku dicatat dikembalikan, sehingga angka ini
        tidak berubah lagi walau tanggal sistem diubah kemudian. Maksimal 500 transaksi
        ditampilkan pada laporan cetak.
    </p>
@endsection