<x-layouts::public :title="__('Términos de uso')">
    <article class="prose prose-zinc mx-auto max-w-3xl px-6 py-16 dark:prose-invert">
        {!! Str::markdown(file_get_contents(base_path('docs/legal/terminos.md'))) !!}
    </article>
</x-layouts::public>
