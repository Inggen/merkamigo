<?php

namespace App\Domain\Loyalty\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Loyalty\Actions\Concerns\AuthorizesLoyaltyEmployees;
use App\Domain\Loyalty\Exceptions\LoyaltyActionException;
use App\Domain\Loyalty\Models\LoyaltyReward;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Models\User;
use App\Support\Media\MediaUploader;
use Illuminate\Http\UploadedFile;
use Throwable;

/**
 * Editor breve de premios (TODO_Merkapuntos.md, F3.5). Esta acción cubre
 * solo la base del modelo de datos — el cálculo de sugerencia con IA
 * (F4.1) y el editor avanzado (vigencia/restricciones/costo máximo
 * desplegable) quedan para la fase de UI.
 */
class CreateLoyaltyReward
{
    use AuthorizesLoyaltyEmployees;

    public function __construct(private readonly MediaUploader $mediaUploader) {}

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws LoyaltyActionException
     */
    public function handle(Business $business, User $actor, array $data): LoyaltyReward
    {
        $this->assertBusinessAdmin($business, $actor, 'No tienes permiso para crear premios en este negocio.');

        $enrollment = $business->loyaltyEnrollment;

        if (! $enrollment || ! $enrollment->isActive()) {
            throw new LoyaltyActionException('Este negocio no tiene Merkamigo Premia activo.');
        }

        // F3.5, aceptación: "si falta costo se pide sin inventarlo."
        if (! isset($data['full_cost_cents']) || (int) $data['full_cost_cents'] <= 0) {
            throw new LoyaltyActionException('Indica el costo completo real del premio para poder publicarlo.');
        }

        if (! isset($data['points_cost']) || (int) $data['points_cost'] <= 0) {
            throw new LoyaltyActionException('Indica cuántos Merkapuntos cuesta este premio.');
        }

        $productId = $this->productIdFor($business, $data['product_id'] ?? null);
        $image = $data['image'] ?? null;
        $imagePath = $image instanceof UploadedFile ? $this->storeImage($business, $image) : null;

        try {
            $reward = $business->loyaltyRewards()->create([
                'product_id' => $productId,
                'image_path' => $imagePath,
                'type' => $data['type'] ?? LoyaltyReward::PRODUCTO,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'points_cost' => $data['points_cost'],
                'full_cost_cents' => $data['full_cost_cents'],
                'stock_total' => $data['stock_total'] ?? null,
                'max_budget_cents' => $data['max_budget_cents'] ?? null,
                'valid_from' => $data['valid_from'] ?? null,
                'valid_until' => $data['valid_until'] ?? null,
                'status' => LoyaltyReward::BORRADOR,
                'terms' => $data['terms'] ?? null,
                'created_by_user_id' => $actor->id,
            ]);
        } catch (Throwable $exception) {
            $this->mediaUploader->delete($imagePath);

            throw $exception;
        }

        app(RecordAuditLog::class)->handle($actor, 'loyalty.reward.created', $reward, [
            'business_id' => $business->id,
        ]);

        return $reward;
    }

