@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'canonical' => null,
    'robots' => null,
    'pageSchemaType' => 'WebPage',
    'pageSchemaData' => [],
    'schemaGraph' => [],
    'ogType' => 'website',
])

{{--
    Pedido del usuario (2026-10-11): revisó todos los enlaces del footer
    (términos, privacidad, soporte, cómo funciona, preguntas frecuentes,
    etc. — todos usan este layout) y en ninguno aparecía la barra
    inferior móvil, porque este layout era una copia separada de
    `layouts/cliente.blade.php` a la que nunca se le agregó esa barra
    (ni el banner de impersonación, ni el `<flux:toast.group>` —
    tres cosas que `cliente.blade.php` sí tiene y esta copia no).

    En vez de volver a copiar esas tres piezas acá (y arriesgarse a que
    se desincronicen de nuevo, como ya pasó una vez con el chatbot),
    este layout ahora delega directo en `layouts/cliente.blade.php` —
    mismos props, mismo slot, una sola fuente de verdad para TODO lo
    que envuelve el contenido de una página pública o interna del
    cliente. Las únicas diferencias que tenía esta copia
    (`showChatWidget`/`showSidebar`) ya traen un valor por defecto
    correcto en `cliente.blade.php` y ninguna vista que usa
    `x-layouts::public` necesitaba cambiarlos.
--}}
<x-layouts::cliente
    :title="$title"
    :description="$description"
    :image="$image"
    :canonical="$canonical"
    :robots="$robots"
    :page-schema-type="$pageSchemaType"
    :page-schema-data="$pageSchemaData"
    :schema-graph="$schemaGraph"
    :og-type="$ogType"
>
    {{ $slot }}
</x-layouts::cliente>
