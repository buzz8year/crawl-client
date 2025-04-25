<?php

namespace crawler\models\keyword;

use crawler\models\source\Source;

class KeywordHandler
{
    public static function findOrCreate(int $sourceId, string $word, int $global = 0)
    {
        $findWord = Keyword::find()
            ->where(['word' => $word])
            ->one();

        if ($findWord) 
        {
            $keywordSource = KeywordSource::find()->where(['keyword_id' => $findWord->id])->one();
            if (!$keywordSource) 
                $keywordSource = KeywordSource::create($findWord->id, $sourceId);
        } 
        else 
        {
            $newKeyword = new Keyword();
            $newKeyword->word = $word;
            $newKeyword->save();

            $keywordSource = KeywordSource::create($newKeyword->id, $sourceId);

            // GLOBAL: Assign keyword globally, to all sources, or specifically to current one
            if ($global) 
            {
                foreach (Source::find()->all() as $source) 
                    if ($source->id != $sourceId) 
                        $keywordSource = KeywordSource::create($newKeyword->id, $source->id);
            }
        }
        $keyword = Keyword::findOne($keywordSource->keyword_id)->word;

        return $keyword;
    }
    
}