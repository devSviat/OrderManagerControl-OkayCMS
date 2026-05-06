<?php

declare(strict_types=1);

namespace Okay\Modules\Sviat\OrderManagerControl\Entities;

use Okay\Core\Entity\Entity;

class OrderManagerControlEntity extends Entity
{
    protected static $fields = [
        'id',
        'order_id',
        'manager_id',
        'manager_login',
        'updated_at',
    ];

    protected static $defaultOrderFields = [
        'updated_at DESC',
    ];

    protected static $table = 'sviat__order_manager_control';
    protected static $tableAlias = 'omc';

    protected function filter__updated_at_min($value)
    {
        if ($value === null || $value === '') {
            return;
        }
        $this->select->where('omc.updated_at >= :omc_updated_at_min')
            ->bindValue('omc_updated_at_min', $value);
    }

    protected function filter__updated_at_max($value)
    {
        if ($value === null || $value === '') {
            return;
        }
        $this->select->where('omc.updated_at < :omc_updated_at_max')
            ->bindValue('omc_updated_at_max', $value);
    }
}
