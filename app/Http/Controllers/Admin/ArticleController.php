<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ArticleController extends Controller
{
    /** Roles allowed to publish/unpublish — enforced server-side independently of edit access. */
    private const PUBLISHER_ROLES = ['super_admin', 'hospital_director'];

    public function index(Request $request): View
    {
        $query = Article::with('creator')->latest('updated_at');

        if ($status = $request->input('status')) {
            if (in_array($status, ['draft', 'published'], true)) {
                $query->where('status', $status);
            }
        }
        if ($search = $request->input('search')) {
            $query->where('title', 'like', "%{$search}%");
        }

        return view('admin.articles.index', [
            'articles' => $query->paginate(15)->withQueryString(),
            'status' => $status,
            'search' => $search,
            'canPublish' => Auth::user()->hasRole(self::PUBLISHER_ROLES),
        ]);
    }

    public function create(): View
    {
        return view('admin.articles.form', ['article' => new Article(['category' => 'Announcement']), 'categories' => Article::CATEGORIES]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $article = new Article($data);
        $article->slug = Article::uniqueSlug($data['title']);
        $article->status = 'draft'; // never client-supplied
        $article->created_by = Auth::id();
        if ($request->hasFile('cover_image')) {
            $article->cover_image_path = $request->file('cover_image')->store('news-covers', 'public');
        }
        $article->save();

        AuditService::record('article.created', 'article', (string) $article->id, ['title' => $article->title], 'success', Auth::user());

        return redirect()->route('admin.articles.edit', $article)->with('status', 'Draft saved. It is not visible to the public until published.');
    }

    public function edit(Article $article): View
    {
        return view('admin.articles.form', [
            'article' => $article,
            'categories' => Article::CATEGORIES,
            'canPublish' => Auth::user()->hasRole(self::PUBLISHER_ROLES),
        ]);
    }

    public function update(Request $request, Article $article): RedirectResponse
    {
        $data = $this->validated($request);

        $article->fill($data);
        if ($article->isDirty('title') && ! $article->isPublished()) {
            $article->slug = Article::uniqueSlug($data['title'], $article->id); // published URLs stay stable
        }
        $oldCover = null;
        if ($request->hasFile('cover_image')) {
            $oldCover = $article->cover_image_path;
            $article->cover_image_path = $request->file('cover_image')->store('news-covers', 'public');
        } elseif ($request->boolean('remove_cover') && $article->cover_image_path) {
            $oldCover = $article->cover_image_path;
            $article->cover_image_path = null;
        }
        $article->save();

        if ($oldCover && $oldCover !== $article->cover_image_path) {
            $stillReferenced = Article::where('cover_image_path', $oldCover)->where('id', '!=', $article->id)->exists();
            if (! $stillReferenced && Storage::disk('public')->exists($oldCover)) {
                Storage::disk('public')->delete($oldCover);
            }
        }

        AuditService::record('article.updated', 'article', (string) $article->id, ['title' => $article->title], 'success', Auth::user());

        return redirect()->route('admin.articles.edit', $article)->with('status', 'Article saved.');
    }

    /** Preview of any article (including drafts) for authorised staff only. */
    public function preview(Article $article): View
    {
        return view('pages.news-show', ['article' => $article, 'related' => collect(), 'isPreview' => true]);
    }

    public function publish(Request $request, Article $article): RedirectResponse
    {
        $this->authorizePublisher();

        $article->update([
            'status' => 'published',
            'published_at' => $article->published_at ?? now(),
            'published_by' => Auth::id(),
        ]);

        AuditService::record('article.published', 'article', (string) $article->id, ['title' => $article->title], 'success', Auth::user());

        return back()->with('status', 'Article published to the public news page.');
    }

    public function unpublish(Request $request, Article $article): RedirectResponse
    {
        $this->authorizePublisher();

        $article->update(['status' => 'draft']);

        AuditService::record('article.unpublished', 'article', (string) $article->id, ['title' => $article->title], 'success', Auth::user());

        return back()->with('status', 'Article unpublished and hidden from the public.');
    }

    private function authorizePublisher(): void
    {
        if (! Auth::user()->hasRole(self::PUBLISHER_ROLES)) {
            abort(403, 'Only the Super Admin or Hospital Director may publish or unpublish news.');
        }
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'summary' => ['required', 'string', 'max:400'],
            'body' => ['required', 'string', 'max:20000'],
            'category' => ['required', 'in:'.implode(',', Article::CATEGORIES)],
            'author_name' => ['required', 'string', 'max:120'],
            'published_at' => ['nullable', 'date'],
            'cover_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:2048'],
        ]);

        if (! Auth::user()->hasRole(self::PUBLISHER_ROLES) || empty($data['published_at'])) {
            unset($data['published_at']);
        }

        return $data;
    }
}
