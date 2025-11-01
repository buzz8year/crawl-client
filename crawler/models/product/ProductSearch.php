<?php

namespace crawler\models\product;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;


class ProductSearch extends Product
{
    public function rules()
    {
        return [
            [['source_id', 'keyword_id', 'category_id', 'track_price'], 'integer'],
            [['title', 'source_url', 'price_update'], 'safe'],
        ];
    }

    public function search (array $params, int $pageSize = 20, bool $sync = false)
    {
        $query = Product::find();

        $this->load($params);

        $query->andFilterWhere([
            'id' => $this->id,
            'source_id' => $this->source_id,
            'keyword_id' => $this->keyword_id,
            'category_id' => $this->category_id,
            'price' => $this->price,
            'price_new' => $this->price_new,
            'price_new_last_update' => $this->price_update,
            'track_price' => $this->track_price,
            'sync_status' => $sync ? 1 : $this->sync_status,
        ]);

        $query->andFilterWhere(['like', 'source_url', $this->source_url]);
        $query->andFilterWhere(['like', 'title', $this->title]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'sort'=> [
                'defaultOrder' => [
                    'id' => SORT_DESC
                ]
            ],
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);

        if (!$this->validate()) {
            return $dataProvider;
        }

        return $dataProvider;
    }
}
