<?php

namespace backend\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\History;


class HistorySearch extends History
{

    public function rules()
    {
        return [
            [['url', 'source_id', 'status'], 'safe'], // Return here to re-enable proxy
            [['source_id', 'category_source_id', 'keyword_id', 'status'], 'integer'],
            [['url'], 'string', 'max' => 255],
        ];
    }


    public function scenarios()
    {
        return Model::scenarios();
    }


    public function search($params)
    {
        $query = History::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'=> [
                'defaultOrder' => [
                    'date' => SORT_DESC
                ]
            ]
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'source_id' => $this->source_id,
            'category_source_id' => $this->category_source_id,
            'keyword_id' => $this->keyword_id,
        ]);

        $query->andFilterWhere(['like', 'url', $this->url])
            ->andFilterWhere(['like', 'note', $this->note]);

        return $dataProvider;
    }
}
