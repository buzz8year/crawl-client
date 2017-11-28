<?php

namespace backend\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\CategorySource;

/**
 * CategorySourceSearch represents the model behind the search form of `backend\models\CategorySource`.
 */
class CategorySourceSearch extends CategorySource
{
    public $title;
    public $status;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'status', 'category_id', 'source_id', 'self_parent_id'], 'integer'],
            [['title', 'source_url'], 'safe'],
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
        $query = CategorySource::find()
            ->join('left join', 'category c', 'c.id = category_source.category_id')
            ->groupBy(['category_source.id']);

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'category_source.id' => $this->id,
            'source_id' => $this->source_id,
            'category_id' => $this->category_id,
            'self_parent_id' => $this->self_parent_id,
        ]);

        $query->andFilterWhere(['like', 'source_url', $this->source_url]);

        if ($this->title) {
            $query->andFilterWhere(['like', 'title', $this->title]);
        }

        if ($this->status) {
            $query->andFilterWhere(['status' => $this->status]);
        }

        return $dataProvider;
    }
}
