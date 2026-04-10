<?php

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * @property int $id
 * @property int $link_id
 * @property string $ip
 * @property string|null $user_agent
 * @property string|null $referer
 * @property string $created_at
 *
 * @property-read Link $link
 */
class LinkClickLog extends ActiveRecord
{
    public static function tableName(): string
    {
        return '{{%link_click_logs}}';
    }

    public function rules(): array
    {
        return [
            [['link_id', 'ip'], 'required'],
            [['link_id'], 'integer'],
            [['ip'], 'ip'],
            [['user_agent'], 'string', 'max' => 1024],
            [['referer'], 'string', 'max' => 2048],
            [['referer'], 'url', 'validSchemes' => ['http', 'https'], 'skipOnEmpty' => true],
            [['created_at'], 'safe'],
            [
                ['link_id'],
                'exist',
                'targetClass' => Link::class,
                'targetAttribute' => ['link_id' => 'id'],
            ],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'link_id' => 'Link ID',
            'ip' => 'IP',
            'user_agent' => 'User Agent',
            'referer' => 'Referer',
            'created_at' => 'Created At',
        ];
    }

    public function behaviors(): array
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => false,
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    public function getLink(): ActiveQuery
    {
        return $this->hasOne(Link::class, ['id' => 'link_id']);
    }
}
