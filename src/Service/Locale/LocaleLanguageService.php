<?php

namespace Lingua\Service\Locale;

use Exception;
use Lingua\Kernel;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Handles providing languages names in given locale
 * This logic utilizes the:
 * @link https://github.com/umpirsky/language-list
 */
class LocaleLanguageService
{
    private static ?string $INCLUSION_PATH = null;

    /**
     * Contains of given structure:
     * [
     *   "locale" => [<Languages>]
     * ]
     *
     * This is not only helpful as an optimization but also solves issue when trying to `include_once`
     * same language multiple times, it returns bool if such thing happens (dunno why)
     *
     * @var array $includedLanguages
     */
    private array $includedLanguages = [];


    /**
     * Will build the locale language file path
     *
     * @param string $locale
     * @return string
     */
    private function buildLocaleLanguageFilePath(string $locale): string
    {
        return "/{$locale}/language.json";
    }

    /**
     * @var KernelInterface $kernel
     */
    private KernelInterface $kernel;

    /**
     * @var LoggerInterface $logger
     */
    private LoggerInterface $logger;

    /**
     * @param Kernel          $kernel
     * @param LoggerInterface $logger
     */
    public function __construct(KernelInterface $kernel, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->kernel = $kernel;
    }

    /**
     * For more information see {@see LanguageMentionDetectionController::getMentionedLanguages()} (param: $localeUsedToReturn)
     *
     * @param string $languageCode
     * @param string $targetLocale
     *
     * @return string
     * @throws Exception
     */
    public function convertLocaleToLanguageName(string $languageCode, string $targetLocale): string
    {
        $languagesForLocale = $this->getLanguagesForLocale($targetLocale);
        return $languagesForLocale[$languageCode];
    }

    /**
     * For more information see {@see LanguageMentionDetectionController::getMentionedLanguages()} (param: $localeUsedToReturn)
     *
     * @param string $languageName
     * @param string $targetLocale
     *
     * @return string|null
     * @throws Exception
     */
    public function convertLanguageNameToLocale(string $languageName, string $targetLocale): ?string
    {
        $languagesForLocale = $this->getLanguagesForLocale($targetLocale);
        $matchingKey        = array_search($languageName, $languagesForLocale);
        if (empty($matchingKey)) {
            return false;
        }
        return $matchingKey;
    }

    /**
     * Will return language package location - this is required due to how the {@see Kernel::getProjectDir()}
     * path changes when the bundle is included in other project
     *
     * @return string
     */
    private function getLanguagePackageLocaleInclusionPath(): string
    {
        if (!is_null(self::$INCLUSION_PATH)) {
            return self::$INCLUSION_PATH;
        }

        $finder      = new Finder();
        $projectRoot = $this->kernel->getProjectDir();
        $scannedDir  = $projectRoot . DIRECTORY_SEPARATOR . "vendor";

        $finder->directories()->in($scannedDir)->name("language-list");
        $folderPath = null;
        foreach($finder as $directory){
            $folderPath = $directory->getRealPath();
        }

        if( empty($folderPath) ){
            throw new NotFoundHttpException("`umpirsky/language-list` package path could not be found");
        }

        self::$INCLUSION_PATH = $folderPath . DIRECTORY_SEPARATOR . "data";

        return self::$INCLUSION_PATH;
    }

    /**
     * Will return languages for locale
     *
     * @param string $baseLocale
     *
     * @return array - key is "locale" (of language in given "baseLocale"), value is "language" (in "baseLocale")
     *                 so for example:
     *                 - baseLocale = "pl", language = "Polski", locale = "pl"
     *                 - baseLocale = "en", language = "Polish", locale = "pl"
     *
     * @throws Exception
     */
    public function getLanguagesForLocale(string $baseLocale): array
    {
        if( array_key_exists($baseLocale, $this->includedLanguages) ){
            return $this->includedLanguages[$baseLocale];
        }

        $maxRetry = 20;
        $retryNum = 0;
        $localeLanguagePath = $this->getLanguagePackageLocaleInclusionPath() . $this->buildLocaleLanguageFilePath($baseLocale);
        $arrayOfLanguagesForLocale = [];

        /**
         * That looks wicked but there is some weird issues with concurrency,
         * If there is one request after another then proper language is returned, but with concurrent
         * call suddenly no languages can be found.
         *
         * Could not recreate it, no idea what is happening, so decide to at least
         * make this dirty solution of obtaining the languages multiple times until giving up
         */
        while (empty($arrayOfLanguagesForLocale) && ($retryNum < $maxRetry)) {
            if (!file_exists($localeLanguagePath)) {
                $this->logger->warning("Given locale is not supported: {$baseLocale}");

                return [];
            }

            $jsonOfLanguagesForLocale  = file_get_contents($localeLanguagePath);
            $arrayOfLanguagesForLocale = json_decode($jsonOfLanguagesForLocale, true);
            if( JSON_ERROR_NONE !== json_last_error() ){
                throw new Exception("Could not decode the languages json for locale: {$baseLocale}. Got json error: " . json_last_error_msg());
            }

            if (!empty($arrayOfLanguagesForLocale)) {
                $this->includedLanguages[$baseLocale] = $arrayOfLanguagesForLocale;
            }

            $retryNum++;
        }


        return $arrayOfLanguagesForLocale;
    }

}