<?php

namespace crawler\models\category;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use crawler\models\source\Source;

/**
 * CategorySearch represents the model behind the search form of `crawler\models\Category`.
 */
class CategorySearch extends Category
{
    public $source;
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'status'], 'integer'],
            [['title', 'source'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    // public function scenarios()
    // {
    //     // bypass scenarios() implementation in the parent class
    //     return Model::scenarios();
    // }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Category::find()
            ->join('left join', 'category_source cs', 'cs.category_id = category.id')
            ->groupBy(['category.id']);

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
            'category.id' => $this->id,
            'status' => $this->status,
        ]);

        if ($this->source) {
            $source = Source::find()->where(['like', 'title', $this->source])->one()->id;
            $query->andFilterWhere([
                'cs.source_id' => $source,
            ]);
        }

        $query->andFilterWhere(['like', 'title', $this->title]);

        return $dataProvider;
    }
}
