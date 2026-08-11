<?php

namespace Emaia\LaravelHotwire\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use InvalidArgumentException;

final readonly class RichTextContent
{
    /** @var array<string, true> */
    private const array BLOCK_ELEMENTS = [
        'address' => true,
        'article' => true,
        'aside' => true,
        'blockquote' => true,
        'center' => true,
        'dd' => true,
        'details' => true,
        'dialog' => true,
        'dir' => true,
        'div' => true,
        'dl' => true,
        'dt' => true,
        'fieldset' => true,
        'figcaption' => true,
        'figure' => true,
        'footer' => true,
        'form' => true,
        'h1' => true,
        'h2' => true,
        'h3' => true,
        'h4' => true,
        'h5' => true,
        'h6' => true,
        'header' => true,
        'hr' => true,
        'li' => true,
        'main' => true,
        'menu' => true,
        'nav' => true,
        'ol' => true,
        'p' => true,
        'pre' => true,
        'section' => true,
        'summary' => true,
        'table' => true,
        'tbody' => true,
        'td' => true,
        'tfoot' => true,
        'th' => true,
        'thead' => true,
        'tr' => true,
        'ul' => true,
    ];

    /** @var array<string, true> */
    private const array IGNORED_ELEMENTS = [
        'base' => true,
        'desc' => true,
        'head' => true,
        'link' => true,
        'meta' => true,
        'noscript' => true,
        'script' => true,
        'style' => true,
        'template' => true,
        'title' => true,
    ];

    /** @var array<string, true> */
    private const array NON_TEXT_CONTENT_ELEMENTS = [
        'audio' => true,
        'canvas' => true,
        'embed' => true,
        'hr' => true,
        'iframe' => true,
        'img' => true,
        'object' => true,
        'svg' => true,
        'video' => true,
    ];

    /** @var array<string, true> */
    private const array OPAQUE_MEDIA_ELEMENTS = [
        'audio' => true,
        'canvas' => true,
        'embed' => true,
        'iframe' => true,
        'object' => true,
        'video' => true,
    ];

    private function __construct(
        private string $text,
        private bool $hasNonTextContent,
    ) {}

    /**
     * Extract normalized plain text from an HTML fragment.
     *
     * @throws InvalidArgumentException when the fragment is not valid UTF-8 or cannot be parsed.
     */
    public static function fromHtml(string $html): self
    {
        if (! mb_check_encoding($html, 'UTF-8')) {
            throw new InvalidArgumentException('Rich text HTML must be valid UTF-8.');
        }

        $html = self::normalizeNamedEntities($html);

        $document = new DOMDocument('1.0', 'UTF-8');
        $loaded = $document->loadHTML(
            '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>'
            .$html.'</body></html>',
            LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_COMPACT,
        );

        if (! $loaded) {
            throw new InvalidArgumentException('Rich text HTML could not be parsed.');
        }

        $extracted = self::extract($document);

        return new self(
            self::normalize($extracted['text']),
            $extracted['hasNonTextContent'],
        );
    }

    /** Determine whether the fragment has no normalized text or recognized non-text content. */
    public function isBlank(): bool
    {
        return $this->text === '' && ! $this->hasNonTextContent;
    }

    /** Return normalized textual content in document order. */
    public function plainText(): string
    {
        return $this->text;
    }

    /** Count characters in the normalized text using Laravel's multibyte string semantics. */
    public function plainTextLength(): int
    {
        return mb_strlen($this->text);
    }

    /**
     * @return array{text: string, hasNonTextContent: bool}
     */
    private static function extract(DOMNode $node): array
    {
        if ($node->nodeType === XML_TEXT_NODE || $node->nodeType === XML_CDATA_SECTION_NODE) {
            return ['text' => $node->nodeValue ?? '', 'hasNonTextContent' => false];
        }

        if ($node->nodeType === XML_COMMENT_NODE) {
            return ['text' => '', 'hasNonTextContent' => false];
        }

        $tag = $node instanceof DOMElement ? strtolower($node->tagName) : null;

        if ($tag !== null && isset(self::IGNORED_ELEMENTS[$tag])) {
            return ['text' => '', 'hasNonTextContent' => false];
        }

        if ($tag !== null && isset(self::OPAQUE_MEDIA_ELEMENTS[$tag])) {
            return ['text' => '', 'hasNonTextContent' => true];
        }

        if ($tag === 'br') {
            return ['text' => "\n", 'hasNonTextContent' => false];
        }

        $isBlock = $tag !== null && isset(self::BLOCK_ELEMENTS[$tag]);
        $text = $isBlock ? "\n" : '';
        $hasNonTextContent = $tag !== null && isset(self::NON_TEXT_CONTENT_ELEMENTS[$tag]);

        foreach ($node->childNodes as $child) {
            $extracted = self::extract($child);
            $text .= $extracted['text'];
            $hasNonTextContent = $hasNonTextContent || $extracted['hasNonTextContent'];
        }

        if ($isBlock) {
            $text .= "\n";
        }

        return ['text' => $text, 'hasNonTextContent' => $hasNonTextContent];
    }

    private static function normalize(string $text): string
    {
        $text = preg_replace('/\r\n?|\x{0085}|\x{2028}|\x{2029}/u', "\n", $text) ?? $text;
        $text = preg_replace('/[\p{Zs}\t\x{000B}\x{000C}]+/u', ' ', $text) ?? $text;
        $text = preg_replace('/(?!\n)\p{Cc}/u', '', $text) ?? $text;
        $text = preg_replace('/(?![\x{200C}\x{200D}])\p{Cf}/u', '', $text) ?? $text;
        $text = preg_replace('/ *\n */u', "\n", $text) ?? $text;
        $text = preg_replace('/\n+/u', "\n", $text) ?? $text;
        $text = trim($text);

        return self::containsOnlyInvisibleCharacters($text) ? '' : $text;
    }

    private static function normalizeNamedEntities(string $html): string
    {
        return preg_replace_callback(
            '/&[A-Za-z][A-Za-z0-9]+;/',
            function (array $matches): string {
                $decoded = html_entity_decode($matches[0], ENT_QUOTES | ENT_HTML5, 'UTF-8');

                if ($decoded === $matches[0]) {
                    return $matches[0];
                }

                return htmlspecialchars($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            },
            $html,
        ) ?? $html;
    }

    private static function containsOnlyInvisibleCharacters(string $text): bool
    {
        return preg_match('/^[\p{Z}\p{Cc}\p{Cf}\p{Default_Ignorable_Code_Point}]*$/u', $text) === 1;
    }
}
