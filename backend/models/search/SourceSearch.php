<?php

namespace backend\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\Source;

/**
 * SourceSearch represents the model behind the search form of `backend\models\Source`.
 */
class SourceSearch extends Source
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'status', 'need_synonymizer', 'need_proxy', 'need_captcha'], 'integer'],
            [['title', 'source_url', 'description', 'class_namespace'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Source::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'status' => $this->status,
            'need_synonymizer' => $this->need_synonymizer,
            'need_proxy' => $this->need_proxy,
            'need_captcha' => $this->need_captcha,
        ]);

        $query->andFilterWhere(['like', 'title', $this->title])
            ->andFilterWhere(['like', 'source_url', $this->source_url])
            ->andFilterWhere(['like', 'description', $this->description])
            ->andFilterWhere(['like', 'class_namespace', $this->class_namespace]);

        return $dataProvider;
    }
}