    /**
     * @param  array<string, mixed>  $data
     *
     * @throws LoyaltyActionException
     */
    public function update(LoyaltyReward $reward, User $actor, array $data): LoyaltyReward
    {
        $this->assertBusinessAdmin($reward->business, $actor, 'No tienes permiso para editar premios en este negocio.');

        if (! isset($data['full_cost_cents']) || (int) $data['full_cost_cents'] <= 0) {
            throw new LoyaltyActionException('Indica el costo completo real del premio.');
        }

        if (! isset($data['points_cost']) || (int) $data['points_cost'] <= 0) {
            throw new LoyaltyActionException('Indica cuántos Merkapuntos cuesta este premio.');
        }

        $productId = $this->productIdFor($reward->business, $data['product_id'] ?? null);
        $image = $data['image'] ?? null;
        $previousImage = $reward->image_path;

        $attributes = [
            'product_id' => $productId,
            'type' => $data['type'] ?? $reward->type,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'points_cost' => $data['points_cost'],
            'full_cost_cents' => $data['full_cost_cents'],
            'stock_total' => $data['stock_total'] ?? null,
            'max_budget_cents' => $data['max_budget_cents'] ?? $reward->max_budget_cents,
            'valid_from' => $data['valid_from'] ?? $reward->valid_from,
            'valid_until' => $data['valid_until'] ?? null,
            'terms' => $data['terms'] ?? $reward->terms,
            'version' => $reward->version + 1,
        ];

        $newImagePath = null;

        if ($image instanceof UploadedFile) {
            $newImagePath = $this->storeImage($reward->business, $image);
            $attributes['image_path'] = $newImagePath;
        }

        try {
            $reward->update($attributes);
        } catch (Throwable $exception) {
            $this->mediaUploader->delete($newImagePath);

            throw $exception;
        }

        if ($image instanceof UploadedFile) {
            $this->mediaUploader->delete($previousImage);
        }

        app(RecordAuditLog::class)->handle($actor, 'loyalty.reward.updated', $reward, [
            'business_id' => $reward->business_id,
        ]);

        return $reward->fresh(['product.media']);
    }

    /**
     * @throws LoyaltyActionException
     */
    public function publish(LoyaltyReward $reward, User $actor): LoyaltyReward
    {
        $this->assertBusinessAdmin($reward->business, $actor, 'No tienes permiso para publicar premios en este negocio.');

        $reward->update(['status' => LoyaltyReward::PUBLICADO]);

        app(RecordAuditLog::class)->handle($actor, 'loyalty.reward.published', $reward, [
            'business_id' => $reward->business_id,
        ]);

        return $reward->fresh();
    }

    /**
     * F3.5, aceptación: "pausar/reactivar sin perder historial" — un
     * premio pausado no es canjeable (`isRedeemable()` exige `publicado`)
     * pero conserva stock/presupuesto consumido y todo su historial de
     * canjes; reactivarlo solo cambia el estado de vuelta.
     *
     * @throws LoyaltyActionException
     */
    public function pause(LoyaltyReward $reward, User $actor): LoyaltyReward
    {
        $this->assertBusinessAdmin($reward->business, $actor, 'No tienes permiso para pausar premios en este negocio.');

        if ($reward->status !== LoyaltyReward::PUBLICADO) {
            throw new LoyaltyActionException('Solo se puede pausar un premio publicado.');
        }

        $reward->update(['status' => LoyaltyReward::PAUSADO]);

        app(RecordAuditLog::class)->handle($actor, 'loyalty.reward.paused', $reward, [
            'business_id' => $reward->business_id,
        ]);

        return $reward->fresh();
    }

    /**
     * @throws LoyaltyActionException
     */
    public function resume(LoyaltyReward $reward, User $actor): LoyaltyReward
    {
        $this->assertBusinessAdmin($reward->business, $actor, 'No tienes permiso para reactivar premios en este negocio.');

        if ($reward->status !== LoyaltyReward::PAUSADO) {
            throw new LoyaltyActionException('Solo se puede reactivar un premio pausado.');
        }

        $reward->update(['status' => LoyaltyReward::PUBLICADO]);

        app(RecordAuditLog::class)->handle($actor, 'loyalty.reward.resumed', $reward, [
            'business_id' => $reward->business_id,
        ]);

        return $reward->fresh();
    }

    private function productIdFor(Business $business, mixed $productId): ?int
    {
        if ($productId === null || $productId === '') {
            return null;
        }

        $product = $business->products()->find($productId);

        if (! $product) {
            throw new LoyaltyActionException('El producto seleccionado no pertenece a este negocio.');
        }

        return $product->id;
    }

    private function storeImage(Business $business, UploadedFile $image): string
    {
        return $this->mediaUploader->store($image, 'loyalty_reward', "loyalty-rewards/{$business->id}");
    }
}
