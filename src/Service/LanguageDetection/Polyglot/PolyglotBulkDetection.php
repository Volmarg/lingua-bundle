<?php

namespace Lingua\Service\LanguageDetection\Polyglot;

use Exception;
use Lingua\Dto\LanguageInformationDto;
use Lingua\Service\LanguageDetection\Polyglot;
use Lingua\Service\Locale\IsoLanguageCodeService;
use Lingua\Service\Locale\LocaleLanguageService;
use Psr\Log\LoggerInterface;

/**
 * Handles bulk language detection (checking languages in multiple files at once)
 */
class PolyglotBulkDetection
{
    /**
     * The executed command which bulk detects the languages used in the provided text
     *
     * @param array $filePaths
     *
     * @return string
     * @throws Exception
     */
    private function bulkLanguageDetectionCommand(array $filePaths): string
    {
        Polyglot::isPolyglotInstalled();
        Polyglot::isRunningWithoutIssues();

        $errorMuting     = " 2> /dev/null";
        $partialCommands = [];
        foreach ($filePaths as $filePath) {
            $partialCommands[] = "polyglot detect --input {$filePath} {$errorMuting}";
        }

        /**
         * - `&` will execute the command in background (parallel),
         * - `wait` tells the shell to wait for all `parallel` commands to be done,
         */
        $fullCommand = implode(" & ", $partialCommands) . " & wait";

        return $fullCommand;
    }

    public function __construct(
        private readonly PolyglotResultParser  $polyglotResultParser,
        private readonly PolyglotFilesHandler  $polyglotFilesHandler,
        private readonly LocaleLanguageService $localeLanguageService,
        private readonly LoggerInterface       $logger,
        private readonly string                $failedDetectionsFolder,
        private readonly IsoLanguageCodeService $isoCountryCodeService
    ){
    }

    /**
     * @param array $texts
     *
     * @return Array<LanguageInformationDto[]>
     * @throws Exception
     */
    public function detect(array $texts): array
    {
        $usedFilesPaths = $this->prepareFilePaths($texts);

        $translationsResultsString = shell_exec($this->bulkLanguageDetectionCommand($usedFilesPaths));
        $translationsResultsArray  = array_filter(explode("\n", $translationsResultsString)); // remove empty lines (polyglot internal issue)

        $foundLanguages = $this->parseDetectionResults($translationsResultsArray, $texts);

        $this->removeInputFiles($usedFilesPaths);

        return $foundLanguages;
    }

    /**
     * Will prepare the input (--input) file paths that will be used for bulk detection
     *
     * @param array $texts
     *
     * @return array
     */
    private function prepareFilePaths(array $texts): array
    {
        $usedFilesPaths = [];
        foreach ($texts as $uniqueId => $text) {
            $shortText                = $this->shortenAndNormalizeText($text);

            $filePath                 = $this->polyglotFilesHandler->getInputFilePath($uniqueId);
            $usedFilesPaths[$uniqueId] = $filePath;

            $bytesInserted = file_put_contents($filePath, $shortText);
            if (is_bool($bytesInserted)) {
                $this->logger->critical("Could not create the file with text for language detection under path: {$filePath}");
            }
        }

        return $usedFilesPaths;
    }

    /**
     * Will parse the detection results:
     * Returns array:
     * - <key> unique id provided for detection (cannot rely on md5 due to concurrent run breaking things),,
     * - <value> array of detected languages for text (<key>)
     *
     * @param array $translationsResultsArray
     * @param array $texts
     *
     * @return array
     * @throws Exception
     */
    private function parseDetectionResults(array $translationsResultsArray, array $texts): array
    {
        $foundLanguages = [];

        foreach ($texts as $uniqueId => $text) {
            $filePath  = $this->polyglotFilesHandler->getInputFilePath($uniqueId);
            $shortText = file_get_contents($filePath);

            // this can probably happen if there is some exception being thrown,
            if (empty($shortText)) {
                $this->logger->warning("No input file exists, or failed detecting translation for text", [
                    "text" => $text,
                ]);
                continue;
            }

            $translationResult = null;
            foreach ($translationsResultsArray as $singleTranslationResult) {
                if (str_contains($singleTranslationResult, $shortText)) {
                    $translationResult = $singleTranslationResult;
                    break;
                }
            }

            if (empty($translationResult)) {
                $this->handleLanguageDetectionFailure($filePath);
                continue;
            }

            $longText = $text;
            $dto      = $this->buildLanguageInformationDto($longText, $filePath, $translationResult);
            if (empty($dto)) {
                continue;
            }

            $dto->setUniqueId($uniqueId);
            $foundLanguages[$dto->getUniqueId()][] = $dto;
        }

        return $foundLanguages;
    }

