<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArticleImageUploadTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;

    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->superAdmin = User::factory()->create([
            'role' => UserRole::SUPER_ADMIN,
            'is_active' => true,
        ]);

        $this->regularUser = User::factory()->create([
            'role' => UserRole::RECEPTIONIST,
            'is_active' => true,
        ]);
    }

    public function test_valid_image_upload_persists_to_public_storage_and_generates_valid_url(): void
    {
        $file = UploadedFile::fake()->image('hospital-news.jpg', 800, 600)->size(500);

        $payload = [
            'title' => 'MediCare Launches Advanced Cardiac Care Suite',
            'summary' => 'New interventional cardiac suite operational at MediCare Hospital.',
            'body' => 'Comprehensive clinical report on the new suite features.',
            'category' => 'Clinical Services',
            'author_name' => 'Communications Desk',
            'cover_image' => $file,
        ];

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.articles.store'), $payload);

        $response->assertStatus(302);

        $article = Article::where('title', 'MediCare Launches Advanced Cardiac Care Suite')->first();
        $this->assertNotNull($article);
        $this->assertNotNull($article->cover_image_path);

        Storage::disk('public')->assertExists($article->cover_image_path);

        // Verify URL generation
        $url = $article->coverUrl();
        $this->assertNotNull($url);
        $this->assertStringContainsString('storage/news-covers', $url);
    }

    public function test_invalid_mime_type_and_oversized_uploads_are_rejected(): void
    {
        // 1. Text file pretending to be image
        $fakeScript = UploadedFile::fake()->create('malicious.php', 10, 'text/x-php');

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.articles.store'), [
                'title' => 'Invalid File Article',
                'summary' => 'Testing upload validation rules.',
                'body' => 'Body text.',
                'category' => 'Announcement',
                'author_name' => 'Editor',
                'cover_image' => $fakeScript,
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors(['cover_image']);

        // 2. Oversized image (> 2MB)
        $oversizedImage = UploadedFile::fake()->image('huge.jpg')->size(3000);

        $response2 = $this->actingAs($this->superAdmin)
            ->post(route('admin.articles.store'), [
                'title' => 'Oversized File Article',
                'summary' => 'Testing size validation.',
                'body' => 'Body text.',
                'category' => 'Announcement',
                'author_name' => 'Editor',
                'cover_image' => $oversizedImage,
            ]);

        $response2->assertStatus(302);
        $response2->assertSessionHasErrors(['cover_image']);
    }

    public function test_missing_cover_image_file_returns_null_and_renders_fallback(): void
    {
        $article = Article::create([
            'title' => 'Article With Missing File',
            'slug' => 'article-with-missing-file',
            'summary' => 'Summary text.',
            'body' => 'Full article body paragraph.',
            'category' => 'Health Advisory',
            'author_name' => 'Dr. Vance',
            'cover_image_path' => 'news-covers/non_existent_file_12345.jpg',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'created_by' => $this->superAdmin->id,
            'published_by' => $this->superAdmin->id,
        ]);

        // File does not physically exist in Storage fake
        $this->assertNull($article->coverUrl());

        // Detail page must not display broken image link
        $response = $this->get(route('news.show', $article->slug));
        $response->assertStatus(200);
        $response->assertDontSee('non_existent_file_12345.jpg');

        // Listing page must render fallback icon
        $listResponse = $this->get(route('news'));
        $listResponse->assertStatus(200);
        $listResponse->assertSee('bi-newspaper');
        $listResponse->assertDontSee('non_existent_file_12345.jpg');
    }

    public function test_replacing_article_image_deletes_old_file_safely(): void
    {
        $initialFile = UploadedFile::fake()->image('cover1.jpg', 400, 300);
        $initialPath = $initialFile->store('news-covers', 'public');

        $article = Article::create([
            'title' => 'Original News Item',
            'slug' => 'original-news-item',
            'summary' => 'Original summary.',
            'body' => 'Body text.',
            'category' => 'Announcement',
            'author_name' => 'Editor',
            'cover_image_path' => $initialPath,
            'created_by' => $this->superAdmin->id,
        ]);

        Storage::disk('public')->assertExists($initialPath);

        // Upload replacement image
        $newFile = UploadedFile::fake()->image('cover2.png', 400, 300);

        $response = $this->actingAs($this->superAdmin)
            ->put(route('admin.articles.update', $article), [
                'title' => 'Original News Item',
                'summary' => 'Original summary.',
                'body' => 'Body text.',
                'category' => 'Announcement',
                'author_name' => 'Editor',
                'cover_image' => $newFile,
            ]);

        $response->assertStatus(302);

        $article->refresh();
        $this->assertNotEquals($initialPath, $article->cover_image_path);
        Storage::disk('public')->assertExists($article->cover_image_path);
        Storage::disk('public')->assertMissing($initialPath);
    }

    public function test_shared_cover_file_is_not_deleted_if_still_referenced_by_another_article(): void
    {
        $sharedFile = UploadedFile::fake()->image('shared.jpg', 400, 300);
        $sharedPath = $sharedFile->store('news-covers', 'public');

        $article1 = Article::create([
            'title' => 'Article One',
            'slug' => 'article-one',
            'summary' => 'Summary 1',
            'body' => 'Body 1',
            'category' => 'Announcement',
            'author_name' => 'Editor',
            'cover_image_path' => $sharedPath,
            'created_by' => $this->superAdmin->id,
        ]);

        $article2 = Article::create([
            'title' => 'Article Two',
            'slug' => 'article-two',
            'summary' => 'Summary 2',
            'body' => 'Body 2',
            'category' => 'Announcement',
            'author_name' => 'Editor',
            'cover_image_path' => $sharedPath,
            'created_by' => $this->superAdmin->id,
        ]);

        // Remove cover from article1
        $this->actingAs($this->superAdmin)
            ->put(route('admin.articles.update', $article1), [
                'title' => 'Article One',
                'summary' => 'Summary 1',
                'body' => 'Body 1',
                'category' => 'Announcement',
                'author_name' => 'Editor',
                'remove_cover' => 1,
            ]);

        $article1->refresh();
        $this->assertNull($article1->cover_image_path);

        // Shared file MUST STILL EXIST because article2 still references it
        Storage::disk('public')->assertExists($sharedPath);
    }
}
