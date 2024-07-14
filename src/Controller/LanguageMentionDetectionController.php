<?php

namespace Lingua\Controller;

use Exception;
use Lingua\Dto\LanguageDetection\WordsFilterResultDto;
use Lingua\Service\Locale\LocaleLanguageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * This class handles checking which languages were mentioned in given data set
 */
class LanguageMentionDetectionController extends AbstractController
{
    private const MINIMAL_STRING_SIMILARITY_PERCENTAGE = 85;

    /**
     * @var LocaleLanguageService $localeLanguageService
     */
    private LocaleLanguageService $localeLanguageService;

    /**
     * Will return language codes that are supported for "mentions" detection,
     * this solves the issue where some weird languages mention are detected, be it:
     * - "Fulah",
     * - "Kom",
     * - "Root",
     *
     * The project won't support any african languages etc. thus limiting supported iso codes
     *
     * @return array
     */
    private function getSupportedLanguageIsoCodes(): array
    {
        return [
            'sq',    'aln',   'arq',   'en_US', 'es_419', 'en',    'ar', 'shu', 'arc',     'en_AU', 'de_AT', 'bar',
            'be',    'bs',    'pt_BR', 'en_GB', 'bg',     'zh',    'zh_Hant',   'zh_Hans', 'lzh',   'hr',    'ce',
            'cs',    'dak',   'nds',   'sli',   'da',     'dz',    'et',        'es_ES',   'pt_PT', 'fi',    'fr',
            'el',    'kl',    'ka',    'haw',   'he',     'hi',    'hif',       'es',      'id',    'ga',    'is',
            'ja',    'en_CA', 'fr_CA', 'kk',    'ko',     'lt',    'lb',        'la',      'lv',    'mk',    'es_MX',
            'ro_MD', 'ne',    'nl',    'de',    'no',     'nb',    'nn',        'pfl',     'fa',    'pl',    'pt',
            'prg',   'ru',    'ro',    'rue',   'sr',     'sh',    'sk',        'sl',      'sco',   'gd',    'fr_CH',
            'gsw',   'sv',    'th',    'uk',    'cy',     'vec',   'hu',        'vi',      'it',    'zea',
        ];
    }

    /**
     * @param LocaleLanguageService $localeLanguageService
     */
    public function __construct(
        LocaleLanguageService $localeLanguageService,
    )
    {
        $this->localeLanguageService = $localeLanguageService;
    }

    /**
     * Will return array of found mentioned languages
     *
     * @param string      $haystack
     * @param string      $usedLocale        - this locale is used to find lanaguages mentions in the the text
     * @param string|null $returnedLocale    - this locale is used to return languages mentions in given local
     *                                       example:
     *                                       - search locale is PL, and finds `Angielski`,
     *                                       - return locale is EN, and returns `English` instead,
     * @return array
     * @throws Exception
     */
    public function getMentionedLanguages(string $haystack, string $usedLocale, ?string $returnedLocale = null): array
    {
        // not converting if target and searched are same, will allow to save some time on execution
        $convertLocale = true;
        if( is_null($returnedLocale) ){
            $returnedLocale = $usedLocale;
            $convertLocale  = false;
        }
        $languages         = $this->localeLanguageService->getLanguagesForLocale($usedLocale);
        $matchingLanguages = [];

        $wordsFilterResult = $this->filterWordsAndLanguages($haystack, $languages);
        foreach ($wordsFilterResult->getLanguages() as $languageCode => $languageName) {
            foreach ($wordsFilterResult->getWords() as $word) {

                // no need to check for time-consuming similarity check if the word already contains the language name
                $hasLanguage = str_contains($word, $languageName);

                if ($hasLanguage && !$convertLocale) {
                    $matchingLanguages[] = $languageName;
                    continue;
                }

                if ($hasLanguage && $convertLocale) {
                    $matchingLanguages[] = $this->localeLanguageService->convertLocaleToLanguageName($languageCode, $returnedLocale);
                    continue;
                }

                similar_text($word, $languageName, $similarityPercentage);

                $isMatchingMinSimilarityThreshold = ($similarityPercentage >= self::MINIMAL_STRING_SIMILARITY_PERCENTAGE);
                if (!$isMatchingMinSimilarityThreshold) {
                    continue;
                }

                if ($convertLocale) {
                    $matchingLanguages[] = $this->localeLanguageService->convertLocaleToLanguageName($languageCode, $returnedLocale);
                    continue;
                }
                $matchingLanguages[] = $languageName;
            }
        }

        $uniqueLanguages = array_values(array_unique($matchingLanguages));
        return $uniqueLanguages;
    }

    /**
     * Going through every single work in text, otherwise won't be able to use {@see \similar_text()},
     * This method needs to filter out as many words and languages as possible else the process will take longer and longer
     *
     * @param string $haystack
     * @param array  $languages
     *
     * @return WordsFilterResultDto
     */
    private function filterWordsAndLanguages(string $haystack, array $languages): WordsFilterResultDto
    {
        $filteredWords     = [];
        $filteredHaystack  = strip_tags($haystack);
        $wordsInText       = array_filter(array_unique(preg_split("/(?<=\w)\b\s*/", $filteredHaystack))); // split by spacebars etc.
        $matchingLanguages = [];

        foreach ($wordsInText as $word) {
            $normalizedWord = preg_replace("#\W#", "", $word);

            // checking if word has part of language name in it
            foreach ($languages as $languageCode => $language) {
                if (!in_array($languageCode, $this->getSupportedLanguageIsoCodes())) {
                    continue;
                }

                $languageNameLength     = strlen($language);
                $halfLanguageNameLength = round($languageNameLength / 2);
                $halfLanguageName       = substr($language, 0 , $halfLanguageNameLength);

                $containsPartOfLanguageName = str_contains($normalizedWord, $halfLanguageName);
                if ($containsPartOfLanguageName) {
                    $matchingLanguages[$languageCode] = $language;
                    $filteredWords[]                  = $normalizedWord;
                    continue 2;
                }

            }
        }

        $filterResult = new WordsFilterResultDto(
            array_unique($filteredWords),
            array_unique($matchingLanguages),
        );

        return $filterResult;
    }

}