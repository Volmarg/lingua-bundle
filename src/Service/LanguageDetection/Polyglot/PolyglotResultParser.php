<?php

namespace Lingua\Service\LanguageDetection\Polyglot;

use Lingua\Dto\LanguageDetection\Polyglot\StringDetectionResultDto;

/**
 * Handles parsing polyglot results (data returned by the polyglot itself)
 */
class PolyglotResultParser
{
    /**
     * Will find the language for returned detection row
     *
     * @param string $translationResult
     *
     * @return StringDetectionResultDto|null
     */
    public function getLanguageFromDetectionResultRow(string $translationResult): ?StringDetectionResultDto
    {
        $langAndTextSplitChar = " ";
        $minSplitChars        = 6;  // this exists to prevent catching standard spacebars etc.
        $maxSplitChars        = 30; // this exists just to limit attempts of getting the language information

        // This is needed because for some detection there are more spaces, for other less
        $splitString = str_repeat($langAndTextSplitChar, $minSplitChars);
        for ($splitCount = $minSplitChars; $splitCount <= $maxSplitChars; $splitCount++) {
            $splitString .= $langAndTextSplitChar;

            // this spacebars explode IS OK.it's just how polyglot separates detected language next to the text used for detection
            $languageInfoArray = explode($splitString, $translationResult);
            $languageInfoArray = array_filter($languageInfoArray);

            $language          = $languageInfoArray[0] ?? null;
            $checkedString     = $languageInfoArray[1] ?? null;
            if (!is_null($checkedString)) {
                $checkedString = trim($checkedString);
                break;
            }
        }

        if (
                empty($language)
            ||  empty($checkedString)
        ) {
            return null;
        }

        return new StringDetectionResultDto($language, $checkedString);
    }

}