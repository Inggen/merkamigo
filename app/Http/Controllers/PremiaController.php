<?php

namespace App\Http\Controllers;

use App\Domain\Analytics\Actions\RegisterAnalyticsEvent;
use App\Domain\Analytics\Models\AnalyticsEvent;
use App\Domain\Businesses\Models\Business;
use App\Domain\Discovery\Models\Category;
use App\Domain\Discovery\Models\Municipality;
use App\Domain\Loyalty\Actions\ReserveLoyaltyRedemption;
use App\Domain\Loyalty\Exceptions\LoyaltyActionException;
use App\Domain\Loyalty\Models\LoyaltyReward;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Catálogo público de Merkapuntos (TODO_Merkapuntos.md, F2.2): navegable
 * sin registro, como el resto del descubrimiento de Merkamigo
 * (`PlazaController`). F2.3 (registro contextual) se resuelve con el
 * mecanismo estándar de Laravel: `redeem()` exige `auth`, así que un
 * invitado que pulsa "Canjear" cae al login/registro y vuelve exactamente
 * a esta misma acción al terminar — sin sesión ni redirect propios.
 */
class PremiaController extends Controller
{
    public function index(Request $request): View
    {
        $query = trim((string) $request->string('q'));
        $municipality = $request->filled('municipio')
            ? Municipality::where('slug', $request->string('municipio'))->where('is_active', true)->first()
            : null;
        $category = $request->filled('categoria')
            ? Category::where('slug', $request->string('categoria'))->where('is_active', true)->first()
            : null;

        // Agrupado con cuidado: cada `when()` debe quedar en su propio
        // paréntesis AND — un `orWhere` suelto al nivel de arriba habría
        // roto el filtro de `status`/negocio para la rama de búsqueda por
        // texto (un bug real que se detectó al revisar esta misma query).
        $rewards = LoyaltyReward::query()
            ->where('status', LoyaltyReward::PUBLICADO)
            ->whereHas('business', function (Builder $q) use ($municipality, $category) {
                /** @var Builder<Business> $q */
                $q->where('status', 'publicado')
                    ->whereHas('loyaltyEnrollment', fn (Builder $e) => $e->where('status', 'activa'))
                    ->when($municipality, fn (Builder $q) => $q->servesMunicipality($municipality->id))
                    ->when($category, fn (Builder $q) => $q->where('category_id', $category->id));
            })
            ->when($query !== '', function (Builder $q) use ($query) {
                $q->where(function (Builder $q) use ($query) {
                    $q->where('title', 'like', "%{$query}%")
                        ->orWhereHas('business', fn (Builder $b) => $b->where('name', 'like', "%{$query}%"));
                });
            })
            ->with(['business.municipality', 'business.category', 'product.media'])
            ->latest()
            ->paginate(12)
            ->withQueryString();

        // F5.4: impresión de premio, igual de anónima/agregada que el
        // resto de eventos medibles de la plataforma (deduplicada, sin
        // IP ni user-agent en crudo).
        foreach ($rewards as $reward) {
            app(RegisterAnalyticsEvent::class)->handle($reward->business, AnalyticsEvent::LOYALTY_REWARD_IMPRESSION, $reward, $request);
        }

        return view('premia.index', [
            'rewards' => $rewards,
            'municipalities' => Municipality::where('is_active', true)->orderBy('name')->get(),
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
            'selectedMunicipality' => $municipality,
            'selectedCategory' => $category,
            'query' => $query,
        ]);
    }

    public function show(LoyaltyReward $reward, Request $request): View|RedirectResponse
    {
        if ((string) $request->route()->originalParameter('reward') !== $reward->getRouteKey()) {
            return redirect()->route('premia.show', $reward, 301);
        }

        abort_unless($reward->status === LoyaltyReward::PUBLICADO, 404);

        $reward->load(['business.organization', 'business.municipality', 'business.category', 'product.media']);

        abort_unless($reward->business->isPublished(), 404);

        app(RegisterAnalyticsEvent::class)->handle($reward->business, AnalyticsEvent::LOYALTY_REWARD_DETAIL_VIEW, $reward, $request);

        // "Tus Merkapuntos": el saldo del cliente en ESTE negocio (F1.2:
        // "los puntos pertenecen al ámbito del negocio emisor"), para que
        // vea de una vez si ya le alcanza, sin tener que ir al dashboard.
        $account = $request->user()
            ? $reward->business->loyaltyAccounts()->where('user_id', $request->user()->id)->first()
            : null;

        // "También te puede interesar": otros premios publicados, primero
        // del mismo negocio y luego del resto — igual criterio que
        // "Más eventos" en `eventos/show.blade.php`.
        $related = LoyaltyReward::query()
            ->where('status', LoyaltyReward::PUBLICADO)
            ->where('id', '!=', $reward->id)
            ->whereHas('business', fn (Builder $q) => $q->where('status', 'publicado')
                ->whereHas('loyaltyEnrollment', fn (Builder $e) => $e->where('status', 'activa')))
            ->with(['business', 'product.media'])
            ->orderByRaw('(business_id = ?) desc', [$reward->business_id])
            ->latest()
            ->limit(8)
            ->get();

        return view('premia.show', [
            'reward' => $reward,
            'business' => $reward->business,
            'customerPoints' => $account?->availablePoints(),
            'related' => $related,
        ]);
    }

    /**
     * F2.5 desde el catálogo público: mismo `ReserveLoyaltyRedemption` que
     * usa la sección Merkapuntos — aquí solo se dispara y se vuelve a
     * `/merkapuntos`, donde ya existe toda la UI de código/cancelación.
     */
    public function redeem(Request $request, LoyaltyReward $reward): RedirectResponse
    {
        try {
            $result = app(ReserveLoyaltyRedemption::class)->handle($request->user(), $reward, (string) Str::uuid());
        } catch (LoyaltyActionException $e) {
            return redirect()->route('premia.show', $reward)->with('error', $e->getMessage());
        }

        app(RegisterAnalyticsEvent::class)->handle($reward->business, AnalyticsEvent::LOYALTY_REDEMPTION_RESERVED, $result['redemption'], $request);

        return redirect()->route('merkapuntos')->with('status', __('¡Listo! Presenta tu código en :business.', ['business' => $reward->business->name]));
    }
}
