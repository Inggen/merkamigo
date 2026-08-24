<?php

namespace App\Domain\Platform\Actions;

use App\Domain\Platform\Models\PlatformKnowledgeDocument;
use App\Support\Media\MediaUploader;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use Smalot\PdfParser\Parser;
use Throwable;

/**
 * Sube el PDF de contexto general del asistente de la plataforma (pedido
 * del usuario) — mismo criterio que `SaveBusinessChatbotDocument`: el
 * texto se extrae una sola vez aquí y se guarda ya listo, así cada
 * pregunta del chat no tiene que releer ni volver a parsear el archivo.
 */
class SavePlatformKnowledgeDocument
{
    /**
     * Mismo límite que `SaveBusinessChatbotDocument` — generoso (unas
     * 15-20 páginas de texto corrido) sin volverse irrazonable para el
     * contexto de una sola respuesta.
     */
    private const MAX_TEXT_LENGTH = 40000;

    public function handle(UploadedFile $document): PlatformKnowledgeDocument
    {
        $text = $this->extractText($document);

        $knowledge = PlatformKnowledgeDocument::current();

        if ($knowledge->document_path) {
            app(MediaUploader::class)->delete($knowledge->document_path, 'private');
        }

        $knowledge->document_path = app(MediaUploader::class)->store(
            $document,
            'chatbot_document',
            'platform-assistant-knowledge',
        );
        $knowledge->document_original_name = $document->getClientOriginalName();
        $knowledge->document_text = $text;
        $knowledge->save();

        return $knowledge;
    }

    private function extractText(UploadedFile $document): string
    {
        try {
            $text = trim((new Parser)->parseFile($document->getRealPath())->getText());
        } catch (Throwable) {
            throw new InvalidArgumentException('No pudimos leer ese PDF. Asegúrate de que no esté protegido con contraseña y vuelve a intentar.');
        }

        if (blank($text)) {
            throw new InvalidArgumentException('No encontramos texto en ese PDF (¿es una imagen escaneada?). Intenta con un PDF que tenga texto seleccionable.');
        }

        return mb_substr($text, 0, self::MAX_TEXT_LENGTH);
    }
}
