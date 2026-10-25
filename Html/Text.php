<?php

namespace Omnidocs\Html;

/** The plain text of an HTML fragment: what a reader reads, for a search index and a description. */
final class Text
{
    public static function of(string $html): string
    {
        // A tag ends a word: "<p>One</p><p>Two</p>" is "One Two", not "OneTwo".
        $text = strip_tags((string) preg_replace(['#<(script|style)\b.*?</\1>#si', '#<[^>]+>#'], ['', '$0 '], $html));
        $text = html_entity_decode($text, \ENT_QUOTES | \ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace(['/\s+([.,;:!?)\]])/u', '/([(\[])\s+/u', '/\s+/u'], ['$1', '$1', ' '], $text));
    }
}