    /**
     * Will remove the temporary files that were used for language detection
     *
     * @param array $filePaths
     *
     * @return void
     */
    private function removeInputFiles(array $filePaths): void
    {
        foreach ($filePaths as $filePath) {
            if (file_exists($filePath)) {
                @unlink($filePath); // don't care about errors, files are removed on reboot / etc.
            }
        }
    }

    /**
     * Will handle case where no languages could get detected
     *
     * @param string $inputFilePath
     *
     * @return void
     */
    private function handleLanguageDetectionFailure(string $inputFilePath): void
    {
        $fileName                = basename($inputFilePath);
        $failedTranslationFolder = $this->failedDetectionsFolder . uniqid();
        $failedTranslationFile   = "{$failedTranslationFolder}/{$fileName}";

        mkdir($failedTranslationFolder);
        rename($inputFilePath, $failedTranslationFile);

        $this->logger->critical("Could not detect language for provided input file", [
            "filePath" => $failedTranslationFile,
        ]);
    }

    /**
     * Will shorten the text used for language detection,
     * - performs additional formatting like removing new lines etc.
     *
     * @param string $text
     *
     * @return string
     */
    private function shortenAndNormalizeText(string $text): string
    {
        // prevent multiple languages detection
        $normalizedText = str_replace("\n", " ", $text);

        // get rid of html tags
        $normalizedText = strip_tags($normalizedText);

        // remove special characters that break language detection, leave only letters & numbers
        $normalizedText = preg_replace("#[^\w]#", " ", $normalizedText);

        // remove multiple spacebars - causing language detection issues
        $normalizedText = preg_replace("#[ ]{1,}#", " ", $normalizedText);

        // polyglot returns wrong results if last line is not a new line
        $normalizedText = $normalizedText . "\n";

        // use shorter text to speed-up language recognition, mb_substring is needed for special characters in each language
        $shortText = trim(mb_substr($normalizedText, 0, Polyglot::MAX_TEXT_LENGTH));

        return $shortText;
    }

    /**
     * @param string $text
     * @param string $filePath
     * @param string $translationResult
     *
     * @return LanguageInformationDto|null
     * @throws Exception
     */
    private function buildLanguageInformationDto(string $text, string $filePath, string $translationResult): ?LanguageInformationDto
    {
        $languageForString = $this->polyglotResultParser->getLanguageFromDetectionResultRow($translationResult);

        // this can probably happen if there is some exception being thrown, as sometimes polyglot prints "could not detect language"
        if (empty($languageForString)) {
            $this->handleLanguageDetectionFailure($filePath);
            return null;
        }

        $localeName = $this->localeLanguageService->convertLanguageNameToLocale($languageForString->getLanguage(), "en"); // this will probably never get translated anyway thus hard-coding
        if (empty($localeName)) {
            $this->logger->critical("Could not detect locale for language: {$languageForString->getLanguage()}", [
                'text'              => $text,
                'translationResult' => $translationResult,
            ]);
            return null;
        }

        $threeDigitCountryCode = $this->isoCountryCodeService->getThreeDigitForTwoDigit($localeName);

        $dto = new LanguageInformationDto();
        $dto->setLanguageName($languageForString->getLanguage());
        $dto->setTwoDigitLanguageCode($localeName);
        $dto->setThreeDigitLanguageCode($threeDigitCountryCode);
        $dto->setText($text);

        return $dto;
    }

}