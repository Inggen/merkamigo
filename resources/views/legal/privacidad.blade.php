<x-layouts::public :title="__('Política de privacidad')">
    <article class="prose prose-zinc mx-auto max-w-3xl px-6 py-16 dark:prose-invert">
        {!! Str::markdown(file_get_contents(base_path('docs/legal/privacidad.md'))) !!}
    </article>
</x-layouts::public>
