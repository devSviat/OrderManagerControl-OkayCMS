<?php

declare(strict_types=1);

namespace Okay\Modules\Sviat\OrderManagerControl\Helpers;

use Okay\Core\EntityFactory;
use Okay\Core\Modules\Extender\ExtenderFacade;
use Okay\Modules\Sviat\OrderManagerControl\Entities\OrderManagerControlEntity;
use Throwable;

class OrderManagerControlHelper
{
    public const ACTIVE_TTL = 90;
    public const CLEANUP_GRACE = 300;

    /** @var EntityFactory */
    private $entityFactory;

    public function __construct(EntityFactory $entityFactory)
    {
        $this->entityFactory = $entityFactory;
    }

    /**
     * @return array<int, array{id:int,login:string}>
     */
    public function getActiveManagers(int $orderId)
    {
        /** @var OrderManagerControlEntity $entity */
        $entity = $this->entityFactory->get(OrderManagerControlEntity::class);

        $threshold = date('Y-m-d H:i:s', time() - self::ACTIVE_TTL);
        $rows = $entity->find(['order_id' => $orderId, 'updated_at_min' => $threshold]);

        $managers = [];
        foreach ($rows as $row) {
            $managers[(int) $row->manager_id] = [
                'id' => (int) $row->manager_id,
                'login' => (string) $row->manager_login,
            ];
        }

        return ExtenderFacade::execute(__METHOD__, array_values($managers), func_get_args());
    }

    public function registerPresence(int $orderId, int $managerId, string $managerLogin): bool
    {
        if ($orderId <= 0 || $managerId <= 0) {
            return ExtenderFacade::execute(__METHOD__, false, func_get_args());
        }

        /** @var OrderManagerControlEntity $entity */
        $entity = $this->entityFactory->get(OrderManagerControlEntity::class);

        $now = date('Y-m-d H:i:s');
        $current = $entity->findOne([
            'order_id' => $orderId,
            'manager_id' => $managerId,
        ]);

        if (!empty($current->id)) {
            $entity->update((int) $current->id, [
                'manager_login' => $managerLogin,
                'updated_at' => $now,
            ]);
            return ExtenderFacade::execute(__METHOD__, true, func_get_args());
        }

        try {
            $entity->add((object) [
                'order_id' => $orderId,
                'manager_id' => $managerId,
                'manager_login' => $managerLogin,
                'updated_at' => $now,
            ]);
        } catch (Throwable $e) {
            $existing = $entity->findOne([
                'order_id' => $orderId,
                'manager_id' => $managerId,
            ]);
            if (!empty($existing->id)) {
                $entity->update((int) $existing->id, [
                    'manager_login' => $managerLogin,
                    'updated_at' => $now,
                ]);
            }
        }

        return ExtenderFacade::execute(__METHOD__, true, func_get_args());
    }

    public function cleanupStale(): int
    {
        /** @var OrderManagerControlEntity $entity */
        $entity = $this->entityFactory->get(OrderManagerControlEntity::class);

        $threshold = date('Y-m-d H:i:s', time() - self::ACTIVE_TTL - self::CLEANUP_GRACE);
        $stale = $entity->noLimit()->find(['updated_at_max' => $threshold]);

        $ids = [];
        foreach ($stale as $row) {
            $ids[] = (int) $row->id;
        }

        if (!empty($ids)) {
            $entity->delete($ids);
        }

        return ExtenderFacade::execute(__METHOD__, count($ids), func_get_args());
    }
}
