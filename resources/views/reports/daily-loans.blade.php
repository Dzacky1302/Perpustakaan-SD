@extends('reports.layout')

@section('content')
    <table class="ringkasan">
        <tr>
            <td class="label">Total Peminjaman pada Periode Ini</td>
            <td>{{ number_format($summary['total'], 0, ',', '.') }} transaksi</td>
        </tr>
        <tr>
            <td class="label">Sudah Dikembalikan</td>
            <td>{{ number_format($summary['returned'], 0, ',', '.') }} buku</td>
        </tr>
        <tr>
            <td class="label">Masih Dipinjam (termasuk terlambat)</td>
            <td>{{ number_format($summary['active'], 0, ',', '.') }} buku &middot; {{ $summary['overdue'] }} terlambat</td>
        </tr>
        <tr>
            <td class="label">Dinyatakan Hilang</td>
            <td>{{ number_format($summary['lost'], 0, ',', '.') }} buku</td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 24px">No</th>
                <th>Nama Siswa</th>
                <th style="width: 48px">Kelas</th>
                <th>Judul Buku</th>
                <th style="width: 62px">Pinjam</th>
                <th style="width: 62px">Tempo</th>
                <th style="width: 62px">Kembali</th>
                <th style="width: 68px">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($loans as $index => $loan)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $loan->student?->name }}</td>
                    <td class="text-center">{{ $loan->student?->classroom?->name }}</td>
                    <td>{{ $loan->book?->title }}</td>
                    <td class="text-center">{{ $loan->borrowed_at?->format('d/m/Y') }}</td>
                    <td class="text-center">{{ $loan->due_at?->format('d/m/Y') }}</td>
                    <td class="text-center">{{ $loan->returned_at?->format('d/m/Y') ?? '-' }}</td>
                    <td class="text-center">
                        @if ($loan->status === 'kembali')
                            <span class="badge badge-ok">Kembali</span>
                        @elseif ($loan->status === 'hilang')
                            <span class="badge badge-warn">Hilang</span>
                        @elseif ($loan->isOverdue())
                            <span class="badge badge-warn">Terlambat</span>
                        @else
                            Dipinjam
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">Tidak ada peminjaman pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <p class="catatan">
        Maksimal 500 transaksi terbaru ditampilkan pada laporan cetak.
        Gunakan menu Peminjaman untuk melihat atau mengekspor seluruh data dalam format Excel.
    </p>
@endsection
