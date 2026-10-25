<?php

namespace Omnidocs;

use Omnidocs\Model\Node;
use Omnidocs\Model\Page;
use Omnidocs\Model\Symbol;

/**
 * One version of a manual, as one format wrote it: a folder of Markdown, the
 * output of Sphinx's JSON builder, Doxygen's XML, the JSON JSDoc explains.
 * Every format answers the same three questions - the contents, a page, the
 * API entries - so that a site renders any of them with the same templates.
 *
 * A source reads files that already exist (a checkout, a build's output): it
 * never runs the tool that wrote them, never reaches the network.
 */
interface SourceInterface
{
    /** The manual's home page, when the source has one ("" is its path). */
    public function home(): ?Node;

    /**
     * The contents, in reading order: pages, and sections holding pages.
     * The home is not in it.
     *
     * @return list<Node>
     */
    public function tree(): array;

    /**
     * A page rendered, by its path ("" for the home); null when there is
     * none. Its links to other pages of the manual, to other files of the
     * repository and its pictures go through $links: the site says where
     * each one is published.
     */
    public function page(string $path, ?LinkerInterface $links = null): ?Page;

    /**
     * The API entries the source documents, each on its page at its anchor;
     * a manual of prose alone has none.
     *
     * @return list<Symbol>
     */
    public function symbols(): array;
}
