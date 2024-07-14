<?php

namespace Lingua\Action\Debug;

use Exception;
use Lingua\Controller\LanguageDetectionController;
use Lingua\Controller\LanguageMentionDetectionController;
use Lingua\Service\LanguageDetection\Polyglot;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Helps debugging the code related to the {@see LanguageMentionDetectionController}
 */
class LanguageMentionDebugAction
{
    public function __construct(
        private LanguageMentionDetectionController   $languageMentionDetectionController,
        private readonly Polyglot                    $polyglot,
        private readonly LanguageDetectionController $languageDetectionController,
    ){}

    /**
     * Return found mentioned languages for provided text
     *
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/debug/other", name: "debug.other", methods: [Request::METHOD_GET])]
    public function debugOther(): JsonResponse
    {
        $text = '';
        $results = $this->languageMentionDetectionController->getMentionedLanguages($text, "en");

        return new JsonResponse([]);
    }

    /**
     * Return found mentioned languages for provided text
     *
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/debug/language-mention", name: "debug.language.mention", methods: [Request::METHOD_GET])]
    public function getMentionedLanguages(): JsonResponse
    {
        $text = '';
        $mentionedLanguages = $this->languageMentionDetectionController->getMentionedLanguages($text, "de", "en");

        return new JsonResponse($mentionedLanguages);
    }

    /**
     * Return found mentioned languages for provided text
     *
     * @return JsonResponse
     * @throws Exception
     */
    #[Route("/debug/bulk-detect-languages", name: "debug.bulk.detect.languages", methods: [Request::METHOD_GET])]
    public function bulkLanguageDetection(): JsonResponse
    {
        $texts = [];
        $results = $this->languageDetectionController->getLanguagesInformation($texts, "en");

        return new JsonResponse($results);
    }
}