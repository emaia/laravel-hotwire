<?php

namespace Emaia\LaravelHotwire\Support;

use Illuminate\View\Compilers\ComponentTagCompiler;

class HotwireTagCompiler extends ComponentTagCompiler
{
    /** @param string[] $prefixes */
    public function __construct(
        array $aliases = [],
        array $namespaces = [],
        $blade = null,
        private readonly array $prefixes = ['hw'],
    ) {
        parent::__construct($aliases, $namespaces, $blade);
    }

    protected function compileOpeningTags(string $value): array|string|null
    {
        $prefixPattern = $this->prefixPattern();

        $pattern = "/
            <
                \s*
                (?<prefix>$prefixPattern)[\:](?<component>[\w\-\:\.]*)
                (?<attributes>
                    (?:
                        \s+
                        (?:
                            (?:
                                @(?:class)(\( (?: (?>[^()]+) | (?-1) )* \))
                            )
                            |
                            (?:
                                @(?:style)(\( (?: (?>[^()]+) | (?-1) )* \))
                            )
                            |
                            (?:
                                \{\{\s*\\\$attributes(?:[^}]+?)?\s*\}\}
                            )
                            |
                            (?:
                                (\:\\\$)(\w+)
                            )
                            |
                            (?:
                                [\w\-:.@%]+
                                (
                                    =
                                    (?:
                                        \\\"[^\\\"]*\\\"
                                        |
                                        '[^']*'
                                        |
                                        [^'\\\"=<>]+
                                    )
                                )?
                            )
                        )
                    )*
                    \s*
                )
                (?<![\/=\-])
            >
        /x";

        return preg_replace_callback($pattern, function (array $matches) {
            $this->boundAttributes = [];

            $attributes = $this->getAttributesFromAttributeString($matches['attributes']);

            return $this->componentString($matches['prefix'].'::'.$matches['component'], $attributes);
        }, $value);
    }

    protected function compileSelfClosingTags(string $value): array|string|null
    {
        $prefixPattern = $this->prefixPattern();

        $pattern = "/
            <
                \s*
                (?<prefix>$prefixPattern)[\:](?<component>[\w\-\:\.]*)
                \s*
                (?<attributes>
                    (?:
                        \s+
                        (?:
                            (?:
                                @(?:class)(\( (?: (?>[^()]+) | (?-1) )* \))
                            )
                            |
                            (?:
                                @(?:style)(\( (?: (?>[^()]+) | (?-1) )* \))
                            )
                            |
                            (?:
                                \{\{\s*\\\$attributes(?:[^}]+?)?\s*\}\}
                            )
                            |
                            (?:
                                (\:\\\$)(\w+)
                            )
                            |
                            (?:
                                [\w\-:.@%]+
                                (
                                    =
                                    (?:
                                        \\\"[^\\\"]*\\\"
                                        |
                                        '[^']*'
                                        |
                                        [^'\\\"=<>]+
                                    )
                                )?
                            )
                        )
                    )*
                    \s*
                )
            \/>
        /x";

        return preg_replace_callback($pattern, function (array $matches) {
            $this->boundAttributes = [];

            $attributes = $this->getAttributesFromAttributeString($matches['attributes']);

            if (isset($attributes['slot'])) {
                $slot = $attributes['slot'];

                unset($attributes['slot']);

                return '@slot('.$slot.') '.$this->componentString($matches['prefix'].'::'.$matches['component'], $attributes)."\n@endComponentClass##END-COMPONENT-CLASS##".' @endslot';
            }

            return $this->componentString($matches['prefix'].'::'.$matches['component'], $attributes)."\n@endComponentClass##END-COMPONENT-CLASS##";
        }, $value);
    }

    protected function compileClosingTags(string $value): array|string|null
    {
        $prefixPattern = $this->prefixPattern();

        return preg_replace("/<\/\s*(?:$prefixPattern)[\:][\w\-\:\.]*\s*>/", ' @endComponentClass##END-COMPONENT-CLASS##', $value);
    }

    private function prefixPattern(): string
    {
        return implode('|', array_map(fn (string $prefix) => preg_quote($prefix, '/'), $this->prefixes));
    }
}
