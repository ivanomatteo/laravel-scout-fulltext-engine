<?php
namespace IvanoMatteo\LaravelScoutFullTextEngine\Parsers\Extractors;



class DottedWordsExtractor implements FeatureExtractor
{
    public function extract($searchText): array
    {
        preg_match_all('/(([[:alpha:]]\.+)+[[:alpha:]])/', $searchText, $m);

        $removed = collect($m[0])->map(function ($word) {
            return trim(str_replace('.', '', $word));
        });

        $undescore = collect($m[0])->map(function ($word) {
            return trim(preg_replace('/\.+/', '_', $word));
        });

        $words = $removed->merge($undescore)->unique()->toArray();

        return $words;
    }
}
