<?php

namespace Lingua\Service\LanguageDetection;

use Exception;
use Lingua\Dto\LanguageInformationDto;
use Lingua\Service\LanguageDetection\Polyglot\PolyglotBulkDetection;
use Lingua\Service\LanguageDetection\Polyglot\PolyglotFilesHandler;
use Lingua\Service\LanguageDetection\Polyglot\PolyglotResultParser;
use Lingua\Service\Locale\IsoLanguageCodeService;
use Lingua\Service\Locale\LocaleLanguageService;

/**
 * This service relies on:
 * - @link https://polyglot.readthedocs.io/en/latest/index.html
 * - @link https://github.com/aboSamoor/polyglot
 *
 * Info - some other tools that could be used for the language detection in future:
 * - {@link https://stackoverflow.com/questions/39142778/python-how-to-determine-the-language}
 *
 * So it's being done of local machines
 */
class Polyglot implements LanguageDetectionInterface, BulkLanguageDetectionInterface
{
    // defines how long the text for language detection should be, in attempt to speed up the check time
    public const MAX_TEXT_LENGTH = 1000;

    /**
     * The executed command which detects the languages used in the provided text,
     * errors must be muted as sometimes even tho the language is detected properly
     * the app will throw some information like "text is too short" and that causes
     * issues with parsing the output information
     *
     * @param string $filePath
     *
     * @return string
     *
     * @throws Exception
     */
    private function languageDetectionCommand(string $filePath): string
    {
        $this->isPolyglotInstalled();
        $this->isRunningWithoutIssues();

        $errorMuting = " 2> /dev/null";
        return "polyglot detect --input " . $filePath . $errorMuting;
    }

    /**
     * @param LocaleLanguageService  $localeLanguageService
     * @param PolyglotBulkDetection  $polyglotBulkDetection
     * @param PolyglotResultParser   $polyglotResultParser
     * @param PolyglotFilesHandler   $polyglotFilesHandler
     * @param IsoLanguageCodeService $isoCountryCodeService
     */
    public function __construct(
        private LocaleLanguageService          $localeLanguageService,
        private readonly PolyglotBulkDetection $polyglotBulkDetection,
        private readonly PolyglotResultParser  $polyglotResultParser,
        private readonly PolyglotFilesHandler  $polyglotFilesHandler,
        private readonly IsoLanguageCodeService $isoCountryCodeService
    )
    {}

    /**
     * Will do simple call to binary file and check if it produces any errors,
     * - had a case when some Python dependencies were missing
     *
     * Known issues:
     * - "No module named 'icu'"
     * -- pip install pyicu
     *
     * @throws Exception
     */
    public static function isRunningWithoutIssues(): void
    {
        // 2>&1 will allow catching errors output
         exec("polyglot 2>&1", $results);
         $fullErrorString = implode(" ", $results);

        // this means that there is some error, because calling polyglot without arguments normally produces this error
        if (!str_contains($fullErrorString, "Too few arguments")) {
            throw new Exception("Polyglot cannot be executed. Got some error. See: " . $fullErrorString);
        }
    }

    /**
     * Will check if the language detection package is installed at all
     * This is getting called inside `services.yaml` to ensure it's called only once
     * as the `shell_exec` calls are time-wise expensive
     *
     * @throws Exception
     */
    public static function isPolyglotInstalled(): void
    {
        $whichResult = shell_exec("which polyglot");
        if(empty($whichResult)){
            throw new Exception("Polyglot package is not installed. Install it following the information on: https://polyglot.readthedocs.io/en/latest/index.html");
        }
    }

    /**
     * {@inheritDoc}
     *
     * If the provided text supports multiple lines separated by the "\n",
     * then each language returns each own language detection result
     *
     * @throws Exception
     */
    public function getLanguageInformation(string $text): ?LanguageInformationDto
    {
        $shortText = substr(strip_tags($text), 0, self::MAX_TEXT_LENGTH);
        $uniqueId  = uniqid();

        $filePath = $this->polyglotFilesHandler->getInputFilePath($uniqueId);
        file_put_contents($filePath, $shortText);

        $detectionResult = shell_exec($this->languageDetectionCommand($filePath));
        $languagesRows   = array_filter(explode("\n", $detectionResult));  // polyglot returns wrong results if last line is not a new line
        $languages       = [];

        // rows - as there can be multiple languages detected
        foreach ($languagesRows as $languageRow) {
            $languageForString = $this->polyglotResultParser->getLanguageFromDetectionResultRow($languageRow);

            // this can probably happen if there is some exception being thrown, as sometimes polyglot prints "could not detect language"
            if (!empty($languageForString)) {
                $languages[] = $languageForString->getLanguage();
            }
        }

        if (empty($languages)) {
            return null;
        }

        $firstLanguage = $languages[array_key_first($languages)];
        $localeName    = $this->localeLanguageService->convertLanguageNameToLocale($firstLanguage, "en"); // this will probably never get translated anyway thus hard-coding
        if (empty($localeName)) {
            return null;
        }

        $threeDigitCountryCode = $this->isoCountryCodeService->getThreeDigitForTwoDigit($localeName);

        $dto = new LanguageInformationDto();
        $dto->setLanguageName($firstLanguage);
        $dto->setTwoDigitLanguageCode($localeName);
        $dto->setThreeDigitLanguageCode($threeDigitCountryCode);
        $dto->setUniqueId($uniqueId);

        unlink($filePath);

        return $dto;
    }

    /**
     * @param array $texts
     *
     * @return Array<LanguageInformationDto[]>
     * @throws Exception
     */
    public function getBulkLanguageInformation(array $texts): array
    {
        return $this->polyglotBulkDetection->detect($texts);
    }
}