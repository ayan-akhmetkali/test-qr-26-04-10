<?php

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * @property int $id
 * @property string $original_url
 * @property string $normalized_url
 * @property string $short_code
 * @property int $click_count
 * @property int $status
 * @property string $created_at
 * @property string|null $updated_at
 *
 * @property-read LinkClickLog[] $clickLogs
 */
class Link extends ActiveRecord
{
    public const STATUS_ACTIVE = 1;
    public const STATUS_DISABLED = 0;

    public static function tableName(): string
    {
        return '{{%links}}';
    }

    public function rules(): array
    {
        return [
            [['original_url', 'normalized_url', 'short_code'], 'required'],
            [['original_url'], 'string'],
            [['normalized_url'], 'string', 'max' => 2048],
            [['short_code'], 'string', 'max' => 16],
            [['short_code'], 'match', 'pattern' => '/^[A-Za-z0-9_-]+$/'],
            [['short_code'], 'unique'],
            [['click_count'], 'integer', 'min' => 0],
            [['status'], 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_DISABLED]],
            [['original_url', 'normalized_url'], 'url', 'validSchemes' => ['http', 'https']],
            [['created_at', 'updated_at'], 'safe'],
            [['click_count'], 'default', 'value' => 0],
            [['status'], 'default', 'value' => self::STATUS_ACTIVE],
        ];
    }

    public function attributeLabels(): array
    {
        return [
            'id' => 'ID',
            'original_url' => 'Original URL',
            'normalized_url' => 'Normalized URL',
            'short_code' => 'Short Code',
            'click_count' => 'Click Count',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }

    public function behaviors(): array
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => 'updated_at',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    public function getClickLogs(): ActiveQuery
    {
        return $this->hasMany(LinkClickLog::class, ['link_id' => 'id']);
    }

    public static function findActiveByShortCode(string $shortCode): ?self
    {
        return static::find()
            ->where(['short_code' => $shortCode, 'status' => self::STATUS_ACTIVE])
            ->one();
    }
}
