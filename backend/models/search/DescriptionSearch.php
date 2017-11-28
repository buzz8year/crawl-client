<?php

namespace backend\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\Description;


class DescriptionSearch extends Description
{

    public function rules()
    {
        return [
            [['id', 'product_id', 'status'], 'integer'],
            [['title', 'text_original', 'text_synonymized'], 'safe'],
        ];
    }


    public function scenarios()
    {
        return Model::scenarios();
    }


    public function search($params)
    {
        $query = Description::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        $query->andFilterWhere([
            'id' => $this->id,
            'product_id' => $this->product_id,
            'status' => $this->status,
        ]);

        $query->andFilterWhere(['like', 'title', $this->title])
            ->andFilterWhere(['like', 'text_original', $this->text_original])
            ->andFilterWhere(['like', 'text_synonymized', $this->text_synonymized]);

        return $dataProvider;
    }
}
