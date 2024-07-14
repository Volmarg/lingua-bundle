<?php

namespace Lingua\Controller;

use Exception;
use Lingua\Dto\LanguageInformationDto;
use Lingua\Service\LanguageDetection\Polyglot;
use Lingua\Service\Locale\LocaleLanguageService;
use Psr\Log\LoggerInterface;
use TypeError;

/**
 * Controller for language detection
 */
class LanguageDetectionController
{

    /**
     * @var LanguageMentionDetectionController $languageMentionDetectionController
     */
    private LanguageMentionDetectionController $languageMentionDetectionController;

    /**
     * @var LoggerInterface $logger
     */
    private LoggerInterface $logger;

    /**
     * @var LocaleLanguageService $localeLanguageService
     */
    private LocaleLanguageService $localeLanguageService;

    /**
     * @param LanguageMentionDetectionController $languageMentionDetectionController
     * @param LocaleLanguageService              $localeLanguageService
     * @param LoggerInterface                    $logger
     * @param Polyglot                           $polyglot
     */
    public function __construct(
        LanguageMentionDetectionController $languageMentionDetectionController,
        LocaleLanguageService              $localeLanguageService,
        LoggerInterface                    $logger,
        private readonly Polyglot          $polyglot,
    )
    {
        $this->logger                             = $logger;
        $this->localeLanguageService              = $localeLanguageService;
        $this->languageMentionDetectionController = $languageMentionDetectionController;
    }

    /**
     * Will return gathered language information for text:
     * - locale in which the text has been created,
     * - name of language in which the text has been created (if possible),
     * - languages mentioned in the text (for example text in german language can mention "english, polish" `words`)
     *
     * @param string      $text
     * @param string|null $targetLocale   - if any languages are found, these will be then returned in target locale
     *                                    if this is not provided then languages are return in provided text locale
     *
     * @return LanguageInformationDto | null
     * @throws Exception
     */
    public function getLanguageInformation(string $text, ?string $targetLocale = null): ?LanguageInformationDto
    {
        $dto = null;

        try{
            if (empty($text)) {
                return null;
            }

            $dto = $this->polyglot->getLanguageInformation($text);
        }catch(Exception | TypeError $e){
            $this->logger->warning("Could not fetch data via polyglot, exception was thrown", [
                "exception"     => [
                    "message" => $e->getMessage(),
                    "stack"   => $e->getTraceAsString(),
                ]
            ]);
            // nothing
        }

        if( !is_null($dto) ){
            if( !empty($targetLocale) ){
                $targetLanguage = $this->localeLanguageService->convertLocaleToLanguageName($dto->getTwoDigitLanguageCode(), $targetLocale);
                $dto->setLanguageName($targetLanguage);
            }

            $mentionedLanguages = $this->languageMentionDetectionController->getMentionedLanguages($text, $dto->getTwoDigitLanguageCode(), $targetLocale);
            $dto->setMentionedLanguages($mentionedLanguages);
        }

        return $dto;
    }

    /**
     * Will return gathered language information for texts:
     * - locale in which the text has been created,
     * - name of language in which the text has been created (if possible),
     * - languages mentioned in the text (for example text in german language can mention "english, polish" `words`)
     *
     * @param array       $texts
     * @param string|null $targetLocale   - if any languages are found, these will be then returned in target locale
     *                                      if this is not provided then languages are return in provided text locale
     *
     * @return LanguageInformationDto[]
     * @throws Exception
     */
    public function getLanguagesInformation(array $texts, ?string $targetLocale = null): array
    {
        $uniqueKeyToText = [];
        foreach ($texts as $index => $text) {
            // md5 is not unique enough, if there are concurrent runs then it becomes messy
            $key = uniqid();
            $uniqueKeyToText[$key] = $text;

            unset($texts[$index]);
            $texts[$key] = $text;
        }

        $languageInformations      = [];
        $detectionInformationArray = $this->polyglot->getBulkLanguageInformation($texts);
        foreach ($detectionInformationArray as $uniqueId => $languagesInformation) {

            $text = $uniqueKeyToText[$uniqueId];
            foreach($languagesInformation as $languageInformation){
                if (!empty($targetLocale)) {
                    $targetLanguage = $this->localeLanguageService->convertLocaleToLanguageName($languageInformation->getTwoDigitLanguageCode(), $targetLocale);
                    $languageInformation->setLanguageName($targetLanguage);
                }

                $mentionedLanguages = $this->languageMentionDetectionController->getMentionedLanguages(
                    $text,
                    $languageInformation->getTwoDigitLanguageCode(),
                    $targetLocale
                );
                $languageInformation->setMentionedLanguages($mentionedLanguages);

                $languageInformations[] = $languageInformation;
            }

        }

        return $languageInformations;
    }

}