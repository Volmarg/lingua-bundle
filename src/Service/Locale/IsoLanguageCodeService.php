<?php

namespace Lingua\Service\Locale;

class IsoLanguageCodeService
{
    /**
     * Mapping created from {@link https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes}
     */
    public const ISO_THREE_DIGIT_TO_ISO_TWO_DIGIT = [
        "abk" => "ab",
        "aar" => "aa",
        "afr" => "af",
        "aka" => "ak",
        "sqi" => "sq",
        "amh" => "am",
        "ara" => "ar",
        "arg" => "an",
        "hye" => "hy",
        "asm" => "as",
        "ava" => "av",
        "ave" => "ae",
        "aym" => "ay",
        "aze" => "az",
        "bam" => "bm",
        "bak" => "ba",
        "eus" => "eu",
        "bel" => "be",
        "ben" => "bn",
        "bis" => "bi",
        "bos" => "bs",
        "bre" => "br",
        "bul" => "bg",
        "mya" => "my",
        "cat" => "ca",
        "cha" => "ch",
        "che" => "ce",
        "nya" => "ny",
        "zho" => "zh",
        "chu" => "cu",
        "chv" => "cv",
        "cor" => "kw",
        "cos" => "co",
        "cre" => "cr",
        "hrv" => "hr",
        "ces" => "cs",
        "dan" => "da",
        "div" => "dv",
        "nld" => "nl",
        "dzo" => "dz",
        "eng" => "en",
        "epo" => "eo",
        "est" => "et",
        "ewe" => "ee",
        "fao" => "fo",
        "fij" => "fj",
        "fin" => "fi",
        "fra" => "fr",
        "fry" => "fy",
        "ful" => "ff",
        "gla" => "gd",
        "glg" => "gl",
        "lug" => "lg",
        "kat" => "ka",
        "deu" => "de",
        "ell" => "el",
        "kal" => "kl",
        "grn" => "gn",
        "guj" => "gu",
        "hat" => "ht",
        "hau" => "ha",
        "heb" => "he",
        "her" => "hz",
        "hin" => "hi",
        "hmo" => "ho",
        "hun" => "hu",
        "isl" => "is",
        "ido" => "io",
        "ibo" => "ig",
        "ind" => "id",
        "ina" => "ia",
        "ile" => "ie",
        "iku" => "iu",
        "ipk" => "ik",
        "gle" => "ga",
        "ita" => "it",
        "jpn" => "ja",
        "jav" => "jv",
        "kan" => "kn",
        "kau" => "kr",
        "kas" => "ks",
        "kaz" => "kk",
        "khm" => "km",
        "kik" => "ki",
        "kin" => "rw",
        "kir" => "ky",
        "kom" => "kv",
        "kon" => "kg",
        "kor" => "ko",
        "kua" => "kj",
        "kur" => "ku",
        "lao" => "lo",
        "lat" => "la",
        "lav" => "lv",
        "lim" => "li",
        "lin" => "ln",
        "lit" => "lt",
        "lub" => "lu",
        "ltz" => "lb",
        "mkd" => "mk",
        "mlg" => "mg",
        "msa" => "ms",
        "mal" => "ml",
        "mlt" => "mt",
        "glv" => "gv",
        "mri" => "mi",
        "mar" => "mr",
        "mah" => "mh",
        "mon" => "mn",
        "nau" => "na",
        "nav" => "nv",
        "nde" => "nd",
        "nbl" => "nr",
        "ndo" => "ng",
        "nep" => "ne",
        "nor" => "no",
        "nob" => "nb",
        "nno" => "nn",
        "iii" => "ii",
        "oci" => "oc",
        "oji" => "oj",
        "ori" => "or",
        "orm" => "om",
        "oss" => "os",
        "pli" => "pi",
        "pus" => "ps",
        "fas" => "fa",
        "pol" => "pl",
        "por" => "pt",
        "pan" => "pa",
        "que" => "qu",
        "ron" => "ro",
        "roh" => "rm",
        "run" => "rn",
        "rus" => "ru",
        "sme" => "se",
        "smo" => "sm",
        "sag" => "sg",
        "san" => "sa",
        "srd" => "sc",
        "srp" => "sr",
        "sna" => "sn",
        "snd" => "sd",
        "sin" => "si",
        "slk" => "sk",
        "slv" => "sl",
        "som" => "so",
        "sot" => "st",
        "spa" => "es",
        "sun" => "su",
        "swa" => "sw",
        "ssw" => "ss",
        "swe" => "sv",
        "tgl" => "tl",
        "tah" => "ty",
        "tgk" => "tg",
        "tam" => "ta",
        "tat" => "tt",
        "tel" => "te",
        "tha" => "th",
        "bod" => "bo",
        "tir" => "ti",
        "ton" => "to",
        "tso" => "ts",
        "tsn" => "tn",
        "tur" => "tr",
        "tuk" => "tk",
        "twi" => "tw",
        "uig" => "ug",
        "ukr" => "uk",
        "urd" => "ur",
        "uzb" => "uz",
        "ven" => "ve",
        "vie" => "vi",
        "vol" => "vo",
        "wln" => "wa",
        "cym" => "cy",
        "wol" => "wo",
        "xho" => "xh",
        "yid" => "yi",
        "yor" => "yo",
        "zha" => "za",
        "zul" => "zu",
    ];

    /**
     * Returns 3-digit ISO country code based on 2-digit code, if no code could be detected then NULL
     * will be returned
     *
     * @param string $twoDigitCode
     *
     * @return string|null
     */
    public function getThreeDigitForTwoDigit(string $twoDigitCode): ?string
    {
        $mapping = array_combine(
            array_values(self::ISO_THREE_DIGIT_TO_ISO_TWO_DIGIT),
            array_keys(self::ISO_THREE_DIGIT_TO_ISO_TWO_DIGIT)
        );

        return $mapping[strtolower($twoDigitCode)] ?? null;
    }
}