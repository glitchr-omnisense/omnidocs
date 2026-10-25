<?php

namespace Omnidocs\Html;

/**
 * A comment's prose as HTML - a PHPDoc's, a JSDoc's description: paragraphs at
 * blank lines, a block indented by four spaces or more as code, lines that
 * start with "-" or "*" as a list, `code` as code; everything else escaped.
 * For the API references, whose tools give text, not Markdown.
 */
final class Prose
{
    /**
     * Paragraphs at blank lines, a block indented by four spaces or more as
     * code, lines starting with "-" or "*" as a list, `code` as code; everything else escaped.
     */
    public static function toHtml(string $text): string
    {
        $html = '';
        foreach (preg_split('/\R{2,}/', trim($text)) ?: [] as $block) {
            if ('' === trim($block)) {
                continue;
            }
            $lines = preg_split('/\R/', $block) ?: [];
            if (preg_match('/^( {4,}|\t)/', $lines[0]) && \count(array_filter($lines, static fn (string $l): bool => '' !== trim($l) && !preg_match('/^( {2,}|\t)/', $l))) === 0) {
                $min = min(array_map(static fn (string $l): int => \strlen($l) - \strlen(ltrim($l)), array_filter($lines, 'trim')));
                $html .= '<pre><code>'.htmlspecialchars(implode("\n", array_map(static fn (string $l): string => substr($l, $min), $lines)), \ENT_QUOTES).'</code></pre>';
            } elseif (preg_match('/^\s*[-*] /', $lines[0])) {
                $items = preg_split('/\R\s*[-*] /', "\n".ltrim($block)) ?: [];
                $html .= '<ul>'.implode('', array_map(static fn (string $i): string => '<li>'.self::inline(trim((string) preg_replace('/^\s*[-*] /', '', $i))).'</li>', array_filter($items, 'trim'))).'</ul>';
            } else {
                $html .= '<p>'.self::inline(trim($block)).'</p>';
            }
        }

        return $html;
    }

    private static function inline(string $text): string
    {
        $out = '';
        foreach (preg_split('/(`[^`]+`)/', $text, -1, \PREG_SPLIT_DELIM_CAPTURE) ?: [] as $i => $piece) {
            $out .= 1 === $i % 2 ? '<code>'.htmlspecialchars(substr($piece, 1, -1), \ENT_QUOTES).'</code>' : nl2br(htmlspecialchars($piece, \ENT_QUOTES), false);
        }

        return str_replace("<br>\n", "\n", $out);
    }
}
