<?php

namespace Ludo\Translation;

use RuntimeException;

/**
 * Class Repository
 */
class Translator
{
    private $translation = array();
    private $language;

    public function __construct()
    {
        if (isset($_COOKIE['lang'])) {
            $language = $_COOKIE['lang'];
        } else if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $language = strtolower($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        } else {
            $language = DEFAULT_LANGUAGE;
        }

        if (($pos = strpos($language, ',')) !== false) {
            $language = substr($language, 0, $pos);

            if (PHP_SAPI != 'cli') {
                setcookie('lang', $language, null, '/');
            }
        }

        $this->language = strtolower($language);
    }

    /**
     * Set current language
     *
     * @param $lang
     */
    public function setLanguage(string $lang): void
    {
        $langDir = LD_LANGUAGE_PATH . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR;
        if (!file_exists($langDir)) {
            throw new RuntimeException(sprintf('language file [%s] does not exist.', $lang));
        }

        $this->language = $lang;
    }

    /**
     * Get the translation for the given key.
     *
     * @param string $key
     * @param array $replace
     * @param string $locale
     * @return string
     */
    public function get(string $key, array $replace = array(), string $locale = null): string
    {
        if (is_null($locale)) {
            $locale = $this->language;
        }

        list($group, $item) = explode('.', $key);
        if (!isset($this->translation[$group])) {
            $filename = LD_LANGUAGE_PATH . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR . $group . '.lang.php';
            file_exists($filename) && $this->translation[$group] = include $filename;
        }

        $value = $this->translation[$group][$item];

        if (!empty($replace)) {
            foreach ($replace as $k => $v) {
                $value = str_replace(':' . $k, $v, $value);
            }
        }
        return $value;
    }

    /**
     * Get untranslated data
     *
     * @param string $base base directory of language files
     * @param string $baseLang base language
     * @param string $diffLang diff language
     * @return array
     */
    public function diff(string $base, string $baseLang, string $diffLang): array
    {
        $baseLanguages = $this->getTranslate($base, $baseLang);
        $diffLanguages = $this->getTranslate($base, $diffLang);

        $result = array();
        foreach ($baseLanguages as $filename => $languages) {
            foreach ($languages as $k => $v) {
                if (empty($diffLanguages[$filename][$k])) {
                    $result[$filename][$k] = $v;
                }
            }
        }
        return $result;
    }

    /**
     * Merge translated data
     *
     * @param string $base base directory of language files
     * @param string $lang language
     * @param array $data translated data
     */
    public function merge(string $base, string $lang, array $data): void
    {
        $baseLangDir = $base . DIRECTORY_SEPARATOR . DEFAULT_LANGUAGE . DIRECTORY_SEPARATOR;

        foreach ($data as $filename => $datum) {
            $baseLangFilename = $baseLangDir . $filename . '.lang.php';
            if (!file_exists($baseLangFilename)) {
                continue;
            }

            $diffLangFilename = $base . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR . $filename . '.lang.php';
            $diffData = array();
            if (file_exists($diffLangFilename)) {
                $diffData = require $diffLangFilename;
            }

            foreach ($datum as $k => $v) {
                $diffData[$k] = $v;
            }
            $str = var_export($diffData, true);
            $str = "<?php\nreturn " . $str . ';';
            file_put_contents($diffLangFilename, $str);
        }
    }

    /**
     * Get translate data
     *
     * @param string $base base directory of language files
     * @param string $lang language short name
     * @return array
     */
    private function getTranslate(string $base, string $lang): array
    {
        $langDirectory = $base . DIRECTORY_SEPARATOR . $lang . DIRECTORY_SEPARATOR;
        $files = scandir($langDirectory);
        $languages = [];
        foreach ($files as $file) {
            if ($file[0] == '.' || $file[0] == '..') {
                continue;
            }

            $filename = $langDirectory . $file;
            $ext = ext($filename);
            if ($ext != 'php') {
                continue;
            }

            $file = explode('.', $file)[0];
            $languages[$file] = require $filename;
        }

        return $languages;
    }
}
