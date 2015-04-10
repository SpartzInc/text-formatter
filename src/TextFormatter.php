<?php namespace Cmgmyr\TextFormatter;

// @todo: https://www.google.com/webhp?sourceid=chrome-instant&ion=1&espv=2&ie=UTF-8#q=words%20that%20shouldn%27t%20be%20capitalized

use Illuminate\Support\Collection;

class TextFormatter
{

    protected $title = '';
    protected $indexedWords;
    protected $ignoredWords = [
        'a',
        'an',
        'and',
        'as',
        'at',
        'but',
        'by',
        'en',
        'for',
        'if',
        'in',
        'is',
        'of',
        'on',
        'or',
        'to',
        'the',
        'vs',
        'via'
    ];

    public function __construct($title)
    {
        $this->setTitle($title);
        $this->createWordIndex();
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        // removes all extra spaces
        $this->title = trim(preg_replace('/\s\s+/', ' ', str_replace("\n", " ", $title)));
    }

    public static function titleCase($title)
    {
        // hack in order to keep static method call
        $obj = new TextFormatter($title);
        return $obj->convertTitle();
    }

    public function convertTitle()
    {
        foreach ($this->indexedWords as $index => $word) {
            if ($this->wordShouldBeUppercase($index, $word)) {
                $this->rebuildTitle($index, $this->uppercaseWord($word));
            }
        }

        return $this->title;
    }

    protected function rebuildTitle($index, $word)
    {
        $this->title =
            mb_substr($this->title, 0, $index, 'UTF-8') .
            $word .
            mb_substr($this->title, $index + mb_strlen($word, 'UTF-8'), mb_strlen($this->title, 'UTF-8'), 'UTF-8');
    }

    protected function isIgnoredWord($word)
    {
        return in_array($word, $this->ignoredWords);
    }

    protected function isFirstWord($index)
    {
        return $index == 0;
    }

    /**
     * @param $word
     * @return string
     */
    protected function uppercaseWord($word)
    {
        // see if first character is special
        $first = mb_substr($word, 0, 1);
        if ($this->isPunctuation($first)) {
            $word = mb_substr($word, 1, -1);

            return $first . ucwords($word);
        }

        return ucwords($word);
    }

    protected function firstWordSentence($index)
    {
        $twoCharactersBack = mb_substr($this->title, $index - 2, 1);

        if ($this->isPunctuation($twoCharactersBack)) {
            return true;
        }

        return false;
    }

    /**
     * @param $string
     * @return int
     */
    protected function isPunctuation($string)
    {
        return preg_match("/\p{P}/u", $string);
    }

    /**
     * @param $index
     * @return int
     */
    protected function correctIndexOffset($index)
    {
        $index = mb_strlen(substr($this->title, 0, $index), 'UTF-8');
        return $index;
    }

    protected function createWordIndex()
    {
        $indexedWords = [];
        $offset = 0;

        $words = explode(' ', $this->title);
        foreach ($words as $word) {
            $indexedWords[$this->getWordIndex($word, $offset)] = strtolower($word);
            $offset += strlen($word) + 1; // plus space
        }

        $this->indexedWords = new Collection($indexedWords);
    }

    protected function isLastWord($word)
    {
        if ($word === $this->indexedWords->last()) {
            return true;
        }

        return false;
    }

    /**
     * @param $word
     * @param $offset
     * @return bool|int
     */
    protected function getWordIndex($word, $offset)
    {
        $index = strpos($this->title, $word, $offset);
        return $this->correctIndexOffset($index);
    }

    /**
     * @param $index
     * @param $word
     * @return bool
     */
    protected function wordShouldBeUppercase($index, $word)
    {
        return $this->isFirstWord($index) || $this->isLastWord($word) || $this->firstWordSentence($index) || !$this->isIgnoredWord($word);
    }
}