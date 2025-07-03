<?php

/**
 * Core of *
 */

namespace ThinkPixel\Core;

/**
 * HTML2MD Class. The ThinkPixel plugin uses this for converting posts content to markdown.
 *
 * @category ThinkPixel
 * @package ThinkPixel
 * @subpackage Core
 * @copyright
 * @author Bogdan Dobrica <bdobrica @ gmail.com>
 * @version 1.3.0
 */

class HTML2MD
{
    /**
     * Converts an HTML string to Markdown.
     * 
     * @param string $html The HTML content to convert.
     * @return string The converted Markdown content.
     */
    static public function convert($html)
    {
        // Load HTML as DOMDocument
        $doc = new \DOMDocument();
        // Suppress warnings from malformed HTML
        @$doc->loadHTML('<?xml encoding="utf-8" ?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        // Start recursive conversion
        $markdown = HTML2MD::convertNode($doc->documentElement ?? $doc);

        return $markdown;
    }

    /**
     * Recursively converts a DOM node and its children to Markdown.
     *
     * @param \DOMElement $node The DOM node to convert.
     * @return string The Markdown representation of the node.
     */
    static private function convertNode(\DOMElement $node): string
    {
        $markdown = '';

        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $markdown .= HTML2MD::normalizeSpaces($child->nodeValue);
            } elseif ($child->nodeType === XML_ELEMENT_NODE) {
                $child = HTML2MD::castToElement($child);
                $tag = strtolower($child->nodeName);
                switch ($tag) {
                    case 'p':
                        $markdown .= "\n\n" . HTML2MD::convertNode($child) . "\n\n";
                        break;
                    case 'br':
                        $markdown .= "  \n";
                        break;
                    case 'strong':
                    case 'b':
                        $markdown .= '**' . HTML2MD::convertNode($child) . '**';
                        break;
                    case 'em':
                    case 'i':
                        $markdown .= '*' . HTML2MD::convertNode($child) . '*';
                        break;
                    case 'a':
                        $href = $child->getAttribute('href');
                        $text = HTML2MD::convertNode($child);
                        $markdown .= "[$text]($href)";
                        break;
                    case 'img':
                        $src = $child->getAttribute('src');
                        $alt = $child->getAttribute('alt');
                        $markdown .= "![$alt]($src)";
                        break;
                    case 'ul':
                        $markdown .= "\n" . HTML2MD::convertList($child, '- ') . "\n";
                        break;
                    case 'ol':
                        $markdown .= "\n" . HTML2MD::convertList($child, '1. ') . "\n";
                        break;
                    case 'blockquote':
                        $lines = explode("\n", trim(HTML2MD::convertNode($child)));
                        foreach ($lines as $line) {
                            $markdown .= '> ' . $line . "\n";
                        }
                        $markdown .= "\n";
                        break;
                    case 'code':
                        // Check if it's a child of <pre>
                        if ($child->parentNode && strtolower($child->parentNode->nodeName) === 'pre') {
                            // We'll handle in <pre>
                            break;
                        }
                        $code = trim(HTML2MD::convertNode($child));
                        // Escape backticks
                        $code = str_replace('`', '\`', $code);
                        $markdown .= "`$code`";
                        break;
                    case 'pre':
                        $first = $child->firstChild;
                        $code = '';
                        $lang = '';
                        // <pre><code class="language-php">...</code></pre>
                        if ($first && $first->nodeName === 'code') {
                            $first = HTML2MD::castToElement($first);
                            $code = $first->textContent;
                            if ($first->hasAttribute('class')) {
                                if (preg_match('/language-(\w+)/', $first->getAttribute('class'), $m)) {
                                    $lang = $m[1];
                                }
                            }
                        } else {
                            $code = $child->textContent;
                        }
                        $markdown .= "\n```$lang\n" . rtrim($code) . "\n```\n";
                        break;
                    case 'hr':
                        $markdown .= "\n---\n";
                        break;
                    case 'h1':
                    case 'h2':
                    case 'h3':
                    case 'h4':
                    case 'h5':
                    case 'h6':
                        $level = intval(substr($tag, 1));
                        $markdown .= "\n" . str_repeat('#', $level) . ' ' . HTML2MD::convertNode($child) . "\n\n";
                        break;
                    // Add more cases as needed!
                    default:
                        // Fallback: just parse inner content
                        $markdown .= HTML2MD::convertNode($child);
                        break;
                }
            }
        }
        return $markdown;
    }

    static private function castToElement(\DOMNode $node): \DOMElement
    {
        if ($node->nodeType !== XML_ELEMENT_NODE) {
            throw new \InvalidArgumentException('Node must be an element node.');
        }
        return $node;
    }

    /**
     * Normalizes spaces in a string (collapses multiple spaces and trims).
     * 
     * @param string $text The text to normalize.
     * @return string The normalized text.
     */
    static private function normalizeSpaces(string $text): string
    {
        // Normalize spaces: replace multiple spaces with a single space
        $text = preg_replace('/\s+/', ' ', $text);
        // Trim leading and trailing spaces
        return trim($text);
    }

    /**
     * Converts a list node (<ul> or <ol>) to Markdown.
     * 
     * @param \DOMElement $node The list node to convert.
     * @param string $prefix The prefix for each list item (e.g., '- ' or '1. ').
     * @return string The Markdown representation of the list.
     */
    static private function convertList(\DOMElement $node, string $prefix): string
    {
        $markdown = '';
        foreach ($node->childNodes as $child) {
            if ($child->nodeName === 'li') {
                $content = trim(HTML2MD::convertNode($child));
                $lines = explode("\n", $content);
                $firstLine = array_shift($lines);
                $markdown .= $prefix . $firstLine . "\n";
                foreach ($lines as $line) {
                    if (trim($line)) {
                        $markdown .= "   " . $line . "\n";
                    }
                }
            }
        }
        return $markdown;
    }
}
