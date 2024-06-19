<?php

namespace App\Tools;

use DateTime;

class Tools
{
    public static function hasEmptyField($item, $fields): bool
    {
        foreach ($fields as $field) {
            if (!empty($item[$field])) {
                continue;
            }

            return true;
        }

        return false;
    }


    /**
     * @param mixed ...$args
     * @return string
     */
    public static function getSlug(...$args): string
    {
        $args = func_get_args();
        if (!empty($args) && count($args) > 0) {
            $name = [];
            foreach ($args as $arg) {
                $name[] = $arg;
            }
            $slug = self::slugify(count($name) > 1 ? implode(" ", $name) : $name[0]);
            if (!empty($slug)) {
                return $slug;
            }
        }
        return "default-slug-" . uniqid();
    }

    public static function slugify($text, string $divider = '-'): ?string
    {
        // replace non letter or digits by divider
        $text = preg_replace('~[^\pL\d]+~u', $divider, $text);

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // trim
        $text = trim($text, $divider);

        // remove duplicate divider
        $text = preg_replace('~-+~', $divider, $text);

        // lowercase
        $text = strtolower($text);

        return !empty($text) ? $text : null;
    }
}