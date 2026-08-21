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
        // Токен сторінки протух — сесія змінилась, відколи її відрендерили.
        // Форк такий POST відхиляє ще до контролера (403), а стокова перевіряє
        // вже ПІСЛЯ його роботи й лише пише в лог `Session expired`, віддаючи
        // 200. Тобто без цієї гілки клієнт на стоку відмови не бачить і б'є
        // далі, засипаючи лог. Очікуване значення обидва рушії тримають в
        // $_SESSION['id'] — у форку це CSRF-токен, у стоковій ідентифікатор
        // сесії, — тож порівняння однакове.
        $sentToken = (string) $this->request->post('session_id');
        $expectedToken = isset($_SESSION['id']) ? (string) $_SESSION['id'] : '';

        if ($expectedToken === '' || !hash_equals($expectedToken, $sentToken)) {
            $this->response->setContent(
                json_encode(['success' => false, 'expired' => true]),
                RESPONSE_JSON
            );
            return;
        }

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
