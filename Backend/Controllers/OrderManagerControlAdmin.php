<?php

declare(strict_types=1);

namespace Okay\Modules\Sviat\OrderManagerControl\Backend\Controllers;

use Okay\Admin\Controllers\IndexAdmin;
use Okay\Modules\Sviat\OrderManagerControl\Helpers\OrderManagerControlHelper;

class OrderManagerControlAdmin extends IndexAdmin
{
    public function fetch(): void
    {
        $this->response->setContent($this->design->fetch('order_manager_control_admin.tpl'));
    }

    public function ping(OrderManagerControlHelper $helper): void
    {
        $orderId = (int) $this->request->post('order_id', 'integer');
        $managerId = !empty($this->manager->id) ? (int) $this->manager->id : 0;

        if ($orderId <= 0 || $managerId <= 0) {
            $this->response->setContent(json_encode(['success' => false]), RESPONSE_JSON);
            return;
        }

        $managerLogin = !empty($this->manager->login)
            ? (string) $this->manager->login
            : 'ID ' . $managerId;

        $helper->registerPresence($orderId, $managerId, $managerLogin);
        $managers = $helper->getActiveManagers($orderId);

        $this->response->setContent(json_encode([
            'success' => true,
            'managers' => $managers,
        ]), RESPONSE_JSON);
    }
}
