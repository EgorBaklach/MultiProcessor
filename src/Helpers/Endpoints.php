<?php namespace Helpers;

class Endpoints
{
    public static function getUrl()
    {
        return 'https://www.instagram.com/';
    }

    public static function getMedia($short_code)
    {
        return "https://www.instagram.com/p/$short_code/";
    }

    public static function getGraphql()
    {
        return 'https://www.instagram.com/graphql/query/';
    }

    public static function getSearch()
    {
        return 'https://www.instagram.com/web/search/topsearch/';
    }

    public static function getGTMedias()
    {
        $k = mt_rand(0, 2);
        $ar = [
            '298b92c8d7cad703f7565aa892ede943',
            'c769cb6c71b24c8a86590b22402fda50',
            '9b498c08113f1e09617a1703c22b2f32'
        ];

        return $ar[$k];
    }

    public static function getGoogleSearch()
    {
        return 'https://www.google.com/search';
    }

    public static function getSpeller()
    {
        return 'https://speller.yandex.net/services/spellservice.json/checkText';
    }
}
