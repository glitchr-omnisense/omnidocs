<?php

namespace Omnidocs\Html;

/**
 * The addresses of a page's links and pictures, rewritten: every <a href>
 * and <img src> of an HTML fragment is handed to $rewrite (the address as
 * written, decoded, and whether it is a picture), which answers the address
 * to print or null to keep it. For the formats whose tool writes HTML
 * (Sphinx's JSON builder); a Markdown source rewrites its links before
 * rendering, on the document.
 */
final class Links
{
    /**
     * @param callable(string $url, bool $image): ?string $rewrite
     */
    public static function rewrite(string $html, callable $rewrite): string
    {
        return preg_replace_callback(
            '#<(a|img)\b([^>]*?)\s(href|src)="([^"]*)"#',
            static function (array $m) use ($rewrite): string {
                [$whole, $tag, $before, $attribute, $url] = $m;
                $target = $rewrite(html_entity_decode($url, \ENT_QUOTES | \ENT_HTML5), 'img' === $tag);

                return null === $target ? $whole : '<'.$tag.$before.' '.$attribute.'="'.htmlspecialchars($target, \ENT_QUOTES).'"';
            },
            $html,
        ) ?? $html;
    }
}
