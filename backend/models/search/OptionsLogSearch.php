<?php

namespace backend\models\search;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use backend\models\OptionsLog;

/**
 * OptionsLogSearch represents the model behind the search form of `backend\models\OptionsLog`.
 */
class OptionsLogSearch extends OptionsLog
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['source_id', 'header_value_id', 'proxy_id', 'status'], 'integer'],
            [['client'], 'safe'],
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
        $query = OptionsLog::find();

        // add conditions that should always apply here

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
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'source_id' => $this->source_id,
            'header_value_id' => $this->header_value_id,
            'proxy_id' => $this->proxy_id,
            'status' => $this->status,
            // 'date' => $this->date,
        ]);

        $query->andFilterWhere(['like', 'client', $this->client]);

        return $dataProvider;
    }
}
