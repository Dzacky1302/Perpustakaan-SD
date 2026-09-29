@extends('reports.layout')

@section('content')
    <table class="ringkasan">
        <tr>
            <td class="label">Kelas</td>
            <td>{{ $classroom->name }} &middot; Wali Kelas: {{ $classroom->homeroom_teacher ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Tahun Ajaran</td>
            <td>{{ $academicYear }}</td>
        </tr>
        <tr>
            <td class="label">Jumlah Buku Paket yang Harus Diterima Setiap Siswa</td>
            <td>{{ $summary['books'] }} judul</td>
        </tr>
        <tr>
            <td class="label">Total Buku Paket Disalurkan ke Kelas Ini</td>
            <td>{{ $summary['distributed'] }} buku</td>
        </tr>
        <tr>
            <td class="label">Total Sudah Kembali / Hilang / Masih Dipinjam</td>
            <td>
                {{ $summary['returned'] }} kembali &middot;
                {{ $summary['lost'] }} hilang &middot;
                {{ $summary['active'] }} masih dipinjam
            </td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 24px">No</th>
                <th>Nama Siswa</th>
                <th style="width: 60px">NISN</th>
                <th style="width: 42px">Terima</th>
                <th style="width: 46px">Kembali</th>
                <th style="width: 42px">Hilang</th>
                <th style="width: 52px">Masih</th>
                <th style="width: 78px">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($students as $index => $student)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $student->name }}</td>
                    <td class="text-center">{{ $student->nisn }}</td>
                    <td class="text-center">{{ $student->total_diterima }}</td>
                    <td class="text-center">{{ $student->total_kembali }}</td>
                    <td class="text-center">{{ $student->total_hilang }}</td>
                    <td class="text-center">{{ $student->total_aktif }}</td>
                    <td class="text-center">
                        @if ($student->total_aktif === 0 && $student->total_diterima > 0)
                            <span class="badge badge-ok">Lengkap</span>
                        @elseif ($student->total_diterima === 0)
                            <span class="badge badge-warn">Belum Terima</span>
                        @else
                            <span class="badge badge-warn">Kurang {{ $student->total_aktif }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">Belum ada data buku paket untuk kelas ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th style="width: 24px">No</th>
                <th>Judul Buku Paket</th>
                <th style="width: 66px">Kode</th>
                <th style="width: 60px">Penerima</th>
                <th style="width: 60px">Kembali</th>
                <th style="width: 52px">Hilang</th>
                <th style="width: 60px">Masih</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($books as $index => $book)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $book->title }}</td>
                    <td class="text-center">{{ $book->code }}</td>
                    <td class="text-center">{{ $book->penerima }}</td>
                    <td class="text-center">{{ $book->kembali }}</td>
                    <td class="text-center">{{ $book->hilang }}</td>
                    <td class="text-center">{{ $book->masih }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="catatan">
        Laporan ini menjadi bukti penelusuran buku paket (BOS) kelas {{ $classroom->name }}.
        Gunakan kolom "Masih" sebagai daftar target penarikan saat kenaikan kelas.
    </p>
@endsection
