<?php

namespace App\Support\Navigation;

/**
 * Única fuente de verdad de "¿se ve la barra inferior móvil del
 * cliente (Vitrinas/Comunidad/Pídelo/Eventos/Merkapuntos) en esta
 * petición?" — usada tanto por `<x-cliente-bottom-nav>` en
 * `layouts/cliente.blade.php` y `layouts/app/sidebar.blade.php` para
 * decidir si se pinta, como por `<x-storefront-chat-widget>` (en esos
 * mismos layouts y en las páginas de vitrina que lo renderizan aparte)
 * para decidir si debe levantarse por encima de ella.
 *
 * Nace de un bug reportado por el usuario (2026-10-11): la condición
 * vivía duplicada en cada sitio y, al arreglar una copia para que un
 * invitado también viera la barra, el chatbot (que tenía su propia
 * copia desactualizada) quedó posicionado como si la barra siguiera
 * oculta para invitados, tapándose con ella.
 *
 * Pedido del usuario (2026-10-17): la barra debe verse en móvil para
 * TODOS — invitados, clientes, emprendedores y quienes todavía no han
 * elegido experiencia —, en todas las páginas. Por eso ya no depende
 * de `experience`; se conserva el método como punto único por si en
 * el futuro alguna página necesita excluirla.
 */
class ClientBottomNav
{
    public static function visible(): bool
    {
        return true;
    }
}
