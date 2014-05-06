<?php

namespace Oro\Bundle\EmailBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class RemoveOldRelation implements Migration, OrderedMigrationInterface
{
    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        // remove old relation
        $table = $schema->getTable('oro_email');
        $table->removeForeignKey('fk_oro_email_folder_id');
        $table->dropIndex('IDX_2A30C171162CB942');
        $table->dropColumn('folder_id');
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 2;
    }
}