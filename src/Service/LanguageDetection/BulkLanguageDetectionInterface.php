<?php

namespace Lingua\Service\LanguageDetection;

use Lingua\Dto\LanguageInformationDto;

/**
 * Ensures that language detection can be made in bulk
 */
interface BulkLanguageDetectionInterface
{
    /**
     * Will return data about language for given string
     *
     * @param string[] $texts
     *
     * @return LanguageInformationDto[]
     */
    public function getBulkLanguageInformation(array $texts): array;

}