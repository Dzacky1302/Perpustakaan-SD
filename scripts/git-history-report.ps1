#Requires -Version 5.1
<#
.SYNOPSIS
  Generate lampiran laporan histori git: tanggal + apa yang diubah.
.DESCRIPTION
  Menulis docs/history-report-YYYY-MM-DD.md berisi:
  - 30 commit terakhir (tanggal, hash, pesan)
  - ringkasan file yang paling sering diubah
  - daftar branch + tag
  Cara pakai: powershell -ExecutionPolicy Bypass -File scripts/git-history-report.ps1
#>
$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

$date = Get-Date -Format 'yyyy-MM-dd'
$outDir = Join-Path $root 'docs'
New-Item -ItemType Directory -Force -Path $outDir | Out-Null
$outFile = Join-Path $outDir "history-report-$date.md"

$log = git log --date=short --pretty='| %ad | %h | %an | %s |' -30
$stats = git log --pretty=format: --name-only -30 | Where-Object { $_ } | Group-Object | Sort-Object Count -Descending | Select-Object -First 20
$branches = git branch -a
$tags = git tag --list

$lines = @(
    "# Riwayat Git — $date",
    '',
    'Dihasilkan otomatis oleh `scripts/git-history-report.ps1`.',
    '',
    '## 30 Commit Terakhir (tanggal | hash | author | pesan)',
    '',
    '| Tanggal | Hash | Author | Pesan |',
    '|---|---|---|---|'
) + $log + @(
    '',
    '## File Paling Sering Diubah (30 commit terakhir)',
    '',
    '| Jumlah | File |',
    '|---|---|'
)
foreach ($s in $stats) { $lines += "| $($s.Count) | $($s.Name) |" }
$lines += @('', '## Branch', '', '```', $branches, '```', '', '## Tag', '', '```', ($tags -join "`n"), '```', '')
$lines | Out-File -FilePath $outFile -Encoding UTF8
Write-Host "OK -> $outFile"
