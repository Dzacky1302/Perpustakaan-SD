<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Slip Perpustakaan' }}</title>
    <style>
        @page { margin: 12mm 12mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10.5px; color: #1f2937; margin: 0; }
        .kop { border-bottom: 3px double #111827; padding-bottom: 6px; margin-bottom: 10px; }
        .kop table { width: 100%; border-collapse: collapse; }
        .kop td { vertical-align: middle; }
        .kop .logo { width: 50px; }
        .kop .logo-inner {
            width: 46px; height: 46px; border: 2px solid #1d4ed8; border-radius: 50%;
            text-align: center; line-height: 42px; font-weight: bold; font-size: 13px; color: #1d4ed8;
        }
        .kop .instansi { text-align: center; }
        .kop .instansi .dinas { font-size: 8.5px; letter-spacing: 0.8px; text-transform: uppercase; }
        .kop .instansi .sekolah { font-size: 13px; font-weight: bold; text-transform: uppercase; }
        .kop .instansi .alamat { font-size: 8px; color: #4b5563; }
        .nomor { text-align: right; font-size: 10px; margin-bottom: 6px; }
        h1.judul {
            text-align: center; font-size: 12px; text-transform: uppercase;
            margin: 0 0 10px; letter-spacing: 0.5px;
        }
        table.data { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        table.data th, table.data td { border: 1px solid #9ca3af; padding: 5px 6px; font-size: 10px; }
        table.data th { background: #e5e7eb; text-align: left; width: 34%; }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .keterangan { font-size: 9.5px; line-height: 1.55; margin-bottom: 12px; }
        .ttd { width: 100%; }
        .ttd td { text-align: center; vertical-align: top; font-size: 10px; }
        .ttd .ruang { height: 58px; }
        .ttd .nama { font-weight: bold; text-decoration: underline; }
        .potong { border-top: 1px dashed #9ca3af; margin-top: 10px; padding-top: 6px; font-size: 8px; color: #6b7280; }
        .besar { font-size: 15px; font-weight: bold; }
        .kuning { background: #fef3c7; }
        .hijau { background: #dcfce7; }
        .kode {
            border: 1.5px dashed #4b5563; border-radius: 3px; padding: 6px 8px;
            margin: 6px 0 10px; text-align: center;
        }
        .kode .judul-kode { font-size: 8px; text-transform: uppercase; letter-spacing: 0.8px; color: #6b7280; }
        .kode .isi-kode { font-size: 17px; font-weight: bold; letter-spacing: 3px; font-family: DejaVu Sans Mono, monospace; }
        .salinan {
            position: fixed; top: 42%; left: 0; right: 0; text-align: center;
            font-size: 58px; font-weight: bold; color: rgba(185, 28, 28, 0.16);
            letter-spacing: 8px; transform: rotate(-22deg); pointer-events: none;
        }
        .salinan-teks { font-size: 9px; font-weight: bold; color: #b91c1c; text-align: right; margin-bottom: 4px; }
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
                        {{ config('perpustakaan.school.address') }} &middot; NPSN {{ config('perpustakaan.school.npsn') }}
                    </div>
                </td>
                <td class="logo"></td>
            </tr>
        </table>
    </div>

    <div class="nomor">Nomor: {{ $slipNumber ?? '-' }}</div>

    @unless (empty($salinan))
        <div class="salinan-teks">
            SALINAN KE-{{ $salinan }} &mdash; dokumen ini bukan salinan asli
        </div>
    @endunless

    <h1 class="judul">{{ $title ?? 'Slip Perpustakaan' }}</h1>

    @unless (empty($verificationCode))
        <div class="kode">
            <div class="judul-kode">Kode Verifikasi Kuitansi</div>
            <div class="isi-kode">{{ $verificationCode }}</div>
        </div>
    @endunless

    @yield('content')

    <table class="ttd">
        <tr>
            <td style="width: 34%">
                {{ config('perpustakaan.school.name') }}, {{ now()->translatedFormat('d F Y') }}<br>
                {{ $signatureLeft ?? 'Pustakawan' }}
                <div class="ruang"></div>
                <div class="nama">{{ $signatureLeftName ?? config('perpustakaan.librarian.name') }}</div>
            </td>
            <td style="width: 32%">
                @isset($student)
                    Penerima, {{ now()->translatedFormat('d F Y') }}<br>
                @endisset
                {{ $student->name ?? 'Siswa' }}
                <div class="ruang"></div>
                <div class="nama">{{ $student->name ?? '-' }}</div>
            </td>
            <td>
                @isset($fineLabel)
                    Penyetor, {{ now()->translatedFormat('d F Y') }}<br>
                    Orang Tua / Wali
                @else
                    {{ $signatureRight ?? 'Kepala Sekolah' }}
                @endif
                <div class="ruang"></div>
                @if (isset($fineLabel))
                    <div class="nama">..............................</div>
                @else
                    <div class="nama">{{ $signatureRightName ?? config('perpustakaan.headmaster.name') }}</div>
                @endif
            </td>
        </tr>
    </table>

    <div class="potong">
        Dicetak dari PustakaSD pada {{ now()->translatedFormat('d F Y H:i') }} WIB.
        Simpan slip ini sebagai bukti resmi perpustakaan sekolah.
    </div>

    @unless (empty($salinan))
        <div class="salinan">SALINAN</div>
    @endunless
</body>
</html>