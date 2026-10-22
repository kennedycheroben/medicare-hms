<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NewsCmsTest extends TestCase
{
    use RefreshDatabase;

    private function user(UserRole $role): User
    {
        return User::create([
            'name' => 'U '.$role->value,
            'email' => $role->value.'@news.test',
            'password' => 'SecurePass123!',
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function payload(array $over = []): array
    {
        return array_merge([
            'title' => 'Clinic Hours Update',
            'summary' => 'Short summary of the update.',
            'body' => "First paragraph.\n\nSecond <script>alert(1)</script> paragraph.",
            'category' => 'Announcement',
            'author_name' => 'Hospital Administration',
        ], $over);
    }

    public function test_draft_is_private_until_published_and_xss_is_escaped(): void
    {
        $admin = $this->user(UserRole::SUPER_ADMIN);

        $this->actingAs($admin)->post(route('admin.articles.store'), $this->payload())->assertRedirect();
        $article = Article::firstOrFail();
        $this->assertSame('draft', $article->status);

        $this->get(route('news'))->assertOk()->assertDontSee('Clinic Hours Update');
        $this->get(route('news.show', $article->slug))->assertNotFound();

        $this->actingAs($admin)->get(route('admin.articles.preview', $article))->assertOk()->assertSee('Staff preview');

        $this->actingAs($admin)->post(route('admin.articles.publish', $article))->assertRedirect();

        auth()->logout();
        $this->get(route('news'))->assertOk()->assertSee('Clinic Hours Update');
        $page = $this->get(route('news.show', $article->slug))->assertOk();
        $page->assertDontSee('<script>alert(1)</script>', false);
        $page->assertSee('&lt;script&gt;', false);

        $this->actingAs($admin)->post(route('admin.articles.unpublish', $article))->assertRedirect();
        $this->get(route('news.show', $article->slug))->assertNotFound();
    }

    public function test_status_cannot_be_forged_on_create(): void
    {
        $admin = $this->user(UserRole::HOSPITAL_DIRECTOR);
        $this->actingAs($admin)->post(route('admin.articles.store'), $this->payload(['status' => 'published']));
        $this->assertSame('draft', Article::firstOrFail()->status);
    }

    public function test_non_publisher_roles_are_forbidden_everywhere(): void
    {
        $article = Article::create(['title' => 'T', 'slug' => 't', 'summary' => 's', 'body' => 'b', 'author_name' => 'a']);

        foreach ([UserRole::DOCTOR, UserRole::RECEPTIONIST, UserRole::PHARMACIST] as $role) {
            $u = $this->user($role);
            $this->actingAs($u)->get(route('admin.articles.index'))->assertForbidden();
            $this->actingAs($u)->post(route('admin.articles.store'), $this->payload())->assertForbidden();
            $this->actingAs($u)->post(route('admin.articles.publish', $article))->assertForbidden();
            $this->actingAs($u)->get(route('admin.articles.preview', $article))->assertForbidden();
        }
        $this->assertSame('draft', $article->fresh()->status);
    }

    public function test_slug_collisions_get_suffixes(): void
    {
        $admin = $this->user(UserRole::SUPER_ADMIN);
        $this->actingAs($admin)->post(route('admin.articles.store'), $this->payload());
        $this->actingAs($admin)->post(route('admin.articles.store'), $this->payload());

        $slugs = Article::orderBy('id')->pluck('slug')->all();
        $this->assertSame(['clinic-hours-update', 'clinic-hours-update-2'], $slugs);
    }

    public function test_cover_upload_validation_and_storage(): void
    {
        Storage::fake('public');
        $admin = $this->user(UserRole::SUPER_ADMIN);

        $bad = UploadedFile::fake()->create('evil.php', 10, 'application/x-php');
        $this->actingAs($admin)->post(route('admin.articles.store'), $this->payload(['cover_image' => $bad]))
            ->assertSessionHasErrors('cover_image');
        $this->assertSame(0, Article::count());

        $big = UploadedFile::fake()->image('big.jpg')->size(4000);
        $this->actingAs($admin)->post(route('admin.articles.store'), $this->payload(['cover_image' => $big]))
            ->assertSessionHasErrors('cover_image');

        $ok = UploadedFile::fake()->image('cover.png', 600, 400);
        $this->actingAs($admin)->post(route('admin.articles.store'), $this->payload(['cover_image' => $ok]))->assertRedirect();
        $article = Article::firstOrFail();
        Storage::disk('public')->assertExists($article->cover_image_path);
    }
}
