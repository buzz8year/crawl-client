<?php

namespace backend\models\morph;

use Yii;

Class Morph {

    protected $morphy;

    public function __construct(string $locale) {
        set_include_path(Yii::getAlias('@morphy') . '/src/' . PATH_SEPARATOR . get_include_path());
        require_once('phpMorphy.php');

        $opts = array(
            'storage' => PHPMORPHY_STORAGE_FILE,
            'graminfo_as_text' => true,
        );

        $dir = Yii::getAlias('@morphy') . '/dicts/' . $locale;

        switch ($locale) {
            case 'ru':
                $lang = 'ru_RU';
                break;
            case 'en':
                $lang = 'en_EN';
                break;
        }

        try {
            $this->morphy = new \phpMorphy($dir, $lang, $opts);
        } catch(phpMorphy_Exception $e) {
            die('Error occured while creating phpMorphy instance: ' . PHP_EOL . $e);
        }
    }

    public function lemmatizeOne(string $word) {

        try {
            $lemmas = $this->morphy->getBaseForm($word);
            $speech = $this->morphy->getPartOfSpeech($word);
            // print_r($this->morphy->getAllForms($word));
            // print_r($speech);
            // if ($lemmas && in_array($speech[0], ['С','П','Г','Н'])) { // SPEECH: сущ., прил., гл. 
            if ($lemmas && !in_array($speech[0], ['СОЮЗ','ПРЕДЛ'])) { // SPEECH: сущ., прил., гл. 
                if (count($lemmas) > 1) {

                    if (in_array('С', $speech) 
                        && (in_array('Г', $speech) || in_array('П', $speech) || in_array('Н', $speech))
                        && count($speech) == count($lemmas)) {
                            foreach ($lemmas as $key => $lemma) {
                                if ($speech[$key] === 'С') { // LOCALE: This is Cyrillic 'С' (сущест.), not Latin one 
                                    // $best = $lemma;
                                    return $lemma;
                                }
                            }
                    }
                    else {
                        $min = -1;
                        foreach ($lemmas as $lemma) {
                            $lev = levenshtein($word, $lemma);
                            if ($lev == 0) {
                                $best = $lemma;
                                $min = 0;
                                break;
                            }
                            elseif ($lev <= $min || $min < 0) {
                                $best = $lemma;
                                $min = $lev;
                            }
                        }
                    }
                    return $best;

                } else {
                    return $lemmas[0]; // TODO: It should broken after first match down the getBaseForm() method and not here, if performance does count
                }

            }

            if (!$lemmas) {
                return $word;
            }

        } catch(phpMorphy_Exception $e) {
            die('Error occured while text processing: ' . $e->getMessage());
        }

    }


    // public function lemmatizeMultiple(array $words) {
    //     $data = [];

    //     if(function_exists('iconv')) {
    //         foreach($words as &$word) {
    //             $word = iconv('utf-8', $this->morphy->getEncoding(), $word);
    //         }
    //         unset($word);
    //     }

    //     try {
    //         foreach($words as $word) {
    //             $base = $this->morphy->getBaseForm($word);
    //             $is_predicted = $this->morphy->isLastPredicted();
    //             $collection = $this->morphy->findWord($word);
    //             if(false === $collection) { 
    //                 continue;
    //             } else {
    //             }
    //             $data[] = $base;
    //         }
    //         return $data;

    //     } catch(phpMorphy_Exception $e) {
    //         die('Error occured while text processing: ' . $e->getMessage());
    //     }
    // }

    public function getPhraseLemmas(string $phrase, int $reverse = 0)
    {
        $data = [];
        $mute = [];

        $title = mb_strtoupper(trim($phrase));
        // $alpha = preg_replace('/[^А-ЯA-Z-]/', ' ', $title);
        $alpha = preg_replace('/[^А-ЯA-Z0-9-]/', ' ', $title);
        $num = preg_replace('/[^0-9.]/', ' ', $title);
        $exp = explode(' ', $title);
        $expNum = explode(' ', $num);
        $expAlpha = explode(' ', $alpha);

        foreach ($exp as $string) {
            // if (preg_match('/[A-ZА-Я].*[0-9]|[0-9].*[A-ZА-Я]/', $string)) {
            //     $data[] = mb_strtolower($string);
            //     $mute[] = preg_replace('/[^0-9]/', '', $string);
            //     $mute[] = preg_replace('/[^А-ЯA-Z]/', '', $string);
            // }
            if ((preg_match('/["]/', $string) || preg_match('/[\'\']/', $string)) && !in_array('дюйм', $mute)) {
                $data[] = 'дюйм';
                $mute[] = 'дюйм';
            }
        }

        foreach ($expAlpha as $alpha) {
            // $locale = preg_match('/[\p{Cyrillic}]/u', $alpha) ? 'ru' : 'en';
            if (preg_match('/[\p{Cyrillic}]/u', $alpha) && !in_array($alpha, $mute)) {
                if ($return = $this->lemmatizeOne($alpha)) {
                    if (strpos($return, '-') === false) {
                        $data[] = mb_strtolower($return);
                    } else {
                        $exp = explode('-', $return);
                        foreach ($exp as $lemma) {
                            $data[] = mb_strtolower($lemma);
                        }
                    }
                }
            }
            if (preg_match('/[\p{Latin}]/u', $alpha) && !in_array($alpha, $mute)) {
                $data[] = mb_strtolower($alpha);
            }
        }

        foreach ($expNum as $number) {
            if ($number && is_numeric($number) && !in_array($number, $mute) && count(explode('.', trim($number, '.'))) > 1) {
                $data[] = $number;
            }
        }

        return $reverse ? array_reverse($data) : $data;
        // return implode('+', $data);
    }

}