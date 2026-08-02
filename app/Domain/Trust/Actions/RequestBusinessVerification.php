<?php

namespace App\Domain\Trust\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Platform\Actions\RecordAuditLog;
use App\Domain\Trust\Models\BusinessVerification;
use App\Models\User;
use App\Support\Media\MediaUploader;
use Illuminate\Http\UploadedFile;

/**
 * Un negocio solicita (o vuelve a solicitar, tras "requiere_ajustes")
 * verificación (3.1 del TODO). Una sola solicitud por negocio —
 * reenviar actualiza la misma fila en vez de crear otra, igual que otras
 * "una por entidad" ya usadas en el proyecto (ofertas por necesidad).
 */
class RequestBusinessVerification
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Business $business, User $actor, array $data, ?UploadedFile $document = null): BusinessVerification
    {
        $verification = BusinessVerification::firstOrNew(['business_id' => $business->id]);

        $verification->fill([
            'business_id' => $business->id,
            'requested_by' => $actor->id,
            'legal_name' => $data['legal_name'],
            'contact_name' => $data['contact_name'],
            'contact_document_type' => $data['contact_document_type'],
            'contact_document_number' => $data['contact_document_number'],
            'request_note' => $data['request_note'] ?? null,
            'status' => BusinessVerification::EN_REVISION,
        ]);

        if ($document) {
            if ($verification->verification_document_path) {
                app(MediaUploader::class)->delete($verification->verification_document_path, 'private');
            }

            $verification->verification_document_path = app(MediaUploader::class)->store(
                $document,
                'verification_document',
                "business-verifications/{$business->id}",
            );
        }

        $verification->save();

        app(RecordAuditLog::class)->handle($actor, 'business.verification_requested', $verification, [
            'business_id' => $business->id,
        ]);

        return $verification->fresh();
    }
}
