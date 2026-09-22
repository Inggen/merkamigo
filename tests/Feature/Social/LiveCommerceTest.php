<?php

namespace Tests\Feature\Social;

use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Marketplace\Models\Order;
use App\Domain\Social\Actions\CalculateLiveControlMetrics;
use App\Domain\Social\Actions\ManageLiveIntroVideo;
use App\Domain\Social\Actions\ManageLivePoll;
use App\Domain\Social\Actions\ManageLiveStream;
use App\Domain\Social\Models\LiveStream;
use App\Domain\Social\Notifications\LiveStarted;
use App\Domain\Storefronts\Actions\CreateStorefront;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class LiveCommerceTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_prepare_start_pin_and_finish_a_purchasable_live(): void
    {
        $business = $this->publishedBusiness();
        $owner = $business->organization->owner;
        $products = $business->products()->get();

        $stream = app(ManageLiveStream::class)->create($business, [
            'title' => 'Lanzamiento de temporada',
            'product_ids' => $products->pluck('id')->all(),
        ], $owner);

        $this->assertSame(LiveStream::BORRADOR, $stream->status);
        $this->assertSame('merkamigo', $stream->stream_origin);
        $this->assertStringStartsWith('live/', $stream->stream_path);
        $this->assertNotEmpty($stream->stream_key);
        $this->assertCount(2, $stream->products);

        $stream = app(ManageLiveStream::class)->start($stream, $owner);
        $this->assertTrue($stream->isLive());
        $this->assertNull($stream->pinned_product_id);

        $stream = app(ManageLiveStream::class)->pinProduct($stream, $products->last()->id, $owner);
        $this->assertSame($products->last()->id, $stream->pinned_product_id);

        $stream = app(ManageLiveStream::class)->end($stream, 'https://www.youtube.com/watch?v=lmnopqrstuv', $owner);
        $this->assertTrue($stream->isReplayAvailable());
        $this->assertNotNull($stream->ended_at);
    }

    public function test_live_products_must_belong_to_the_business(): void
    {
        $business = $this->publishedBusiness('Principal');
        $otherProduct = $this->publishedBusiness('Otro')->products()->firstOrFail();

        $this->expectException(ValidationException::class);

        app(ManageLiveStream::class)->create($business, [
            'title' => 'Live inválido',
            'product_ids' => [$otherProduct->id],
        ], $business->organization->owner);
    }

    public function test_media_server_allows_publish_with_the_private_stream_key(): void
    {
        $business = $this->publishedBusiness();
        $stream = $this->draftLive($business);

        $this->postJson(route('streaming.auth'), [
            'action' => 'publish',
            'path' => $stream->stream_path,
            'user' => 'merkamigo',
            'password' => $stream->stream_key,
        ])->assertNoContent();
    }

    public function test_media_server_rejects_an_invalid_or_public_read_of_a_draft(): void
    {
        $business = $this->publishedBusiness();
        $stream = $this->draftLive($business);

        $this->postJson(route('streaming.auth'), [
            'action' => 'publish',
            'path' => $stream->stream_path,
            'user' => 'merkamigo',
            'password' => 'clave-incorrecta',
        ])->assertForbidden();

        $this->postJson(route('streaming.auth'), [
            'action' => 'read',
            'path' => $stream->stream_path,
        ])->assertForbidden();
    }

    public function test_starting_a_live_notifies_followers(): void
    {
        Notification::fake();
        $business = $this->publishedBusiness();
        $follower = User::factory()->create();
        $business->follows()->create(['user_id' => $follower->id]);
        $stream = $this->draftLive($business);

        app(ManageLiveStream::class)->start($stream, $business->organization->owner);

        Notification::assertSentTo($follower, LiveStarted::class);
    }

    public function test_live_and_replay_are_public_and_keep_the_product_available(): void
    {
        $business = $this->publishedBusiness();
        $stream = app(ManageLiveStream::class)->start($this->draftLive($business), $business->organization->owner);
        $stream = app(ManageLiveStream::class)->pinProduct($stream, $business->products->first()->id, $business->organization->owner);

        $this->get(route('live.show', $stream))
            ->assertOk()
            ->assertSee('Venta en vivo')
            ->assertSee($stream->pinnedProduct->name)
            ->assertSee(__('EN VIVO'));

        $stream = app(ManageLiveStream::class)->end($stream, 'https://www.youtube.com/watch?v=lmnopqrstuv', $business->organization->owner);

        $this->get(route('live.show', $stream))
            ->assertOk()
            ->assertSee(__('REPLAY'))
            ->assertSee($stream->pinnedProduct->name);
    }

    public function test_authenticated_viewer_can_chat_and_react_during_a_live(): void
    {
        $business = $this->publishedBusiness();
        $stream = app(ManageLiveStream::class)->start($this->draftLive($business), $business->organization->owner);
        $stream = app(ManageLiveStream::class)->pinProduct($stream, $business->products->first()->id, $business->organization->owner);
        $viewer = User::factory()->create();
        $this->actingAs($viewer);

        Livewire::test('pages::live.show', ['liveStream' => $stream])
            ->set('message', 'Quiero conocer más del producto')
            ->call('sendMessage')
            ->assertHasNoErrors()
            ->call('toggleReaction')
            ->assertSet('reacted', true)
            ->call('sendReaction', 'laugh')
            ->assertDispatched('live-reaction', emoji: '😂')
            ->call('sendReaction', 'laugh')
            ->assertDispatched('live-reaction', emoji: '😂')
            ->call('sendReaction', 'thanks')
            ->assertDispatched('live-reaction', emoji: '🙏')
            ->call('openProduct', $stream->products->first()->id)
            ->call('addToCart', true)
            ->assertSee(__('Pagar con Wompi'))
            ->assertDontSee(__('Consultar por WhatsApp'));

        $this->assertDatabaseHas('live_stream_messages', [
            'live_stream_id' => $stream->id,
            'user_id' => $viewer->id,
        ]);
        $this->assertDatabaseHas('live_stream_reactions', [
            'live_stream_id' => $stream->id,
            'user_id' => $viewer->id,
            'type' => 'heart',
        ]);
        $this->assertDatabaseHas('live_stream_reactions', [
            'live_stream_id' => $stream->id,
            'user_id' => $viewer->id,
            'type' => 'laugh',
        ]);
        $this->assertSame(2, $stream->reactions()->where('user_id', $viewer->id)->where('type', 'laugh')->count());
        $this->assertDatabaseHas('live_stream_reactions', [
            'live_stream_id' => $stream->id,
            'user_id' => $viewer->id,
            'type' => 'thanks',
        ]);
    }

    public function test_seller_sees_new_audience_reactions_in_the_studio(): void
    {
        $business = $this->publishedBusiness();
        $owner = $business->organization->owner;
        $stream = app(ManageLiveStream::class)->start($this->draftLive($business), $owner);

        $studio = Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.live-studio', ['business' => $business->id, 'liveStream' => $stream])
            ->assertSee(__('Enviar una reacción'))
            ->assertSee('🙏');

        $stream->reactions()->create([
            'visitor_hash' => hash('sha256', 'studio-reaction-viewer'),
            'type' => 'laugh',
        ]);

        $studio->call('refreshStudio')
            ->assertHasNoErrors()
            ->assertDispatched('studio-reaction', emoji: '😂')
            ->call('sendReaction', 'celebrate')
            ->assertHasNoErrors()
            ->assertDispatched('studio-reaction', emoji: '🎉');

        $this->assertDatabaseHas('live_stream_reactions', [
            'live_stream_id' => $stream->id,
            'user_id' => $owner->id,
            'type' => 'celebrate',
        ]);
    }

    public function test_viewer_receives_reactions_sent_by_other_participants(): void
    {
        $business = $this->publishedBusiness();
        $stream = app(ManageLiveStream::class)->start($this->draftLive($business), $business->organization->owner);
        $viewer = Livewire::actingAs(User::factory()->create())
            ->test('pages::live.show', ['liveStream' => $stream]);

        $stream->reactions()->create([
            'visitor_hash' => hash('sha256', 'another-live-viewer'),
            'type' => 'wow',
        ]);

        $viewer->call('heartbeat')
            ->assertHasNoErrors()
            ->assertDispatched('live-reaction', emoji: '😮');
    }

    public function test_live_hls_is_served_through_the_merkamigo_https_domain(): void
    {
        config(['services.live_streaming.proxy_hls' => true]);

        Http::fake([
            'http://127.0.0.1:8888/*' => Http::response('#EXTM3U', 200, [
                'Content-Type' => 'application/vnd.apple.mpegurl',
            ]),
        ]);

        $business = $this->publishedBusiness();
        $stream = app(ManageLiveStream::class)->start($this->draftLive($business), $business->organization->owner);
        $stream = app(ManageLiveStream::class)->pinProduct($stream, $business->products->first()->id, $business->organization->owner);

        $this->get(route('streaming.hls', ['liveStream' => $stream, 'asset' => 'index.m3u8']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.apple.mpegurl')
            ->assertSee('#EXTM3U');

        $this->assertStringStartsWith(
            config('app.url').'/streaming/live/',
            $stream->hlsUrl(),
        );
    }

    public function test_home_feed_previews_the_active_live_signal(): void
    {
        $business = $this->publishedBusiness();
        $stream = app(ManageLiveStream::class)->start($this->draftLive($business), $business->organization->owner);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-live-feed-preview', false)
            ->assertSee(route('streaming.whep', $stream), false)
            ->assertSee('x-ref="video"', false)
            ->assertSee('wire:ignore', false)
            ->assertDontSee('brightness(1.08) saturate(1.12) sepia(0.08)', false)
            ->assertDontSee('transform: scaleX(-1)', false)
            ->assertSee(__('Conectando vista previa…'));
    }

    public function test_live_control_metrics_only_include_paid_orders_from_that_live(): void
    {
        $business = $this->publishedBusiness();
        $stream = app(ManageLiveStream::class)->start($this->draftLive($business), $business->organization->owner);
        $viewer = User::factory()->create();

        $stream->views()->create([
            'user_id' => $viewer->id,
            'visitor_hash' => hash('sha256', 'viewer'),
            'last_seen_at' => now(),
        ]);
        $stream->messages()->create(['user_id' => $viewer->id, 'body' => 'Me interesa']);

        $paid = $stream->orders()->create([
            'business_id' => $business->id,
            'product_id' => $business->products->first()->id,
            'buyer_user_id' => $viewer->id,
            'quantity' => 2,
            'unit_price_cents' => 2500000,
            'amount_cents' => 5000000,
            'currency' => 'COP',
            'commission_cents' => 0,
            'reference' => 'LIVE-PAID',
            'status' => Order::PAGADO,
        ]);
        $paid->items()->create([
            'product_id' => $business->products->first()->id,
            'quantity' => 2,
            'unit_price_cents' => 2500000,
            'amount_cents' => 5000000,
        ]);
        $stream->orders()->create([
            'business_id' => $business->id,
            'product_id' => $business->products->last()->id,
            'buyer_user_id' => $viewer->id,
            'quantity' => 1,
            'unit_price_cents' => 2500000,
            'amount_cents' => 2500000,
            'currency' => 'COP',
            'commission_cents' => 0,
            'reference' => 'LIVE-PENDING',
            'status' => Order::PENDIENTE,
        ]);

        $metrics = app(CalculateLiveControlMetrics::class)->handle($stream);

        $this->assertSame(1, $metrics['unique_viewers']);
        $this->assertSame(1, $metrics['comments']);
        $this->assertSame(1, $metrics['orders']);
        $this->assertSame(2, $metrics['products_sold']);
        $this->assertSame(5000000, $metrics['sales_cents']);

        Livewire::actingAs($business->organization->owner)
            ->test('pages::emprendedores.negocios.lives', ['business' => $business->id])
            ->assertSee(__('Ventas del Live'))
            ->assertSee('$50.000')
            ->assertSee('Me interesa');
    }

    public function test_live_page_uses_the_device_native_share_sheet(): void
    {
        $business = $this->publishedBusiness();
        $stream = app(ManageLiveStream::class)->start($this->draftLive($business), $business->organization->owner);

        $this->get(route('live.show', $stream))
            ->assertOk()
            ->assertSee('navigator.share', false)
            ->assertSee('shareLive()', false)
            ->assertDontSee('share-live', false)
            ->assertDontSee(__('Compartir Live'));
    }

    public function test_scheduled_live_uses_its_cover_on_the_page_and_social_metadata(): void
    {
        Storage::fake('public');
        $business = $this->publishedBusiness();
        $scheduledAt = now()->addDay()->format('Y-m-d\TH:i');

        Livewire::actingAs($business->organization->owner)
            ->test('pages::emprendedores.negocios.lives', ['business' => $business->id])
            ->set('title', 'Live programado con portada')
            ->set('description', 'Una transmisión para compartir en redes.')
            ->set('scheduled_at', $scheduledAt)
            ->set('cover', UploadedFile::fake()->image('portada-live.jpg', 1200, 630))
            ->set('product_ids', $business->products->pluck('id')->all())
            ->call('create')
            ->assertHasNoErrors();

        $stream = $business->liveStreams()->latest()->firstOrFail();
        $coverUrl = $stream->coverUrl();

        Storage::disk('public')->assertExists($stream->cover_path);

        $this->get(route('live.show', $stream))
            ->assertOk()
            ->assertSee(__('Live programado'))
            ->assertSee($coverUrl, false)
            ->assertSee('<meta property="og:image" content="'.$coverUrl.'">', false)
            ->assertSee('<meta name="twitter:image" content="'.$coverUrl.'">', false)
            ->assertSee('<link rel="canonical" href="'.route('live.show', $stream).'">', false);
    }

    public function test_cover_is_available_without_scheduling_the_live(): void
    {
        Storage::fake('public');
        $business = $this->publishedBusiness();

        Livewire::actingAs($business->organization->owner)
            ->test('pages::emprendedores.negocios.lives', ['business' => $business->id])
            ->assertSee(__('Imagen de portada (opcional)'))
            ->set('title', 'Live inmediato con portada')
            ->set('cover', UploadedFile::fake()->image('portada-inmediata.webp', 1200, 630))
            ->set('product_ids', $business->products->pluck('id')->all())
            ->call('create')
            ->assertHasNoErrors();

        $stream = $business->liveStreams()->latest()->firstOrFail();
        $this->assertNull($stream->scheduled_at);
        Storage::disk('public')->assertExists($stream->cover_path);
    }

    public function test_live_player_exposes_the_current_connection_status(): void
    {
        $business = $this->publishedBusiness();
        $stream = app(ManageLiveStream::class)->start($this->draftLive($business), $business->organization->owner);
        $stream->update(['signal_status' => 'reconnecting']);

        $this->get(route('live.show', $stream))
            ->assertOk()
            ->assertSee(__('RECONECTANDO'));
    }

    public function test_mobile_buyer_has_immersive_live_controls(): void
    {
        $business = $this->publishedBusiness();
        $stream = app(ManageLiveStream::class)->start($this->draftLive($business), $business->organization->owner);

        $this->get(route('live.show', $stream))
            ->assertOk()
            ->assertSee(__('Los productos aparecerán aquí cuando el anfitrión los presente.'))
            ->assertDontSee($business->products->first()->name);

        $stream = app(ManageLiveStream::class)->pinProduct($stream, $business->products->first()->id, $business->organization->owner);

        $this->get(route('live.show', $stream))
            ->assertOk()
            ->assertSee(__('Activar sonido'))
            ->assertSee(__('Ver productos'))
            ->assertSee(__('Productos presentados'))
            ->assertSee(__('Ocultar chat'))
            ->assertSee(__('Ver carrito'))
            ->assertSee(__('Ver artículo'));
    }

    public function test_destination_guide_changes_with_the_selected_social_network(): void
    {
        $business = $this->publishedBusiness();
        $stream = $this->draftLive($business);
        $stream->streamingDestinations()->create([
            'provider' => 'facebook',
            'name' => 'Facebook de mi negocio',
            'rtmp_url' => 'rtmps://live-api-s.facebook.com:443/rtmp/',
            'stream_key' => 'private-test-key',
            'is_enabled' => true,
        ]);

        Livewire::actingAs($business->organization->owner)
            ->test('pages::emprendedores.negocios.lives', ['business' => $business->id])
            ->assertSee('data-provider-icon="facebook"', false)
            ->assertSee(__('Habilitado · Desconectado'))
            ->assertDontSee('private-test-key')
            ->assertSee(__('Transmitir también en YouTube'))
            ->set('destination_provider', 'instagram')
            ->assertSee(__('Transmitir también en Instagram'))
            ->assertSee(__('Instagram debe mostrar la opción para transmitir con software externo en tu cuenta.'))
            ->assertSee(__('Ej. Instagram de mi negocio'))
            ->set('destination_provider', 'tiktok')
            ->assertSee(__('Transmitir también en TikTok'))
            ->assertSee(__('Necesitas acceso a TikTok LIVE y a la transmisión mediante software externo; la disponibilidad depende de la cuenta y la región.'));
    }

    public function test_owner_sees_a_branded_studio_without_stream_credentials(): void
    {
        $business = $this->publishedBusiness();
        $stream = $this->draftLive($business);

        $this->actingAs($business->organization->owner)
            ->get(route('emprendedores.negocios.lives.studio', [$business, $stream]))
            ->assertOk()
            ->assertSee(__('Estudio Live'))
            ->assertSee(__('Activar cámara y micrófono'))
            ->assertSee(__('Iniciar transmisión'))
            ->assertSee(__('Nada se muestra hasta que pulses Mostrar.'))
            ->assertSee(__('Chat'))
            ->assertSee(__('Enviar mensaje'))
            ->assertSee(__('Enviar una reacción'))
            ->assertSee('live-chat-surface', false)
            ->assertSee(__('Encuesta'))
            ->assertSee(__('Configuración'))
            ->assertSee(__('Invertir cámara'))
            ->assertSee('x-model="cameraMirrored"', false)
            ->assertSee('scaleX(-1)', false)
            ->assertSee(__('Copiar enlace'))
            ->assertSee(__('Compartir'))
            ->assertSee(__('Abrir Live'))
            ->assertSee(__('Detener'))
            ->assertSee(__('Video de inicio'))
            ->assertSee('studio-product-overlay', false)
            ->assertSee('studio-poll-overlay', false)
            ->assertSee('wire:ignore.self', false)
            ->assertSee(route('live.show', $stream), false)
            ->assertDontSee($stream->stream_key)
            ->assertDontSee('127.0.0.1:8889');

        $this->assertStringContainsString('cameraMirrored: true', file_get_contents(resource_path('js/live-studio.js')));
        $this->assertStringContainsString('drawBroadcastOverlays(context)', file_get_contents(resource_path('js/live-studio.js')));
        $this->assertStringContainsString('drawProductOverlay(context)', file_get_contents(resource_path('js/live-studio.js')));
        $this->assertStringContainsString('drawPollOverlay(context)', file_get_contents(resource_path('js/live-studio.js')));
        $this->assertStringContainsString('drawReactionOverlays(context)', file_get_contents(resource_path('js/live-studio.js')));
        $this->assertStringContainsString('this.drawMediaFrame(context, source, false, this.cameraMirrored)', file_get_contents(resource_path('js/live-studio.js')));
        $this->assertStringContainsString('context.scale(-1, 1)', file_get_contents(resource_path('js/live-studio.js')));
    }

    public function test_seller_controls_the_featured_product_and_chat_from_the_studio(): void
    {
        $business = $this->publishedBusiness();
        $owner = $business->organization->owner;
        $stream = app(ManageLiveStream::class)->start($this->draftLive($business), $owner);
        $product = $business->products->firstOrFail();
        $viewer = User::factory()->create();
        $stream->messages()->create(['user_id' => $viewer->id, 'body' => 'Quiero verlo de cerca']);

        Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.live-studio', ['business' => $business->id, 'liveStream' => $stream])
            ->assertSee('Quiero verlo de cerca')
            ->call('showProduct', $product->id)
            ->assertHasNoErrors()
            ->assertDispatched('studio-product-overlay')
            ->assertSee(__('Visible'))
            ->set('message', 'Claro, ahora te lo mostramos')
            ->call('sendMessage')
            ->assertHasNoErrors()
            ->assertSee('Claro, ahora te lo mostramos')
            ->call('hideProduct')
            ->assertHasNoErrors()
            ->assertDispatched('studio-product-overlay');

        $this->assertNull($stream->fresh()->pinned_product_id);
        $this->assertDatabaseHas('live_stream_messages', [
            'live_stream_id' => $stream->id,
            'user_id' => $owner->id,
            'body' => 'Claro, ahora te lo mostramos',
        ]);
    }

    public function test_seller_shares_a_poll_and_viewers_vote_inside_the_live(): void
    {
        $business = $this->publishedBusiness();
        $owner = $business->organization->owner;
        $stream = app(ManageLiveStream::class)->start($this->draftLive($business), $owner);

        $studio = Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.live-studio', ['business' => $business->id, 'liveStream' => $stream])
            ->set('pollQuestion', '¿Cuál producto mostramos ahora?')
            ->set('pollOptions', ['Producto rojo', 'Producto azul'])
            ->call('savePoll')
            ->assertHasNoErrors()
            ->assertSee('¿Cuál producto mostramos ahora?')
            ->assertSee(__('Oculta'))
            ->assertSee(__('Mostrar en la transmisión'));

        $poll = $stream->polls()->latest()->firstOrFail();
        $this->assertNotNull($poll->closed_at);

        $studio->call('showPoll', $poll->id)
            ->assertHasNoErrors()
            ->assertDispatched('studio-poll-overlay')
            ->assertSee(__('Visible'))
            ->assertSee(__('Ocultar de la transmisión'));

        $this->assertNull($poll->fresh()->closed_at);

        Livewire::test('pages::live.show', ['liveStream' => $stream])
            ->assertSee('¿Cuál producto mostramos ahora?')
            ->assertSee('Producto rojo')
            ->call('voteOnPoll', 0)
            ->assertHasNoErrors()
            ->assertSet('pollVote', 0)
            ->assertSee(__('Tu voto fue registrado. Puedes cambiarlo mientras la encuesta esté activa.'));

        $this->assertDatabaseHas('live_stream_poll_votes', [
            'live_stream_poll_id' => $poll->id,
            'option_index' => 0,
        ]);

        $studio->call('hidePoll', $poll->id)
            ->assertHasNoErrors()
            ->assertDispatched('studio-poll-overlay');

        $this->assertNotNull($poll->fresh()->closed_at);
    }

    public function test_live_keeps_up_to_five_polls_and_only_one_can_be_visible(): void
    {
        $business = $this->publishedBusiness();
        $owner = $business->organization->owner;
        $stream = $this->draftLive($business);
        $polls = collect();

        foreach (range(1, 5) as $number) {
            $polls->push(app(ManageLivePoll::class)->save(
                $stream,
                "Encuesta {$number}",
                ['Opción A', 'Opción B'],
                $owner,
            ));
        }

        $this->assertCount(5, $stream->polls);

        try {
            app(ManageLivePoll::class)->save($stream, 'Encuesta 6', ['Opción A', 'Opción B'], $owner);
            $this->fail('Se permitió guardar más de cinco encuestas.');
        } catch (ValidationException $exception) {
            $this->assertSame(__('Puedes guardar máximo 5 encuestas por transmisión.'), $exception->errors()['question'][0]);
        }

        app(ManageLiveStream::class)->start($stream, $owner);
        app(ManageLivePoll::class)->show($polls[0]);
        app(ManageLivePoll::class)->show($polls[1]);

        $this->assertNotNull($polls[0]->fresh()->closed_at);
        $this->assertNull($polls[1]->fresh()->closed_at);
        $this->assertSame(1, $stream->polls()->whereNull('closed_at')->count());
    }

    public function test_seller_can_activate_and_hide_an_intro_video(): void
    {
        Storage::fake('public');
        $business = $this->publishedBusiness();
        $owner = $business->organization->owner;
        $stream = $this->draftLive($business);

        $stream = app(ManageLiveIntroVideo::class)->store(
            $stream,
            UploadedFile::fake()->create('inicio.mp4', 1024, 'video/mp4'),
            $owner,
        );

        $this->assertTrue($stream->intro_video_active);
        Storage::disk('public')->assertExists($stream->intro_video_path);

        Livewire::actingAs($owner)
            ->test('pages::emprendedores.negocios.live-studio', ['business' => $business->id, 'liveStream' => $stream])
            ->assertSee(__('Volver a cámara'))
            ->assertSee('x-on:live-intro-toggled.window="setIntroVideoActive($event.detail.active)"', false)
            ->assertSee('x-ref="introSource"', false)
            ->call('toggleIntroVideo')
            ->assertHasNoErrors()
            ->assertDispatched('live-intro-toggled')
            ->assertSee(__('Mostrar video inicial'));

        $this->assertFalse($stream->fresh()->intro_video_active);
        $this->assertStringContainsString('async setIntroVideoActive(active)', file_get_contents(resource_path('js/live-studio.js')));
        $this->assertStringContainsString('this.drawMediaFrame(context, intro, true)', file_get_contents(resource_path('js/live-studio.js')));
        $this->assertStringContainsString('captureIntroStream(source)', file_get_contents(resource_path('js/live-studio.js')));
    }

    public function test_whip_proxy_authenticates_server_side_and_starts_the_live(): void
    {
        Http::fake(fn ($request) => $request->method() === 'DELETE'
            ? Http::response('', 204)
            : Http::response('answer-sdp', 201, [
                'Content-Type' => 'application/sdp',
                'Location' => '/live/session/123',
            ]));

        $business = $this->publishedBusiness();
        $stream = $this->draftLive($business);

        $response = $this->actingAs($business->organization->owner)
            ->call(
                'POST',
                route('emprendedores.negocios.lives.studio.publish', [$business, $stream]),
                server: ['CONTENT_TYPE' => 'application/sdp'],
                content: 'offer-sdp',
            )
            ->assertCreated()
            ->assertHeader('Content-Type', 'application/sdp')
            ->assertHeader('X-Whip-Session')
            ->assertSee('answer-sdp');

        $this->assertTrue($stream->fresh()->isLive());
        Http::assertSent(fn ($request) => $request->url() === 'http://127.0.0.1:8889/'.$stream->stream_path.'/whip'
            && $request->hasHeader('Authorization', 'Basic '.base64_encode('merkamigo:'.$stream->stream_key))
            && $request->body() === 'offer-sdp');

        $this->delete(route('emprendedores.negocios.lives.studio.destroy', [$business, $stream]), [], [
            'HTTP_X_WHIP_SESSION' => $response->headers->get('X-Whip-Session'),
        ])->assertNoContent();

        $this->assertSame(LiveStream::FINALIZADO, $stream->fresh()->status);
    }

    public function test_whep_proxy_serves_the_live_signal_without_transcoding(): void
    {
        Http::fake(fn ($request) => $request->method() === 'DELETE'
            ? Http::response('', 204)
            : Http::response('answer-sdp', 201, [
                'Content-Type' => 'application/sdp',
                'Location' => '/live/session/456',
            ]));

        $business = $this->publishedBusiness();
        $stream = app(ManageLiveStream::class)->start($this->draftLive($business), $business->organization->owner);

        // Público: cualquier visitante puede leer la señal del Live publicado.
        $response = $this
            ->call(
                'POST',
                route('streaming.whep', $stream),
                server: ['CONTENT_TYPE' => 'application/sdp'],
                content: 'offer-sdp',
            )
            ->assertCreated()
            ->assertHeader('Content-Type', 'application/sdp')
            ->assertHeader('X-Whep-Session')
            ->assertSee('answer-sdp');

        Http::assertSent(fn ($request) => $request->url() === 'http://127.0.0.1:8889/'.$stream->stream_path.'/whep'
            && $request->body() === 'offer-sdp');

        $this->delete(route('streaming.whep.destroy', $stream), [], [
            'HTTP_X_WHEP_SESSION' => $response->headers->get('X-Whep-Session'),
        ])->assertNoContent();

        // Un Live en borrador no expone señal.
        $draft = $this->draftLive($business);
        $this->post(route('streaming.whep', $draft), [], ['CONTENT_TYPE' => 'application/sdp'], 'offer-sdp')
            ->assertNotFound();
    }

    private function draftLive(Business $business): LiveStream
    {
        return app(ManageLiveStream::class)->create($business, [
            'title' => 'Venta en vivo',
            'description' => 'Conoce nuestras novedades.',
            'product_ids' => $business->products()->pluck('id')->all(),
        ], $business->organization->owner);
    }

    private function publishedBusiness(string $name = 'Negocio Live'): Business
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
        $business = app(CreateStorefront::class)->handle($owner, [
            'name' => "{$name} {$counter}",
            'whatsapp_number' => '+573001112233',
            'municipality_id' => $municipality->id,
            'category_id' => $category->id,
        ])->business;
        $business->update(['status' => 'publicado']);

        foreach (range(1, 2) as $productNumber) {
            $business->products()->create([
                'name' => "Producto Live {$counter}-{$productNumber}",
                'slug' => "producto-live-{$counter}-{$productNumber}",
                'type' => 'producto',
                'price' => 25000,
                'price_type' => 'exacto',
                'status' => 'publicado',
            ]);
        }

        return $business->fresh(['organization.owner', 'products']);
    }
}
