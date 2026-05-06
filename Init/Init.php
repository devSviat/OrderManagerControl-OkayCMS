<?php

declare(strict_types=1);

namespace Okay\Modules\Sviat\OrderManagerControl\Init;

use Okay\Admin\Helpers\BackendOrdersHelper;
use Okay\Core\Modules\AbstractInit;
use Okay\Core\Modules\EntityField;
use Okay\Core\Scheduler\Schedule;
use Okay\Modules\Sviat\OrderManagerControl\Entities\OrderManagerControlEntity;
use Okay\Modules\Sviat\OrderManagerControl\Extenders\BackendExtender;
use Okay\Modules\Sviat\OrderManagerControl\Helpers\OrderManagerControlHelper;

class Init extends AbstractInit
{
    public function install(): void
    {
        $this->setBackendMainController('OrderManagerControlAdmin');

        $managerIdField = (new EntityField('manager_id'))->setTypeInt(11, false);

        $this->migrateEntityTable(OrderManagerControlEntity::class, [
            (new EntityField('id'))->setIndexPrimaryKey()->setTypeInt(11, false)->setAutoIncrement(),
            (new EntityField('order_id'))->setTypeInt(11, false)->setIndexUnique(null, $managerIdField),
            $managerIdField,
            (new EntityField('manager_login'))->setTypeVarchar(64, false)->setDefault(''),
            (new EntityField('updated_at'))->setTypeDatetime(false)->setIndex(),
        ]);
    }

    public function init(): void
    {
        $this->registerBackendController('OrderManagerControlAdmin');
        $this->addBackendControllerPermission('OrderManagerControlAdmin', 'orders');

        $this->registerChainExtension(
            [BackendOrdersHelper::class, 'findOrder'],
            [BackendExtender::class, 'findOrder']
        );

        $this->registerSchedule(
            (new Schedule([OrderManagerControlHelper::class, 'cleanupStale']))
                ->name('OrderManagerControl: cleanup stale presence rows')
                ->time('*/5 * * * *')
                ->overlap(false)
                ->timeout(60)
        );
    }
}
