<?php

namespace Lingua\Dto;

/**
 * Dto representing the language information
 */
class LanguageInformationDto
{
    /**
     * @var string|null $languageName
     */
    private ?string $languageName;

    /**
     * @var string $twoDigitLanguageCode
     */
    private string $twoDigitLanguageCode;

    /**
     * @var string|null $threeDigitLanguageCode
     */
    private ?string $threeDigitLanguageCode;

    /**
     * @var array $mentionedLanguages
     */
    private array $mentionedLanguages = [];

    /**
     * @var string $text
     */
    private string $text;

    /**
     * @var string $uniqueId
     */
    private string $uniqueId;

    /**
     * @return string|null
     */
    public function getLanguageName(): ?string
    {
        return $this->languageName;
    }

    /**
     * @param string|null $languageName
     */
    public function setLanguageName(?string $languageName): void
    {
        $this->languageName = $languageName;
    }

    /**
     * @return string
     */
    public function getTwoDigitLanguageCode(): string
    {
        return $this->twoDigitLanguageCode;
    }

    /**
     * @param string $twoDigitLanguageCode
     */
    public function setTwoDigitLanguageCode(string $twoDigitLanguageCode): void
    {
        $this->twoDigitLanguageCode = $twoDigitLanguageCode;
    }

    /**
     * @return array
     */
    public function getMentionedLanguages(): array
    {
        return $this->mentionedLanguages;
    }

    /**
     * @param array $mentionedLanguages
     */
    public function setMentionedLanguages(array $mentionedLanguages): void
    {
        $this->mentionedLanguages = $mentionedLanguages;
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * @param string $text
     */
    public function setText(string $text): void
    {
        $this->text = $text;
    }

    /**
     * @return string|null
     */
    public function getThreeDigitLanguageCode(): ?string
    {
        return $this->threeDigitLanguageCode;
    }

    /**
     * @param string|null $threeDigitLanguageCode
     */
    public function setThreeDigitLanguageCode(?string $threeDigitLanguageCode): void
    {
        $this->threeDigitLanguageCode = $threeDigitLanguageCode;
    }

    /**
     * @return string
     */
    public function getUniqueId(): string
    {
        return $this->uniqueId;
    }

    /**
     * @param string $uniqueId
     */
    public function setUniqueId(string $uniqueId): void
    {
        $this->uniqueId = $uniqueId;
    }

}
