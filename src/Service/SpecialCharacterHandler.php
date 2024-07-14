<?php

namespace Lingua\Service;

class SpecialCharacterHandler
{
    private const POL_CHARACTER_MAP = [
        "ą" => "a",
        "ć" => "c",
        "ę" => "e",
        "ł" => "l",
        "ń" => "n",
        "ó" => "o",
        "ś" => "s",
        "ź" => "z",
        "ż" => "z",
    ];

    private const DEU_CHARACTER_MAP = [
        "ä" => "a",
        "ö" => "o",
        "ü" => "u",
        "ß" => "s",
    ];

    private const SWE_CHARACTER_MAP = [
        'å' => 'a',
        "ä" => "a",
        "ö" => "o",
        "é" => "e",
        "ü" => "u",
    ];

    private const NOR_CHARACTER_MAP_INDIRECT = [
        'æ' => 'ae',
        "ø" => "oe",
        "å" => "aa",
    ];

    /**
     * Replaces special characters for target iso code, or replaces original text if code is not supported
     *
     * @param string $text
     * @param string $isoCode3digit
     * @param bool   $directCharacter - because turns out that some services use "ö -> o", other "ö -> oe"
     *
     * @return string
     */
    public static function escapeCharacters(string $text, string $isoCode3digit, bool $directCharacter = true): string
    {
        $characters = match (strtolower($isoCode3digit)) {
            "nor"   => self::NOR_CHARACTER_MAP_INDIRECT,
            default => [],
        };

        if ($directCharacter) {
            $characters = match (strtolower($isoCode3digit)) {
                "deu"   => self::DEU_CHARACTER_MAP,
                "pol"   => self::POL_CHARACTER_MAP,
                "swe"   => self::SWE_CHARACTER_MAP,
                default => [],
            };
        }

        $escapedText = $text;
        foreach ($characters as $from => $to) {
            $escapedText = str_replace(mb_strtolower($from), mb_strtolower($to), $escapedText);
            $escapedText = str_replace(mb_strtoupper($from), mb_strtoupper($to), $escapedText);
        }

        return $escapedText;
    }
}
