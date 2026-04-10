<?php

namespace app\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\Expression;

class ClickStatsSearch extends Model
{
    public string $short_code = '';
    public string $ip = '';
    public ?int $min_ip_clicks = null;

    public function rules(): array
    {
        return [
            [['short_code', 'ip'], 'trim'],
            [['short_code'], 'string', 'max' => 16],
            [['short_code'], 'match', 'pattern' => '/^[A-Za-z0-9_-]*$/'],
            [['ip'], 'string', 'max' => 45],
            [['min_ip_clicks'], 'integer', 'min' => 1],
        ];
    }

    public function search(array $params): ActiveDataProvider
    {
        $query = LinkClickLog::find()
            ->alias('log')
            ->asArray()
            ->select([
                'link.id AS link_id',
                'link.short_code',
                'link.original_url',
                'link.click_count AS total_clicks',
                'log.ip',
                'COUNT(*) AS ip_clicks',
                'MAX(log.created_at) AS last_click_at',
            ])
            ->innerJoin(['link' => Link::tableName()], 'link.id = log.link_id')
            ->groupBy(['link.id', 'link.short_code', 'link.original_url', 'link.click_count', 'log.ip']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'key' => 'link_id',
            'pagination' => ['pageSize' => 20],
            'sort' => [
                'defaultOrder' => ['ip_clicks' => SORT_DESC, 'last_click_at' => SORT_DESC],
                'attributes' => [
                    'short_code' => [
                        'asc' => ['link.short_code' => SORT_ASC],
                        'desc' => ['link.short_code' => SORT_DESC],
                    ],
                    'ip' => [
                        'asc' => ['log.ip' => SORT_ASC],
                        'desc' => ['log.ip' => SORT_DESC],
                    ],
                    'ip_clicks' => [
                        'asc' => ['ip_clicks' => SORT_ASC],
                        'desc' => ['ip_clicks' => SORT_DESC],
                    ],
                    'total_clicks' => [
                        'asc' => ['link.click_count' => SORT_ASC],
                        'desc' => ['link.click_count' => SORT_DESC],
                    ],
                    'last_click_at' => [
                        'asc' => ['last_click_at' => SORT_ASC],
                        'desc' => ['last_click_at' => SORT_DESC],
                    ],
                ],
            ],
        ]);

        $this->load($params, '');
        if (!$this->validate()) {
            $query->andWhere('1=0');
            return $dataProvider;
        }

        $query->andFilterWhere(['like', 'link.short_code', $this->short_code]);
        $query->andFilterWhere(['like', 'log.ip', $this->ip]);

        if ($this->min_ip_clicks !== null && $this->min_ip_clicks > 0) {
            $query->andHaving(['>=', new Expression('COUNT(*)'), $this->min_ip_clicks]);
        }

        return $dataProvider;
    }
}
