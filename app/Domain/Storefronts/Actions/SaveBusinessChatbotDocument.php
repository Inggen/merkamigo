<?php

namespace App\Domain\Storefronts\Actions;

use App\Domain\Businesses\Models\Business;
use App\Domain\Storefronts\Models\BusinessChatbotProfile;
use App\Support\Media\MediaUploader;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use Smalot\PdfParser\Parser;
use Throwable;

/**
 * Sube el PDF con información del negocio que el chatbot IA usa como
 * contexto (pedido del usuario, ver `AnswerBusinessChatQuestion`). El
 * texto se extrae una sola vez aquí y se guarda ya listo — así cada
 * pregunta del chat no tiene que releer ni volver a parsear el archivo.
 */
class SaveBusinessChatbotDocument
{
    /**
     * Límite de caracteres del texto extraído que se manda al modelo en
     * cada pregunta — un PDF de varias páginas puede tener miles de
     * palabras; sin tope, cada mensaje del chat se volvería carísimo y
     * lento. Es generoso (unas 15-20 páginas de texto corrido) sin volverse
     * irrazonable para el contexto de una sola respuesta.
     */
    private const MAX_TEXT_LENGTH = 40000;

    public function handle(Business $business, UploadedFile $document): BusinessChatbotProfile
    {
        $text = $this->extractText($document);

        $profile = BusinessChatbotProfile::firstOrNew(['business_id' => $business->id]);

        if ($profile->document_path) {
            app(MediaUploader::class)->delete($profile->document_path, 'private');
        }

        $profile->business_id = $business->id;
        $profile->document_path = app(MediaUploader::class)->store(
            $document,
            'chatbot_document',
            "business-chatbot/{$business->id}",
        );
        $profile->document_original_name = $document->getClientOriginalName();
        $profile->document_text = $text;
        $profile->save();

        return $profile;
    }

    private function extractText(UploadedFile $document): string
    {
        try {
            $text = trim((new Parser)->parseFile($document->getRealPath())->getText());
        } catch (Throwable) {
            throw new InvalidArgumentException('No pudimos leer ese PDF. Asegúrate de que no esté protegido con contraseña y vuelve a intentar.');
        }

        if (blank($text)) {
            throw new InvalidArgumentException('No encontramos texto en ese PDF (¿es una imagen escaneada?). Intenta con un PDF que tenga texto seleccionable, o usa el campo de notas.');
        }

        return mb_substr($text, 0, self::MAX_TEXT_LENGTH);
    }
}
