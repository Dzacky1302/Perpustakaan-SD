<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use App\Services\BookStockService;
use App\Services\SpreadsheetService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BookController extends Controller
{
    public function __construct(
        private readonly BookStockService $stock,
        private readonly SpreadsheetService $spreadsheet,
    ) {
    }

    public function index(Request $request): Response
    {
        $filters = $request->only('search', 'book_type', 'category_id');

        $books = Book::query()
            ->with('category:id,code,name,color')
            ->search($filters['search'] ?? null)
            ->type($filters['book_type'] ?? null)
            ->when($filters['category_id'] ?? null, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->orderBy('code')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (Book $book) => [
                'id' => $book->id,
                'code' => $book->code,
                'title' => $book->title,
                'author' => $book->author,
                'publisher' => $book->publisher,
                'published_year' => $book->published_year,
                'book_type' => $book->book_type,
                'grade_level' => $book->grade_level,
                'shelf_location' => $book->shelf_location,
                'funding_source' => $book->funding_source,
                'total_copies' => $book->total_copies,
                'available_copies' => $book->available_copies,
                'category_id' => $book->category_id,
                'category' => $book->category?->name,
                'category_color' => $book->category?->color,
            ]);

        return Inertia::render('Books/Index', [
            'books' => $books,
            'categories' => Category::orderBy('code')->get(['id', 'code', 'name']),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'book_type' => $filters['book_type'] ?? '',
                'category_id' => $filters['category_id'] ?? '',
            ],
            'summary' => [
                'titles' => Book::count(),
                'copies' => (int) Book::sum('total_copies'),
                'package_titles' => Book::where('book_type', 'paket')->count(),
                'collection_titles' => Book::where('book_type', 'koleksi')->count(),
            ],
            'nextCode' => $this->nextCode(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $validated['available_copies'] = $validated['total_copies'];

        Book::create($validated);

        return back()->with('success', 'Buku baru berhasil ditambahkan ke katalog.');
    }

    public function update(Request $request, Book $book): RedirectResponse
    {
        $validated = $request->validate($this->rules($book));

        // Jumlah tersedia ikut berubah ketika total eksemplar disesuaikan.
        $difference = (int) $validated['total_copies'] - $book->total_copies;
        $validated['available_copies'] = max(0, min(
            $validated['total_copies'],
            $book->available_copies + $difference
        ));

        $book->update($validated);

        return back()->with('success', 'Data buku berhasil diperbarui.');
    }

    public function destroy(Book $book): RedirectResponse
    {
        $activeLoans = $book->dailyLoans()->where('status', 'dipinjam')->count()
            + $book->packageLoans()->where('status', 'dipinjam')->count();

        if ($activeLoans > 0) {
            return back()->with('error', 'Buku masih dipinjam siswa, tidak bisa dihapus.');
        }

        $book->delete();

        return back()->with('success', 'Buku berhasil dihapus dari katalog.');
    }

    /**
     * Perbaiki stok tersedia berdasarkan peminjaman aktif.
     */
    public function syncStock(): RedirectResponse
    {
        $total = $this->stock->syncAll();

        return back()->with('success', "Stok {$total} judul koleksi berhasil disinkronkan.");
    }

    /**
     * Unduh katalog buku dalam format Excel.
     */
    public function export()
    {
        $rows = Book::with('category:id,name')
            ->orderBy('code')
            ->get()
            ->map(fn (Book $book) => [
                $book->code,
                $book->title,
                $book->author,
                $book->publisher,
                $book->published_year,
                $book->category?->name,
                $book->book_type === 'paket' ? 'Buku Paket' : 'Koleksi',
                $book->grade_level ? 'Kelas '.$book->grade_level : '-',
                $book->shelf_location,
                $book->funding_source,
                $book->total_copies,
                $book->available_copies,
            ]);

        $path = $this->spreadsheet->build('katalog-buku.xlsx', [
            'Kode', 'Judul', 'Pengarang', 'Penerbit', 'Tahun', 'Kategori', 'Jenis',
            'Target Kelas', 'Lokasi Rak', 'Sumber Dana', 'Jumlah Eksemplar', 'Tersedia',
        ], $rows);

        return response()->download($path, 'katalog-buku.xlsx')->deleteFileAfterSend(true);
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(?Book $book = null): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('books', 'code')->ignore($book?->id),
            ],
            'title' => ['required', 'string', 'max:120'],
            'author' => ['nullable', 'string', 'max:80'],
            'publisher' => ['nullable', 'string', 'max:80'],
            'published_year' => ['nullable', 'integer', 'between:1900,2100'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'book_type' => ['required', Rule::in(['paket', 'koleksi'])],
            'grade_level' => ['nullable', 'integer', 'between:1,6'],
            'shelf_location' => ['nullable', 'string', 'max:40'],
            'funding_source' => ['nullable', 'string', 'max:60'],
            'total_copies' => ['required', 'integer', 'between:1,9999'],
        ];
    }

    /**
     * Kode buku berikutnya berdasarkan nomor terbesar, contoh BK-0123.
     */
    private function nextCode(): string
    {
        $maxNumber = (int) Book::query()
            ->selectRaw('MAX(CAST(SUBSTR(code, 4) AS INTEGER)) as max_number')
            ->value('max_number');

        return 'BK-'.str_pad((string) ($maxNumber + 1), 4, '0', STR_PAD_LEFT);
    }
}
