<?php
/**
 * Created by PhpStorm.
 * User: Terry
 * Date: 10/24/2018
 * Time: 7:40 AM
 */

namespace Tops\sys;


class TStringTokenizer
{
    const defaultIgnoreWords = '';
    const ignoreWordsTranslationCode = 'tokenizer-ignored-words';
    const defaultRemoveChars = '?,!,.,:,;,-,&,_,(,),[,],{,}';
    const removeCharsTranslationCode = 'tokenizer-removed-chars';
    private $removeChars = array();
    private $ignoredWords = array();
    private $allowDuplicates = false;

    public function __construct($ignoreWords = null,$removeChars = null,$addComma = true)
    {
        if ($ignoreWords === null) {
            $ignoreWords = TLanguage::text(self::ignoreWordsTranslationCode, self::defaultIgnoreWords);
        }
        if ($removeChars == null) {
            $removeChars = TLanguage::text(self::removeCharsTranslationCode,self::defaultRemoveChars);
        }
        $this->ignoredWords = explode(',', $ignoreWords);
        $this->removeChars = explode(',',$removeChars);
        $this->allowDuplicates = false;
        if ($addComma) {
            $this->removeChars[] = ',';
            $this->removeChars[] = "'";
        }
    }

    /**
     * @var TStringTokenizer
     */
    private static $instance;
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new TStringTokenizer(); // use defaults
        }
        return self::$instance;
    }

    public static function setIgnoredWords($ignoreWords) {
        self::getInstance();
        self::$instance->ignoredWords =  explode(',',$ignoreWords);
    }

    public static function setRemoveChars($removeChars,$addComma=true) {
        self::getInstance();
        self::$instance->removeChars = explode(',',$removeChars);
        if ($addComma) {
            self::$instance[] = ',';
        }
    }

    public static function allowDuplicates() {
        self::getInstance();
        self::$instance->allowDuplicates = true;
    }

    public static function setParameters($ignoreWords,$removeChars)
    {
        self::$instance = new TStringTokenizer($ignoreWords, $removeChars);
    }

    public static function extractKeywords($string,$minLength=0) {
        return self::getInstance()->extractWords($string,$minLength);
    }

    private function addToWordList(array &$list,$word)
    {
        $word = trim($word);
        if (!(empty($word) || in_array($word,$list))) {
            $list[] = $word;
        }
    }

    function processWord($word,&$results,&$phrase)
    {
        $word = trim($word);
        if ($word === "\"") {  // single quote character, no word
            $startQuote = ($phrase === null);
            $endQuote = !$startQuote;
            $word = '';
        }
        else {
            $endQuote = substr($word, strlen($word) - 1) === "\"";
            $startQuote = substr($word, 0, 1) === "\"";

            if ($endQuote) {
                // trim trailing quote
                $word = trim(substr($word, 0, strlen($word) - 1));
            }
            if ($startQuote) {
                // trim leading quote
                $word = trim(substr($word, 1));
            }

            if ($startQuote === true && $endQuote === true) {
                if (!empty($phrase)) {
                    $this->addToWordList($results, $phrase);
                }
                $this->addToWordList($results, $word);
                $phrase = null;
                return;
            }
        }

        if ($endQuote) {
            $this->addToWordList($results, $phrase . ' ' . $word);
            $phrase = null;
            return;
        }

        if ($startQuote) {
            $phrase = $word;
            return;
        }

        if ($phrase === null) {
            if (!in_array($word, $this->ignoredWords)) {
                $this->addToWordList($results, $word);
            }
        } else if (!empty($word)) {
            $phrase .= ' ' . $word;
        }

    }

    function extractWords($string,$minLength=0)
    {
        $string = trim(strtolower(str_replace($this->removeChars, " ", $string)));
        $string = str_replace(["“", "”"], '"', $string);
        $results = array();
        $delimiters = " \n\t";
        $phrase = null;
        $tok = strtok($string, $delimiters);
        while ($tok !== false) {
            $tok = trim($tok);
            if (strlen($tok) >= $minLength) {
                $this->processWord($tok, $results, $phrase);
            }
            $tok = strtok($delimiters);
        }
        if (!empty($phrase)) {
            $this->addToWordList($results,$phrase);
        }
        return $results;
    }
}