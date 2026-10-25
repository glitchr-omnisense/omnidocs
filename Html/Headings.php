<?php

namespace Omnidocs\Html;

use Omnidocs\Model\Heading;

/**
 * The headings of an HTML fragment that carry an id - on the heading, or on
 * the element that wraps it (Sphinx's <section id>) - for "on this page" and
 * the search index; and ids for texts that have none.
 */
final class Headings
{
    /**
     * @param int $from the first level kept
     * @param int $to   the last level kept
     *
     * @return list<Heading>
     */
    public static function of(string $html, int $from = 2, int $to = 4): array
    {
        $headings = [];
        if (!preg_match_all('#<h([1-6])(\s[^>]*)?>(.*?)</h\1>#s', $html, $matches, \PREG_SET_ORDER | \PREG_OFFSET_CAPTURE)) {
            return [];
        }
        foreach ($matches as $m) {
            $level = (int) $m[1][0];
            if ($level < $from || $level > $to) {
                continue;
            }
            $id = null;
            if (preg_match('/\sid="([^"]+)"/', $m[2][0] ?? '', $own)) {
                $id = $own[1];
            } elseif (preg_match_all('/<(?:section|div)\b[^>]*\sid="([^"]+)"[^>]*>\s*$/', substr($html, max(0, $m[0][1] - 400), min(400, $m[0][1])), $wrap)) {
                // Sphinx: <section id="quickstart"><h1>Quickstart<a class="headerlink"...
                $id = end($wrap[1]);
            }
            if (null === $id || '' === $id) {
                continue;
            }
            $headings[] = new Heading($level, html_entity_decode($id, \ENT_QUOTES | \ENT_HTML5), Text::of(self::withoutPermalinks($m[3][0])));
        }

        return $headings;
    }

    /** A heading's text without the permalink a tool adds to it (¶, #). */
    public static function withoutPermalinks(string $html): string
    {
        // A permalink, and what a reference adds to a heading beside its name (its kind: "class").
        return (string) preg_replace(['#<a\b[^>]*class="[^"]*(?:headerlink|heading-anchor|doc-anchor)[^"]*"[^>]*>.*?</a>#s', '#<span\b[^>]*class="[^"]*api-kind[^"]*"[^>]*>.*?</span>#s'], '', $html);
    }

    /**
     * An id for a text, unique among $taken (which it joins): "Scroll stops" → "scroll-stops",
     * then "scroll-stops-1". Letters of any script are kept, as GitHub keeps them.
     *
     * @param array<string, true> $taken
     */
    public static function id(string $text, array &$taken = []): string
    {
        $slug = trim((string) preg_replace('/[^\p{L}\p{N}_]+/u', '-', mb_strtolower(trim($text))), '-');
        $slug = '' !== $slug ? $slug : 'section';
        $id = $slug;
        for ($i = 1; isset($taken[$id]); ++$i) {
            $id = $slug.'-'.$i;
        }
        $taken[$id] = true;

        return $id;
    }
}
