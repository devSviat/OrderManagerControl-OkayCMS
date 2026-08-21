<?php

declare(strict_types=1);

namespace Okay\Modules\Sviat\OrderManagerControl\Extenders;

use Okay\Core\Design;
use Okay\Core\Modules\Extender\ExtensionInterface;
use Okay\Modules\Sviat\OrderManagerControl\Helpers\OrderManagerControlHelper;

class BackendExtender implements ExtensionInterface
{
    /** @var OrderManagerControlHelper */
    private $helper;

    /** @var Design */
    private $design;

    public function __construct(OrderManagerControlHelper $helper, Design $design)
    {
        $this->helper = $helper;
        $this->design = $design;
    }

    public function findOrder($order)
    {
        if (empty($order) || empty($order->id)) {
            return $order;
        }

        $orderId = (int) $order->id;
        $manager = $this->design->getVar('manager');

        if (!empty($manager) && !empty($manager->id)) {
            $login = !empty($manager->login)
                ? (string) $manager->login
                : 'ID ' . (int) $manager->id;
            $this->helper->registerPresence($orderId, (int) $manager->id, $login);
        }

        $managers = $this->helper->getActiveManagers($orderId);
        $logins = array_values(array_map(
            static fn(array $m): string => (string) $m['login'],
            $managers
        ));

        // Готовий рядок і прапорець, а не масив для обчислень у шаблоні:
        // Smarty 3 стокової звіряє кожну функцію в шаблоні з політикою
        // безпеки, і картка замовлення падала на join() фаталом.
        $order->omc_active_managers = $logins;
        $order->omc_active_managers_text = implode(', ', $logins);
        $order->omc_is_busy = count($logins) > 1;

        return $order;
    }
}
