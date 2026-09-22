<?php

namespace Tests\Feature\Social;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Social\Actions\CreatePost;
use App\Domain\Social\Actions\CreatePostComment;
use App\Domain\Social\Actions\ToggleFollowBusiness;
use App\Domain\Social\Actions\TogglePostReaction;
use App\Domain\Social\Notifications\NewFollower;
use App\Domain\Social\Notifications\PostCommented;
use App\Domain\Social\Notifications\PostReacted;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Sprint 3 de TODO_social.md (Fase 11): notificaciones sociales
 * (nuevo seguidor, reacción, comentario).
 */
class SocialNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_business_owner_is_notified_of_a_new_follower(): void
    {
        Notification::fake();

        $business = $this->publishedBusiness();
        $owner = $business->organization->owner;
        $follower = User::factory()->create();

        app(ToggleFollowBusiness::class)->handle($follower, $business);

        Notification::assertSentTo($owner, NewFollower::class);
    }

    public function test_unfollowing_does_not_send_a_notification(): void
    {
        $business = $this->publishedBusiness();
        $follower = User::factory()->create();
        app(ToggleFollowBusiness::class)->handle($follower, $business);

        Notification::fake();

        app(ToggleFollowBusiness::class)->handle($follower, $business);

        Notification::assertNothingSent();
    }

    public function test_the_post_author_is_notified_of_a_reaction_but_not_for_their_own(): void
    {
        Notification::fake();

        $business = $this->publishedBusiness();
        $owner = $business->organization->owner;
        $post = app(CreatePost::class)->handle($business, ['type' => 'texto', 'body' => 'Hola'], [], $owner);
        $visitor = User::factory()->create();

        app(TogglePostReaction::class)->handle($visitor, $post);
        Notification::assertSentTo($owner, PostReacted::class);

        Notification::fake();
        app(TogglePostReaction::class)->handle($owner, $post->fresh());
        Notification::assertNothingSentTo($owner);
    }

    public function test_the_post_author_is_notified_of_a_comment_but_not_for_their_own(): void
    {
        Notification::fake();

        $business = $this->publishedBusiness();
        $owner = $business->organization->owner;
        $post = app(CreatePost::class)->handle($business, ['type' => 'texto', 'body' => 'Hola'], [], $owner);
        $visitor = User::factory()->create();

        app(CreatePostComment::class)->handle($post, ['body' => '¡Qué rico!'], $visitor);
        Notification::assertSentTo($owner, PostCommented::class);

        Notification::fake();
        app(CreatePostComment::class)->handle($post->fresh(), ['body' => 'Gracias a todos'], $owner);
        Notification::assertNothingSentTo($owner);
    }

    private function publishedBusiness(): Business
    {
        static $counter = 0;
        $counter++;

        $municipality = Municipality::firstOrCreate(
            ['slug' => 'cajica'],
            ['name' => 'Cajicá', 'department' => 'Cundinamarca', 'is_active' => true],
        );
        $category = Category::firstOrCreate(
            ['slug' => 'alimentos'],
            ['name' => 'Alimentos', 'is_active' => true],
        );

        $owner = User::factory()->create();
        $storefront = app(CreateStorefront::class)->handle($owner, [
            'name' => "Negocio de prueba {$counter}",
            'whatsapp_number' => '+573001112233',
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
        ]);
        $business = $storefront->business;
        $business->update(['status' => 'publicado']);

        return $business->fresh(['organization.owner', 'municipality']);
    }
}
