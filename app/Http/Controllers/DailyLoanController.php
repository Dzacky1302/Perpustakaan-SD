<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookCopy;
use App\Models\DailyLoan;
use App\Models\Student;
use App\Services\BookCopyService;
use App\Services\BookStockService;
use App\Services\FineService;
use App\Services\SlipService;
use App\Services\SpreadsheetService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DailyLoanController extends Controller
{
    public function __construct(
        private readonly BookStockService $stock,
        private readonly BookCopyService $copyService,
        private readonly SpreadsheetService $spreadsheet,
        private readonly FineService $fine,
        private readonly SlipService $slip,
    ) {
    }

    public function index(Request $request): Response
    {
        $filters = $request->only('status', 'search', 'from', 'to');
        $status = $filters['status'] ?? 'aktif';

        $loans = $this->filteredQuery($status, $filters)
            ->with(['student:id,name,classroom_id', 'book:id,code,title'])
            ->paginate(25)
            ->withQueryString()
            ->through(fn (DailyLoan $loan) => [
                'id' => $loan->id,
                'student' => $loan->student?->name,
                'classroom' => $loan->effectiveClassroom()?->name,
                'book' => $loan->book?->title,
                'book_code' => $loan->book?->code,
                'borrowed_at' => $loan->borrowed_at?->toDateString(),
                'due_at' => $loan->due_at?->toDateString(),
                'returned_at' => $loan->returned_at?->toDateString(),
                'status' => $loan->status,
                'is_overdue' => $loan->isOverdue(),
                'days_late' => $loan->daysLate(),
                'fine' => $loan->liveFine(),
                'fine_label' => 'Rp'.number_format($loan->liveFine(), 0, ',', '.'),
                'fine_paid' => $loan->fineIsPaid(),
                'fine_paid_at' => $loan->fine_paid_at?->toDateString(),
                'slip_number' => $loan->slip_number,
                'return_slip_number' => $loan->return_slip_number,
                'notes' => $loan->notes,
            ]);

        return Inertia::render('Circulation/DailyLoans', [
            'loans' => $loans,
            'filters' => [
                'status' => $status,
                'search' => $filters['search'] ?? '',
                'from' => $filters['from'] ?? '',
                'to' => $filters['to'] ?? '',
            ],
            'summary' => [
                'active' => DailyLoan::active()->count(),
                'overdue' => DailyLoan::active()->whereDate('due_at', '<', Carbon::today())->count(),
                'returned_today' => DailyLoan::whereDate('returned_at', Carbon::today())->count(),
                'borrowed_today' => DailyLoan::whereDate('borrowed_at', Carbon::today())->count(),
                'fine_unpaid' => $this->fine->totalUnpaid(),
                'fine_unpaid_label' => $this->fine->format($this->fine->totalUnpaid()),
                'fine_collected' => $this->fine->totalCollected(),
                'students_blocked' => Student::whereIn(
                    'id',
                    DailyLoan::where('fine_amount', '>', 0)->whereNull('fine_paid_at')->distinct('student_id')->pluck('student_id')
                )->count(),
            ],

            // Dipakai form "Catat Peminjaman".
            'students' => Student::query()
                ->where('is_active', true)
                ->with('classroom:id,name')
                ->orderBy('name')
                ->get()
                ->map(fn (Student $student) => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'nisn' => $student->nisn,
                    'classroom' => $student->classroom?->name,
                    'active_loans' => DailyLoan::where('student_id', $student->id)
                        ->where('status', DailyLoan::STATUS_DIPINJAM)
                        ->count(),
                ]),
            'loanDays' => (int) config('perpustakaan.loan_days', 7),
            'maxLoans' => (int) config('perpustakaan.max_loans_per_student', 2),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        // Satu kolom isian menerima apa pun: barcode stiker, ISBN tercetak,
        // kode internal, atau kata kunci judul. Kalau semuanya kosong,
        // petugas boleh memilih buku dari daftar seperti biasa.
        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:students,id'],
            'book_id' => ['nullable', 'integer', 'exists:books,id'],
            'book_query' => ['nullable', 'string', 'max:120'],
            'book_copy_id' => ['nullable', 'integer', 'exists:book_copies,id'],
            'borrowed_at' => ['nullable', 'date'],
            'loan_days' => ['nullable', 'integer', 'between:1,30'],
            'notes' => ['nullable', 'string', 'max:150'],
        ]);

        $book = $this->resolveBook($validated);

        if (! $book) {
            return back()->with('error', 'Buku tidak ditemukan. Scan barcode, ketik ISBN/kode, atau pilih dari daftar.');
        }

        if (! $this->stock->isAvailable($book)) {
            return back()->with('error', "Stok \"{$book->title}\" sedang kosong, semua eksemplar dipinjam.");
        }

        $borrowedAt = isset($validated['borrowed_at'])
            ? Carbon::parse($validated['borrowed_at'])
            : Carbon::today();

        $student = Student::findOrFail($validated['student_id']);

        // Siswa dengan denda yang belum dibayar tidak boleh menambah tanggungan.
        if ($message = $this->fine->blockMessage($student)) {
            return back()->with('error', $message);
        }

        DB::transaction(function () use ($validated, $book, $borrowedAt, $student) {
            $loan = DailyLoan::create([
                'student_id' => $student->id,
                'classroom_id' => $student->classroom_id,
                'book_id' => $book->id,
                'book_copy_id' => $this->resolveCopy($book, $validated),
                'borrowed_at' => $borrowedAt,
                'due_at' => $borrowedAt->copy()->addDays($validated['loan_days'] ?? 7),
                'status' => DailyLoan::STATUS_DIPINJAM,
                'notes' => $validated['notes'] ?? null,
            ]);

            $this->slip->ensureLoanNumber($loan);
            $this->stock->decrease($book);

            return $loan;
        });

        return back()->with('success', "Peminjaman \"{$book->title}\" oleh {$student->name} berhasil dicatat.");
    }

    /**
     * Pencarian buku untuk kolom isian pada form peminjaman.
     *
     * Menerima barcode, ISBN, kode internal, atau kata kunci. Dipakai juga
     * sebagai daftar cadangan ketika barcode tidak terbaca.
     */
    public function searchBooks(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        // Input yang persis cocok berhak mendapat jawaban pasti, bukan daftar tebakan.
        if ($term !== '') {
            $found = $this->copyService->locate($term);

            if ($found['book']) {
                return response()->json(['books' => [$this->bookPayload($found['book'], $found['copy'], $found['matched_by'])]]);
            }
        }

        $books = $this->copyService->search($term, 12)
            ->map(fn (Book $book) => $this->bookPayload($book, null, null))
            ->values();

        return response()->json(['books' => $books]);
    }

    /**
     * @return array<string, mixed>
     */
    private function bookPayload(Book $book, ?BookCopy $copy, ?string $matchedBy): array
    {
        return [
            'id' => $book->id,
            'code' => $book->code,
            'isbn' => $book->isbn,
            'title' => $book->title,
            'author' => $book->author,
            'available_copies' => $book->available_copies,
            'book_copy_id' => $copy?->id,
            'accession_number' => $copy?->accession_number,
            'barcode' => $copy?->barcode,
            'matched_by' => $matchedBy,
        ];
    }

    /**
     * Tentukan buku dari input bebas (barcode/ISBN/kode/judul) atau pilihan menu.
     */
    private function resolveBook(array $validated): ?Book
    {
        if (filled($validated['book_id'] ?? null)) {
            return Book::find($validated['book_id']);
        }

        if (filled($validated['book_query'] ?? null)) {
            $found = $this->copyService->locate($validated['book_query'])['book'];

            if ($found) {
                return $found;
            }
        }

        return null;
    }

    /**
     * Eksemplar mana yang dipinjam.
     *
     * Kalau petugas memilih eksemplar secara spesifik (mis. lewat barcode),
     * itu yang dipakai. Kalau hanya mengetik ISBN atau judul, sistem memilih
     * eksemplar yang available secara otomatis.
     */
    private function resolveCopy(Book $book, array $validated): ?int
    {
        if (filled($validated['book_copy_id'] ?? null)) {
            $copy = BookCopy::find($validated['book_copy_id']);

            if ($copy && $copy->book_id === $book->id) {
                return $copy->id;
            }
        }

        return $this->copyService->allocate($book)?->id;
    }

    /**
     * Tandai buku sudah dikembalikan.
     */
    public function returnBook(Request $request, DailyLoan $dailyLoan): RedirectResponse
    {
        if ($dailyLoan->status !== DailyLoan::STATUS_DIPINJAM) {
            return back()->with('error', 'Peminjaman ini sudah diselesaikan sebelumnya.');
        }

        $validated = $request->validate([
            'returned_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:150'],
        ]);

        $returnedAt = isset($validated['returned_at'])
            ? Carbon::parse($validated['returned_at'])
            : Carbon::today();

        DB::transaction(function () use ($dailyLoan, $validated, $returnedAt) {
            $dailyLoan->update([
                'returned_at' => $returnedAt,
                'status' => DailyLoan::STATUS_KEMBALI,
                'notes' => $validated['notes'] ?? $dailyLoan->notes,
            ]);

            // Denda dikunci di saat buku kembali: mulai hari ini angka
            // tidak berubah lagi walau tanggal sistem diubah.
            $this->fine->settle($dailyLoan, $returnedAt);
            $this->slip->ensureReturnNumber($dailyLoan, $returnedAt);

            $this->stock->increase($dailyLoan->book);
        });

        $fineDue = (int) $dailyLoan->fresh()->fine_amount;

        if ($fineDue > 0) {
            return back()->with(
                'warning',
                "Pengembalian dicatat. Denda keterlambatan sebesar {$this->fine->format($fineDue)} harus dilunasi oleh siswa."
            );
        }

        return back()->with('success', 'Pengembalian buku berhasil dicatat.');
    }

    /**
     * Tandai buku hilang (stok tidak dikembalikan, tercatat sebagai temuan).
     */
    public function lost(Request $request, DailyLoan $dailyLoan): RedirectResponse
    {
        if ($dailyLoan->status !== DailyLoan::STATUS_DIPINJAM) {
            return back()->with('error', 'Peminjaman ini sudah diselesaikan sebelumnya.');
        }

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:150'],
        ]);

        $dailyLoan->update([
            'status' => DailyLoan::STATUS_HILANG,
            'notes' => $validated['notes'] ?? 'Buku dinyatakan hilang.',
        ]);

        // Buku hilang tetap dihitung dendanya sampai batas maksimal.
        $this->fine->settle($dailyLoan, Carbon::today());
        $this->slip->ensureReturnNumber($dailyLoan);

        return back()->with('error', 'Buku ditandai hilang. Mohon catat penggantiannya di buku inventaris.');
    }

    /**
     * Catat pembayaran denda oleh siswa.
     */
    public function payFine(Request $request, DailyLoan $dailyLoan): RedirectResponse
    {
        if ($dailyLoan->fineIsPaid()) {
            return back()->with('error', 'Denda untuk peminjaman ini sudah tercatat lunas.');
        }

        $amount = $dailyLoan->status === DailyLoan::STATUS_DIPINJAM
            ? $dailyLoan->liveFine()
            : (int) $dailyLoan->fine_amount;

        if ($amount <= 0) {
            return back()->with('error', 'Tidak ada denda yang perlu dibayar untuk peminjaman ini.');
        }

        $validated = $request->validate([
            'fine_notes' => ['nullable', 'string', 'max:255'],
            'fine_paid_at' => ['nullable', 'date'],
        ]);

        $this->fine->markPaid(
            $dailyLoan,
            $request->user(),
            $validated['fine_notes'] ?? null,
            isset($validated['fine_paid_at']) ? Carbon::parse($validated['fine_paid_at']) : null,
        );
        return back()->with('success', "Pembayaran denda {$this->fine->format($amount)} berhasil dicatat.");
    }

    /**
     * Batalkan pencatatan pembayaran denda (salah input / siswa menolak).
     */
    public function cancelFine(DailyLoan $dailyLoan): RedirectResponse
    {
        if (! $dailyLoan->fineIsPaid()) {
            return back()->with('error', 'Denda ini memang belum berstatus lunas.');
        }

        $dailyLoan->forceFill([
            'fine_paid_at' => null,
            'fine_received_by' => null,
            'fine_notes' => null,
        ])->save();

        return back()->with('warning', 'Pencatatan pembayaran denda dibatalkan.');
    }

    public function destroy(DailyLoan $dailyLoan): RedirectResponse
    {
        DB::transaction(function () use ($dailyLoan) {
            if ($dailyLoan->status === DailyLoan::STATUS_DIPINJAM) {
                $this->stock->increase($dailyLoan->book);
            }

            $dailyLoan->delete();
        });

        return back()->with('success', 'Catatan peminjaman dihapus.');
    }

    /**
     * Surat peminjaman (slip) untuk dicetak & ditandatangani siswa.
     */
    public function printSlip(DailyLoan $dailyLoan)
    {
        $loan = $this->slip->ensureLoanNumber($dailyLoan->load(['student.classroom', 'classroom', 'book']));

        return $this->inlinePdf('slips.loan', [
            'loan' => $loan,
            'student' => $loan->student,
            'book' => $loan->book,
            'classroomName' => $loan->effectiveClassroom()?->name ?? $loan->student?->classroom?->name,
            'slipNumber' => $loan->slip_number,
        ], 'surat-peminjaman-'.($loan->slip_number ?: $loan->id));
    }

    /**
     * Surat pengembalian (slip) sebagai bukti buku sudah dikembalikan.
     */
    public function printReturnSlip(DailyLoan $dailyLoan)
    {
        $loan = $dailyLoan->load(['student.classroom', 'classroom', 'book']);

        if ($loan->status === DailyLoan::STATUS_DIPINJAM) {
            return back()->with('error', 'Buku ini belum dikembalikan, surat pengembalian belum bisa dicetak.');
        }

        $this->slip->ensureReturnNumber($loan);

        return $this->inlinePdf('slips.return', [
            'loan' => $loan->fresh(),
            'student' => $loan->student,
            'book' => $loan->book,
            'classroomName' => $loan->effectiveClassroom()?->name ?? $loan->student?->classroom?->name,
            'slipNumber' => $loan->fresh()->return_slip_number,
        ], 'surat-pengembalian-'.($loan->fresh()->return_slip_number ?: $loan->id));
    }

    /**
     * Slip cetak yang dibuka langsung di tab/browser (bukan diunduh),
     * supaya staff bisa langsung memilih "Print" dari dialog Cetak.
     *
     * @param  array<string, mixed>  $data
     */
    private function inlinePdf(string $view, array $data, string $filename)
    {
        $pdf = Pdf::loadView($view, $data)->setPaper('a5', 'portrait');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.str_replace('/', '-', $filename).'.pdf"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    /**
     * Halaman rekap denda untuk pustakawan & kepala sekolah.
     */
    public function fines(Request $request): Response
    {
        $filters = $request->only('status', 'search');
        $status = $filters['status'] ?? 'belum';

        $fines = $this->fineQuery($status, $filters)
            ->paginate(25)
            ->withQueryString()
            ->through(fn (DailyLoan $loan) => [
                'id' => $loan->id,
                'student' => $loan->student?->name,
                'nisn' => $loan->student?->nisn,
                'classroom' => $loan->effectiveClassroom()?->name,
                'book' => $loan->book?->title,
                'due_at' => $loan->due_at?->toDateString(),
                'returned_at' => $loan->returned_at?->toDateString(),
                'days_late' => (int) $loan->fine_days_late,
                'fine' => (int) $loan->fine_amount,
                'fine_label' => $this->fine->format((int) $loan->fine_amount),
                'paid' => $loan->fineIsPaid(),
                'paid_at' => $loan->fine_paid_at?->toDateString(),
                'receiver' => $loan->fineReceiver?->name,
            ]);

        return Inertia::render('Circulation/Fines', [
            'fines' => $fines,
            'filters' => [
                'status' => $status,
                'search' => $filters['search'] ?? '',
            ],
            'summary' => [
                'unpaid' => $this->fine->totalUnpaid(),
                'unpaid_label' => $this->fine->format($this->fine->totalUnpaid()),
                'collected' => $this->fine->totalCollected(),
                'collected_label' => $this->fine->format($this->fine->totalCollected()),
                'count_unpaid' => DailyLoan::where('fine_amount', '>', 0)->whereNull('fine_paid_at')->count(),
                'rate' => $this->fine->format($this->fine->dailyRate()),
                'max' => $this->fine->format($this->fine->maxPerBook()),
            ],
        ]);
    }

    /**
     * Ekspor rekap denda ke Excel.
     */
    public function finesExcel(Request $request)
    {
        $status = $request->query('status') ?: 'belum';

        $rows = $this->fineQuery($status, $request->only('search'))
            ->get()
            ->map(fn (DailyLoan $loan, int $index) => [
                $index + 1,
                $loan->student?->nisn,
                $loan->student?->name,
                $loan->effectiveClassroom()?->name ?? '-',
                $loan->book?->title,
                $loan->due_at?->format('d/m/Y'),
                $loan->returned_at?->format('d/m/Y') ?? '-',
                (int) $loan->fine_days_late,
                (int) $loan->fine_amount,
                $loan->fine_paid_at?->format('d/m/Y') ?? 'Belum dibayar',
                $loan->fineReceiver?->name ?? '-',
            ]);

        $filename = 'denda-'.$status.'-'.Carbon::today()->format('Ymd').'.xlsx';

        $path = $this->spreadsheet->build($filename, [
            'No', 'NISN', 'Nama Siswa', 'Kelas', 'Judul Buku', 'Jatuh Tempo',
            'Tanggal Kembali', 'Hari Telat', 'Denda', 'Status Bayar', 'Diterima Oleh',
        ], $rows);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Query dasar rekap denda.
     *
     * @param  array<string, mixed>  $filters
     * @return \Illuminate\Database\Eloquent\Builder<DailyLoan>
     */
    private function fineQuery(string $status, array $filters)
    {
        return DailyLoan::query()
            ->with(['student:id,name,nisn,classroom_id', 'classroom:id,name', 'book:id,code,title', 'fineReceiver:id,name'])
            ->where('fine_amount', '>', 0)
            ->when($status === 'belum', fn ($query) => $query->whereNull('fine_paid_at'))
            ->when($status === 'lunas', fn ($query) => $query->whereNotNull('fine_paid_at'))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query
                ->where(fn ($query) => $query
                    ->whereHas('student', fn ($query) => $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('nisn', 'like', "%{$search}%"))
                    ->orWhereHas('book', fn ($query) => $query->where('title', 'like', "%{$search}%"))))
            ->orderByRaw('case when fine_paid_at is null then 0 else 1 end')
            ->orderByDesc('fine_amount')
            ->orderByDesc('id');
    }

    /**
     * Unduh daftar peminjaman sesuai filter dalam format Excel.
     */
    public function export(Request $request)
    {
        $filters = $request->only('status', 'search', 'from', 'to');
        $status = $filters['status'] ?? 'aktif';

        $rows = $this->filteredQuery($status, $filters)
            ->with(['student.classroom:id,name', 'classroom:id,name', 'book:id,code,title'])
            ->get()
            ->map(fn (DailyLoan $loan, int $index) => [
                $index + 1,
                $loan->student?->nisn,
                $loan->student?->name,
                $loan->effectiveClassroom()?->name ?? 'Tidak tercatat',
                $loan->book?->code,
                $loan->book?->title,
                $loan->borrowed_at?->format('d/m/Y'),
                $loan->due_at?->format('d/m/Y'),
                $loan->returned_at?->format('d/m/Y') ?? '-',
                match ($loan->status) {
                    DailyLoan::STATUS_KEMBALI => 'Kembali',
                    DailyLoan::STATUS_HILANG => 'Hilang',
                    default => $loan->isOverdue() ? 'Terlambat' : 'Dipinjam',
                },
                (int) $loan->fine_amount > 0 ? (int) $loan->fine_amount : '',
                (int) $loan->fine_amount > 0
                    ? ($loan->fine_paid_at ? 'Lunas '.$loan->fine_paid_at->format('d/m/Y') : 'Belum dibayar')
                    : '',
                $loan->slip_number,
                $loan->notes,
            ]);

        $filename = 'peminjaman-'.$status.'-'.Carbon::today()->format('Ymd').'.xlsx';

        $path = $this->spreadsheet->build($filename, [
            'No', 'NISN', 'Nama Siswa', 'Kelas', 'Kode Buku', 'Judul Buku',
            'Tanggal Pinjam', 'Jatuh Tempo', 'Tanggal Kembali', 'Status',
            'Denda', 'Status Denda', 'No. Slip', 'Catatan',
        ], $rows);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Query dasar daftar peminjaman yang dipakai halaman dan ekspor.
     *
     * @param  array<string, mixed>  $filters
     * @return \Illuminate\Database\Eloquent\Builder<DailyLoan>
     */
    private function filteredQuery(string $status, array $filters)
    {
        return DailyLoan::query()
            ->when($status === 'aktif', fn ($query) => $query->active())
            ->when($status === 'terlambat', fn ($query) => $query
                ->active()
                ->whereDate('due_at', '<', Carbon::today()))
            ->when($status === 'kembali', fn ($query) => $query->where('status', DailyLoan::STATUS_KEMBALI))
            ->when($status === 'hilang', fn ($query) => $query->where('status', DailyLoan::STATUS_HILANG))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(fn ($query) => $query
                    ->whereHas('student', fn ($query) => $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('nisn', 'like', "%{$search}%"))
                    ->orWhereHas('book', fn ($query) => $query
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")));
            })
            ->when($filters['from'] ?? null, fn ($query, $from) => $query->whereDate('borrowed_at', '>=', $from))
            ->when($filters['to'] ?? null, fn ($query, $to) => $query->whereDate('borrowed_at', '<=', $to))
            ->orderByDesc('borrowed_at')
            ->orderByDesc('id');
    }
}
