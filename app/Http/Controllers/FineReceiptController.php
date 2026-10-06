<?php

namespace App\Http\Controllers;

use App\Models\DailyLoan;
use App\Models\FineReceipt;
use App\Services\FineReceiptService;
use App\Services\FineService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Kuitansi denda: cetak, riwayat cetak, dan pengecekan kode verifikasi.
 *
 * Halaman cek sengaja hanya untuk petugas yang sudah masuk. Kode verifikasi
 * dipakai petugas untuk meyakinkan orang tua bahwa denda itu benar ada,
 * bukan untuk dibuka publik, agar kode tidak bisa ditebak dari luar.
 */
class FineReceiptController extends Controller
{
    public function __construct(
        private readonly FineReceiptService $receipts,
        private readonly FineService $fine,
    ) {
    }

    /**
     * Cetak kuitansi. Jenisnya mengikuti keadaan denda:
     * belum dibayar -> tagihan, sudah dibayar -> pelunasan.
     */
    public function print(DailyLoan $dailyLoan)
    {
        // Kuitansi adalah dokumen keuangan dan tiap cetakan tercatat sebagai
        // salinan, jadi pencetakannya hanya untuk pustakawan. Kepsek cukup
        // membuka arsip & laporan.
        abort_unless(request()->user()?->isAdmin() ?? false, 403, 'Hanya pustakawan yang dapat mencetak kuitansi.');

        $loan = $dailyLoan->load(['student.classroom', 'classroom', 'book', 'fineReceiver']);

        // Nominal yang tercetak harus angka yang sudah DIKUNCI (fine_amount),
        // bukan proyeksi liveFine(). Selama buku masih dipinjam dendanya masih
        // berjalan dan belum ada yang bisa ditagih, jadi kuitansinya belum sah.
        $amount = (int) $loan->fine_amount;

        if ($amount <= 0) {
            return back()->with(
                'error',
                $loan->status === DailyLoan::STATUS_DIPINJAM
                    ? 'Buku ini masih dipinjam. Kuitansi baru bisa dicetak setelah buku dikembalikan dan dendanya dikunci.'
                    : 'Peminjaman ini tidak punya denda, kuitansi tidak diperlukan.'
            );
        }

        $isPaid = $loan->fineIsPaid();

        // Peminjaman lama mungkin belum punya kuitansi: terbitkan saat diminta
        // supaya arsipnya tetap lengkap.
        $receipt = $isPaid
            ? ($this->receipts->find($loan, FineReceipt::TYPE_PELUNASAN) ?? $this->receipts->issueForPayment($loan, request()->user()))
            : ($this->receipts->find($loan, FineReceipt::TYPE_TAGIHAN) ?? $this->receipts->issueForFine($loan));

        // Dicatat setiap kali dicetak, cetakan kedua ke atas ditandai SALINAN.
        $copyNumber = $this->receipts->recordPrint($receipt, request()->user());

        return $this->inlinePdf('slips.kuitansi-denda', [
            'receipt' => $receipt->load('dailyLoan.student', 'dailyLoan.classroom', 'dailyLoan.book', 'issuer'),
            'isPaid' => $isPaid,
            'slipNumber' => $receipt->receipt_number,
            'verificationCode' => $receipt->verification_code,
            'fineLabel' => $this->fine->format($amount),
            'salinan' => $copyNumber > 1 ? $copyNumber : null,
            'printCount' => $copyNumber,
        ], 'kuitansi-'.$receipt->receipt_number);
    }

