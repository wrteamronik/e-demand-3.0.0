<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Test extends Migration
{
    public function up()
    {
        $this->forge->addField(
            [
                'id' =>
                [
                    'type' => 'INT',
                    'auto_increment' => true,
                    'unsigned' => true,
                    'constraint' => 11,
                ],
                'name' =>
                [
                    'type' => 'VARCHAR',
                    'constraint' => 200,
                ],
            ]
        );
        $this->forge->addKey('id', true);
        $this->forge->createTable('test_migrations');
    }
    public function down()
    {
        $this->forge->dropTable('test_migrations');
    }
}
