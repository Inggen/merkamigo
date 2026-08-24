<?php

namespace App\Domain\Platform\Actions;

use App\Domain\Platform\Models\PlatformKnowledgeDocument;
use App\Support\Media\MediaUploader;

class RemovePlatformKnowledgeDocument
{
    public function handle(PlatformKnowledgeDocument $knowledge): void
    {
        if ($knowledge->document_path) {
            app(MediaUploader::class)->delete($knowledge->document_path, 'private');
        }

        $knowledge->update([
            'document_path' => null,
            'document_original_name' => null,
            'document_text' => null,
        ]);
    }
}
