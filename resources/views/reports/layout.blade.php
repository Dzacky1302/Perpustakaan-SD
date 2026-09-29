<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Laporan Perpustakaan' }}</title>
    <style>
        @page { margin: 22px 26px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1f2937; }
        .kop { border-bottom: 3px double #111827; padding-bottom: 8px; margin-bottom: 14px; }
        .kop table { width: 100%; border-collapse: collapse; }
        .kop td { vertical-align: middle; }
        .kop .logo { width: 62px; }
        .kop .logo-inner {
            width: 58px; height: 58px; border: 2px solid #1d4ed8; border-radius: 50%;
            text-align: center; line-height: 54px; font-weight: bold; font-size: 16px; color: #1d4ed8;
        }
        .kop .instansi { text-align: center; }
        .kop .instansi .dinas { font-size: 11px; letter-spacing: 1px; text-transform: uppercase; }
        .kop .instansi .sekolah { font-size: 16px; font-weight: bold; text-transform: uppercase; }
        .kop .instansi .alamat { font-size: 9px; color: #4b5563; }
        h1.judul { text-align: center; font-size: 13px; text-transform: uppercase; margin: 0 0 2px; }
        p.periode { text-align: center; font-size: 10px; color: #4b5563; margin: 0 0 12px; }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.data th, table.data td { border: 1px solid #9ca3af; padding: 4px 6px; }
        table.data th { background: #e5e7eb; font-size: 10px; text-transform: uppercase; }
        table.data td { font-size: 10px; }
        table.data tbody tr:nth-child(even) td { background: #f9fafb; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .ringkasan { margin-bottom: 14px; width: 100%; }
        .ringkasan td { border: 1px solid #d1d5db; padding: 5px 8px; font-size: 10px; }
        .ringkasan td.label { background: #f3f4f6; width: 60%; }
        .ttd { width: 100%; margin-top: 18px; }
        .ttd td { text-align: center; vertical-align: top; font-size: 10px; }
        .ttd .ruang { height: 52px; }
        .ttd .nama { font-weight: bold; text-decoration: underline; }
        .catatan { font-size: 9px; color: #6b7280; margin-top: 8px; }
        .badge { padding: 1px 5px; border-radius: 3px; font-size: 9px; }
        .badge-ok { background: #d1fae5; color: #065f46; }
        .badge-warn { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="kop">
        <table>
            <tr>
                <td class="logo"><div class="logo-inner">SD</div></td>
                <td class="instansi">
                    <div class="dinas">{{ config('perpustakaan.school.office') }}</div>
                    <div class="sekolah">{{ config('perpustakaan.school.name') }}</div>
                    <div class="alamat">
                        {{ config('perpustakaan.school.address') }} &middot;
                        Telp. {{ config('perpustakaan.school.phone') }} &middot;
                        NPSN {{ config('perpustakaan.school.npsn') }}
                    </div>
                </td>
                <td class="logo"></td>
            </tr>
        </table>
    </div>

    <h1 class="judul">{{ $title ?? 'Laporan Perpustakaan' }}</h1>
    @isset($periode)
        <p class="periode">{{ $periode }}</p>
    @endisset

    @yield('content')

    <table class="ttd">
        <tr>
            <td style="width: 55%"></td>
            <td>
                Nusantara, {{ now()->translatedFormat('d F Y') }}<br>
                Kepala Sekolah
                <div class="ruang"></div>
                <div class="nama">{{ config('perpustakaan.headmaster.name') }}</div>
                NIP. {{ config('perpustakaan.headmaster.nip') }}
            </td>
        </tr>
    </table>

    <p class="catatan">Dicetak dari aplikasi PustakaSD pada {{ now()->translatedFormat('d F Y H:i') }} WIB.</p>
</body>
</html>
