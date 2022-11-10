<?php

namespace IvanoMatteo\LaravelScoutFullTextEngine\Parsers\Query;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use IvanoMatteo\LaravelScoutFullTextEngine\Parsers\Extractors\FeatureExtractor;

class QueryParserMysqlFullTextBool implements QueryParser
{
    private bool $matchAll = false;
    private bool $startsWith = false;
    private array $extractors = [];

    public const DEF_RESERVED_CHARS = [
        '-', '+', '<', '>', '@', '(', ')', '~', '"', "'",
    ];

    public function __construct()
    {
    }

    public function addExtractor(FeatureExtractor $extractor, bool $mustMatch = false, bool $startsWith = false): static
    {
        $this->extractors[] = [
            'prefix' => $mustMatch ? '+' : '',
            'suffix' => $startsWith ? '*' : '',
            'extractor' => $extractor,
        ];

        return $this;
    }

    public function matchAll(): static
    {
        $this->matchAll = true;

        return $this;
    }

    public function startsWith(): static
    {
        $this->startsWith = true;

        return $this;
    }

    public function parseSearchText(string $query): string
    {
        $tokens = $this->tokenize($query);

        $extracted = $this->runExtractors($tokens->implode(' '));

        $tokens = $this->filterTokens($tokens);
        $tokens = $this->addQuantizers($tokens);

        return $tokens->merge($extracted)->implode(' ');
    }

    public function tokenize(string $query): Collection
    {
        return collect(preg_split("/\\s+/", trim(Str::transliterate($query))))
            ->filter(fn ($str) => ($str !== null && trim($str) !== ''));
    }

    public function runExtractors(string $tmpQuery): Collection
    {
        /** @var Collection */
        $result = collect($this->extractors)
            ->reduce(function (Collection $carry, array $extr) use ($tmpQuery) {
                $tmp_extracted = $extr['extractor']->extract($tmpQuery);

                return $carry->merge(collect($tmp_extracted)
                    ->map(function (string $str) use ($extr) {
                        return $extr['prefix'] . $str . $extr['suffix'];
                    }));
            }, collect());

        return $result;
    }

    public function filterTokens(Collection $tokens): Collection
    {
        return $tokens->map(function (string $word) {

            if (Str::contains($word, static::DEF_RESERVED_CHARS)) {
                $word = '"' . trim(str_replace('"', ' ', $word)) . '"';
            }

            return $word;
        });
    }

    public function addQuantizers(Collection $tokens): Collection
    {
        return $tokens->map(function (string $word) {

            if ($this->startsWith && !Str::endsWith($word, '"')) {
                $word = $word . '*';
            }

            if ($this->matchAll) {
                $word = '+' . $word;
            }

            return $word;
        });
    }
}
