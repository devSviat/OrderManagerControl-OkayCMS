<?php

$lang['sviat_order_manager_control__title'] = 'Order Manager Control';
$lang['sviat_order_manager_control__how_it_works_title'] = 'Как работает модуль';
$lang['sviat_order_manager_control__intro'] = 'Модуль добавляет индикатор в панель заказа и показывает, кто из менеджеров сейчас работает с этим заказом.';
$lang['sviat_order_manager_control__point_badge'] = 'На странице заказа отображается бейдж с логинами активных менеджеров.';
$lang['sviat_order_manager_control__point_ping'] = 'При открытой вкладке каждые 30 секунд отправляется служебный запрос (ping).';
$lang['sviat_order_manager_control__point_ttl'] = 'Менеджер считается активным в течение 90 секунд после последнего ping.';
$lang['sviat_order_manager_control__point_states'] = 'Если активен только текущий менеджер, бейдж в спокойном состоянии; если менеджеров несколько - в занятом состоянии.';
$lang['sviat_order_manager_control__point_inactive'] = 'Когда вкладка неактивна или закрыта, запросы останавливаются, а запись автоматически исчезает после завершения TTL.';
