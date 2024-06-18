<?php

namespace App\Tools;

use DateTime;

class Tools
{
    /**
     * @return DateTime
     */
    public static function currentDatetime(): DateTime
    {
        return new DateTime();
    }

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
}