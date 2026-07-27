<x-layouts::public :title="__('Reglas de comunidad')">
    <article class="prose prose-zinc mx-auto max-w-3xl px-6 py-16 dark:prose-invert">
        {!! Str::markdown(file_get_contents(base_path('docs/legal/reglas-comunidad.md'))) !!}
    </article>
</x-layouts::public>