    /**
     * Daftar kuitansi beserta jumlah cetaknya.
     */
    public function index(Request $request): Response
    {
        $type = $request->query('type') ?: null;
        $search = trim((string) $request->query('search', ''));

        $receipts = FineReceipt::query()
            ->with(['dailyLoan.student:id,name,nisn', 'dailyLoan.book:id,title', 'issuer:id,name'])
            ->withCount('prints')
            ->type($type)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('receipt_number', 'like', "%{$search}%")
                        ->orWhere('verification_code', 'like', '%'.strtoupper($search).'%')
                        ->orWhereHas('dailyLoan.student', fn ($student) => $student->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest('issued_at')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (FineReceipt $receipt) => [
                'id' => $receipt->id,
                'receipt_number' => $receipt->receipt_number,
                'type' => $receipt->type,
                'amount_label' => $this->fine->format((int) $receipt->amount),
                'verification_code' => $receipt->verification_code,
                'student' => $receipt->dailyLoan?->student?->name,
                'nisn' => $receipt->dailyLoan?->student?->nisn,
                'book' => $receipt->dailyLoan?->book?->title,
                'issued_at' => $receipt->issued_at?->toDateString(),
                'issuer' => $receipt->issuer?->name,
                'print_count' => $receipt->prints_count,
                'loan_id' => $receipt->daily_loan_id,
            ]);

        return Inertia::render('Circulation/Receipts', [
            'receipts' => $receipts,
            'filters' => ['type' => $type ?? '', 'search' => $search],
            'summary' => [
                'total' => FineReceipt::count(),
                'tagihan' => FineReceipt::where('type', FineReceipt::TYPE_TAGIHAN)->count(),
                'pelunasan' => FineReceipt::where('type', FineReceipt::TYPE_PELUNASAN)->count(),
                'duplikat' => FineReceipt::has('prints', '>', 1)->count(),
            ],
        ]);
    }

    /**
     * Halaman pengecekan kode verifikasi (khusus petugas yang sudah masuk).
     */
    public function verify(Request $request): Response
    {
        $code = trim((string) $request->query('code', ''));
        $receipt = null;
        $notFound = false;

        if ($code !== '') {
            $receipt = $this->receipts->findByCode($code);
            $notFound = $receipt === null;
        }

        return Inertia::render('Circulation/VerifyReceipt', [
            'code' => $code,
            'notFound' => $notFound,
            'receipt' => $receipt ? $this->receiptPayload($receipt) : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function receiptPayload(FineReceipt $receipt): array
    {
        return [
            'receipt_number' => $receipt->receipt_number,
            'type' => $receipt->type,
            'type_label' => $receipt->type === FineReceipt::TYPE_PELUNASAN ? 'Kuitansi Pelunasan' : 'Kuitansi Tagihan',
            'amount_label' => $this->fine->format((int) $receipt->amount),
            'days_late' => (int) $receipt->days_late,
            'rate_label' => $this->fine->format((int) $receipt->rate_per_day),
            'due_at' => $receipt->due_at?->translatedFormat('d F Y'),
            'returned_at' => $receipt->returned_at?->translatedFormat('d F Y'),
            'issued_at' => $receipt->issued_at?->translatedFormat('d F Y H:i'),
            'issuer' => $receipt->issuer?->name,
            'student' => $receipt->dailyLoan?->student?->name,
            'nisn' => $receipt->dailyLoan?->student?->nisn,
            'book' => $receipt->dailyLoan?->book?->title,
            'loan_id' => $receipt->daily_loan_id,
            'prints' => $receipt->prints
                ->sortBy('copy_number')
                ->map(fn ($print) => [
                    'copy_number' => $print->copy_number,
                    'printed_at' => $print->printed_at?->translatedFormat('d F Y H:i'),
                    'printed_by' => $print->printer?->name,
                ])
                ->values(),
        ];
    }

    /**
     * Halaman PDF dibuka langsung di tab, supaya petugas bisa langsung
     * memilih "Cetak" dari dialog peramban.
     *
     * @param  array<string, mixed>  $data
     */
    private function inlinePdf(string $view, array $data, string $filename): HttpResponse
    {
        $pdf = Pdf::loadView($view, $data)->setPaper('a5', 'portrait');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.str_replace('/', '-', $filename).'.pdf"',
        ]);
    }
}