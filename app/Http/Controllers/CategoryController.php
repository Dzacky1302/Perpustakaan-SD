<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Master/Categories', [
            'categories' => Category::withCount('books')
                ->withSum('books as copies_sum', 'total_copies')
                ->orderBy('code')
                ->get()
                ->map(fn (Category $category) => [
                    'id' => $category->id,
                    'code' => $category->code,
                    'name' => $category->name,
                    'color' => $category->color,
                    'books_count' => $category->books_count,
                    'copies_sum' => (int) $category->copies_sum,
                ]),
            'colors' => ['sky', 'emerald', 'amber', 'rose', 'violet', 'teal', 'slate'],
            'totalBooks' => Book::count(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Category::create($request->validate($this->rules()));

        return back()->with('success', 'Kategori baru berhasil ditambahkan.');
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $category->update($request->validate($this->rules($category)));

        return back()->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->books()->exists()) {
            return back()->with('error', 'Kategori masih dipakai oleh koleksi buku.');
        }

        $category->delete();

        return back()->with('success', 'Kategori berhasil dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(?Category $category = null): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('categories', 'code')->ignore($category?->id),
            ],
            'name' => ['required', 'string', 'max:80'],
            'color' => ['required', 'string', 'max:20'],
        ];
    }
}
