<?php

namespace Omnidocs\Html;

/**
 * The HTML every format renders to, so that one stylesheet and one script
 * read a manual whatever wrote it - the conventions of symfony.com/doc:
 *
 *  - a callout: <blockquote class="doc-callout doc-callout-note" data-label="Note">,
 *    for note, tip, important, warning, caution, seealso; versionadded and
 *    deprecated carry their version (data-version) and say it in their label;
 *  - a version badge beside a heading: <span class="doc-version" data-version="1.4">;
 *  - a code block in its frame: <div class="doc-code" data-lang="js">, its
 *    caption (a file name) above it in <div class="doc-code-caption">;
 *  - code blocks side by side in tabs (YAML / PHP, npm / yarn):
 *    <div class="doc-tabs" data-doc-tabs>, a tab list of buttons and a
 *    panel each; without a script every panel shows, its title above it.
 *
 * assets/omnidocs.js gives the tabs and a copy button to each frame.
 */
final class Conventions
{
    public const CALLOUTS = ['note', 'tip', 'important', 'warning', 'caution', 'seealso', 'versionadded', 'deprecated'];

    /** The labels, in English; %version% is the callout's version. */
    public const LABELS = [
        'note' => 'Note',
        'tip' => 'Tip',
        'important' => 'Important',
        'warning' => 'Warning',
        'caution' => 'Caution',
        'seealso' => 'See also',
        'versionadded' => 'New in version %version%',
        'deprecated' => 'Deprecated since version %version%',
    ];

    /**
     * The words a reader reads, in their language: any of LABELS' keys.
     *
     * @param array<string, string> $labels
     */
    public function __construct(private readonly array $labels = [])
    {
    }

    public function label(string $type, ?string $version = null): string
    {
        $label = $this->labels[$type] ?? self::LABELS[$type] ?? ucfirst($type);

        return trim(str_replace('%version%', (string) $version, $label));
    }

    /** A callout around $inner (HTML). */
    public function callout(string $type, string $inner, ?string $version = null): string
    {
        return '<blockquote '.self::attributes($this->calloutAttributes($type, $version)).'>'.$inner.'</blockquote>';
    }

    /** @return array<string, string> the attributes of a callout's element, in their order */
    public function calloutAttributes(string $type, ?string $version = null): array
    {
        return ['class' => 'doc-callout doc-callout-'.$type, 'data-label' => $this->label($type, $version)] + (null !== $version && '' !== $version ? ['data-version' => $version] : []);
    }

    public function versionBadge(string $version, string $type = 'versionadded'): string
    {
        return '<span class="doc-version doc-version-'.self::escape($type).'" data-version="'.self::escape($version).'" title="'.self::escape($this->label($type, $version)).'">'.self::escape($version).'</span>';
    }

    /**
     * A code block in its frame. $code is HTML already (escaped, or coloured by a highlighter).
     */
    public function code(string $code, ?string $language = null, ?string $caption = null): string
    {
        $language = null !== $language && '' !== $language ? $language : null;

        return '<div class="doc-code"'.(null !== $language ? ' data-lang="'.self::escape($language).'"' : '').'>'
            .(null !== $caption && '' !== $caption ? '<div class="doc-code-caption">'.self::escape($caption).'</div>' : '')
            .'<pre class="notranslate"><code'.(null !== $language ? ' class="language-'.self::escape($language).'"' : '').'>'.$code.'</code></pre>'
            .'</div>';
    }

    /**
     * Panels side by side, one shown at a time: [title => HTML]. $id makes the
     * ids of its tabs and panels unique in the page ("tabs-1").
     *
     * @param array<string, string> $panels
     */
    public function tabs(array $panels, string $id): string
    {
        $list = '';
        $bodies = '';
        $i = 0;
        foreach ($panels as $title => $html) {
            $tab = self::escape($id.'-tab-'.$i);
            $panel = self::escape($id.'-panel-'.$i);
            $list .= '<button type="button" role="tab" id="'.$tab.'" aria-controls="'.$panel.'" aria-selected="'.(0 === $i ? 'true' : 'false').'">'.self::escape((string) $title).'</button>';
            $bodies .= '<div class="doc-tab" role="tabpanel" id="'.$panel.'" aria-labelledby="'.$tab.'" data-tab="'.self::escape((string) $title).'">'.$html.'</div>';
            ++$i;
        }

        return '<div class="doc-tabs" data-doc-tabs><div class="doc-tabs-list" role="tablist">'.$list.'</div>'.$bodies.'</div>';
    }

    /** @param array<string, string> $attributes */
    public static function attributes(array $attributes): string
    {
        $out = [];
        foreach ($attributes as $name => $value) {
            $out[] = $name.'="'.self::escape($value).'"';
        }

        return implode(' ', $out);
    }

    public static function escape(string $text): string
    {
        return htmlspecialchars($text, \ENT_QUOTES | \ENT_SUBSTITUTE | \ENT_HTML5, 'UTF-8');
    }
}
