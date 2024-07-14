<?php

namespace Lingua\Service\LanguageDetection\Polyglot;

/**
 * Handles any kind of files based logic related to the polyglot
 */
class PolyglotFilesHandler
{
    /**
     * Will set the path of the file in which the text used for language detection get stored
     * It has to be done this way since the cli allows providing only the file which has the text,
     * Direct text input is not supported
     */
    public function getInputFilePath(string $fileName): string
    {
        return $this->getInputFolder() . $fileName . "." . $this->getInputFileExtension();
    }

    /**
     * @return string
     */
    public function getInputFolder(): string
    {
        return "/tmp/polyglot/";
    }

    /**
     * @return string
     */
    public function getInputFileExtension(): string
    {
        return "txt";
    }

}