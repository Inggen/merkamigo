<?php

namespace App\Http\Controllers;

use App\Domain\Businesses\Models\Business;
use App\Domain\Messaging\Actions\StartBusinessConversation;
use App\Domain\Social\Models\ContentPromotion;
use App\Domain\Social\Models\Post;
use App\Domain\Storefronts\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactBusinessController extends Controller
{
    public function __invoke(Business $business, Request $request, StartBusinessConversation $startConversation): RedirectResponse
    {
        abort_unless($business->isPublished(), 404);

        $context = $this->resolveContext($business, $request);
        $channel = $business->contact_channel ?: 'merkamigo';

        if ($channel === 'whatsapp' && filled($business->whatsapp_number)) {
            return isset($context['product'])
                ? redirect()->route('vitrinas.whatsapp.product', [$business, $context['product']])
                : redirect()->route('vitrinas.whatsapp', $business);
        }

        if ($channel === 'phone' && filled($business->whatsapp_number)) {
            return redirect()->away('tel:'.preg_replace('/[^0-9+]/', '', $business->whatsapp_number));
        }

        $website = $business->social_links['website'] ?? null;
        if ($channel === 'external_link' && filter_var($website, FILTER_VALIDATE_URL)) {
            return redirect()->away($website);
        }

        return $this->openInternalConversation($business, $request, $startConversation, $context);
    }

    public function internal(Business $business, Request $request, StartBusinessConversation $startConversation): RedirectResponse
    {
        abort_unless($business->isPublished(), 404);

        return $this->openInternalConversation(
            $business,
            $request,
            $startConversation,
            $this->resolveContext($business, $request),
        );
    }

    /**
     * @param  array{key: string, type: ?string, id: ?int, label: ?string, url: ?string, product?: Product}  $context
     */
    private function openInternalConversation(
        Business $business,
        Request $request,
        StartBusinessConversation $startConversation,
        array $context,
    ): RedirectResponse {
        if (! Auth::check()) {
            return redirect()->guest(route('login'));
        }

        $conversation = $startConversation->handle($business, $request->user(), [
            'key' => $context['key'],
            'type' => $context['type'],
            'id' => $context['id'],
            'label' => $context['label'],
            'url' => $context['url'],
        ]);

        return redirect()->route('messages.show', $conversation);
    }

    /**
     * @return array{key: string, type: ?string, id: ?int, label: ?string, url: ?string, product?: Product}
     */
    private function resolveContext(Business $business, Request $request): array
    {
        $type = $request->string('context_type')->toString();
        $id = $request->integer('context_id');

        if ($type === 'product' && $id > 0) {
            $product = $business->products()->where('status', 'publicado')->findOrFail($id);
            $contextType = $product->type === 'servicio' ? 'service' : 'product';

            return [
                'key' => $contextType.':'.$product->id,
                'type' => $contextType,
                'id' => $product->id,
                'label' => $product->name,
                'url' => route('vitrinas.product', [$business, $product]),
                'product' => $product,
            ];
        }

        if ($type === 'post' && $id > 0) {
            $post = Post::query()->whereBelongsTo($business)->where('status', 'publicado')->findOrFail($id);

            return [
                'key' => 'post:'.$post->id,
                'type' => 'post',
                'id' => $post->id,
                'label' => str($post->body)->limit(80)->toString() ?: __('Publicación'),
                'url' => route('home'),
            ];
        }

        if ($type === 'promotion' && $id > 0) {
            $promotion = ContentPromotion::query()->whereBelongsTo($business)->active()->findOrFail($id);

            return [
                'key' => 'promotion:'.$promotion->id,
                'type' => 'promotion',
                'id' => $promotion->id,
                'label' => __('Promoción de :business', ['business' => $business->name]),
                'url' => route('promotions.click', $promotion),
            ];
        }

        return [
            'key' => 'business',
            'type' => null,
            'id' => null,
            'label' => null,
            'url' => route('vitrinas.show', $business),
        ];
    }
}
