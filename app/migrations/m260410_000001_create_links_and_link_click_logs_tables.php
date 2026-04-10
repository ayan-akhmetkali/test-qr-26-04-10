<?php

use yii\db\Migration;

/**
 * Handles the creation of tables `{{%links}}` and `{{%link_click_logs}}`.
 */
class m260410_000001_create_links_and_link_click_logs_tables extends Migration
{
    public function safeUp()
    {
        $tableOptions = null;

        if ($this->db->driverName === 'mysql') {
            $tableOptions = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE=InnoDB';
        }

        $this->createTable('{{%links}}', [
            'id' => $this->bigPrimaryKey(),
            'original_url' => $this->text()->notNull(),
            'normalized_url' => $this->string(2048)->notNull(),
            'short_code' => $this->string(16)->notNull(),
            'click_count' => $this->bigInteger()->notNull()->defaultValue(0),
            'status' => $this->tinyInteger()->notNull()->defaultValue(1),
            'created_at' => $this->dateTime()->notNull(),
            'updated_at' => $this->dateTime()->null(),
        ], $tableOptions);

        $this->createIndex('ux_links_short_code', '{{%links}}', 'short_code', true);
        $this->createIndex('ix_links_created_at', '{{%links}}', 'created_at');

        $this->createTable('{{%link_click_logs}}', [
            'id' => $this->bigPrimaryKey(),
            'link_id' => $this->bigInteger()->notNull(),
            'ip' => $this->string(45)->notNull(),
            'user_agent' => $this->string(1024)->null(),
            'referer' => $this->string(2048)->null(),
            'created_at' => $this->dateTime()->notNull(),
        ], $tableOptions);

        $this->createIndex('ix_logs_link_id_created_at', '{{%link_click_logs}}', ['link_id', 'created_at']);
        $this->createIndex('ix_logs_ip', '{{%link_click_logs}}', 'ip');

        $this->addForeignKey(
            'fk_link_click_logs_link_id',
            '{{%link_click_logs}}',
            'link_id',
            '{{%links}}',
            'id',
            'CASCADE',
            'RESTRICT'
        );
    }

    public function safeDown()
    {
        $this->dropForeignKey('fk_link_click_logs_link_id', '{{%link_click_logs}}');

        $this->dropTable('{{%link_click_logs}}');
        $this->dropTable('{{%links}}');
    }
}
