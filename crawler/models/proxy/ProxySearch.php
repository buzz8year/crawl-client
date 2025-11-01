<?php

namespace crawler\models\proxy;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use crawler\models\proxy\Proxy;

/**
 * ProxySearch represents the model behind the search form of `crawler\models\proxy\Proxy`.
 */
class ProxySearch extends Proxy
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'port'], 'integer'],
            [['ip'], 'safe'],
        ];
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
        $query = Proxy::find();

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
            'port' => $this->port,
        ]);

        $query->andFilterWhere(['like', 'ip', $this->ip]);

        return $dataProvider;
    }
}
