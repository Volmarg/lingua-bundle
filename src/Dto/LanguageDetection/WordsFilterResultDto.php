<?php

namespace Lingua\Dto\LanguageDetection;

class WordsFilterResultDto
{
    public function __construct(
        private array $words,
        private array $languages,
    ){}

    /**
     * @return array
     */
    public function getWords(): array
    {
        return $this->words;
    }

    /**
     * @param array $words
     */
    public function setWords(array $words): void
    {
        $this->words = $words;
    }

    /**
     * @return array
     */
    public function getLanguages(): array
    {
        return $this->languages;
    }

    /**
     * @param array $languages
     */
    public function setLanguages(array $languages): void
    {
        $this->languages = $languages;
    }

}