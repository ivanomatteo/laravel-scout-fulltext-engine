<?php

namespace IvanoMatteo\LaravelScoutFullTextEngine\Parsers\Query;

use Illuminate\Support\Collection;
use IvanoMatteo\LaravelScoutFullTextEngine\Parsers\Extractors\FeatureExtractor;
use Str;

class QueryParserMysqlFullTextBool implements QueryParser
{
    private bool $matchAll = false;
    private bool $startsWith = false;
    private array $extractors = [];

    public const DEF_RESERVED_CHARS = [
        '-', '+', '<', '>', '@', '(', ')', '~', '"', "'",
    ];
    public const DEF_REPLACE_CHARS = [
        ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
    ];

    /**
     * @param \Illuminate\Database\Connection $conn
     * @param string[] $columns
     */
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
        $tmpTokens = $this->tokenize($query);

        $extracted = $this->runExtractors($tmpTokens->implode(' '));

        return $tmpTokens->map(function (string $word) {
            if ($this->startsWith) {
                $word = $word . '*';
            }
            if ($this->matchAll) {
                $word = '+' . $word;
            }

            return $word;
        })->merge($extracted)->implode(' ');
    }


    public function tokenize(string $query): Collection
    {
        $query = str_replace(static::DEF_RESERVED_CHARS, static::DEF_REPLACE_CHARS, $query);

        return collect(preg_split("/\\s+/", Str::transliterate(trim($query))))
            ->filter(fn ($str) => (trim($str) !== '' && $str !== null))
            ->map(function ($word) {
                return implode(' ', preg_split("/\\s+/", trim($word)));
            });
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
}
