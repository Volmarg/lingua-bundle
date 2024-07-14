<?php

namespace Lingua\Service\LanguageDetection;

use Lingua\Dto\LanguageInformationDto;

/**
 * Common logic for language detection services
 */
interface LanguageDetectionInterface
{
    /**
     * Will return data about language for given string
     *
     * @param string $text
     * @return LanguageInformationDto | null
     */
    public function getLanguageInformation(string $text): ?LanguageInformationDto;

}