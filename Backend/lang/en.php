<?php

$lang['sviat_order_manager_control__title'] = 'Order Manager Control';
$lang['sviat_order_manager_control__how_it_works_title'] = 'How the module works';
$lang['sviat_order_manager_control__intro'] = 'The module adds an indicator to the order toolbar and shows which managers are currently working with this order.';
$lang['sviat_order_manager_control__point_badge'] = 'An order page badge shows logins of active managers.';
$lang['sviat_order_manager_control__point_ping'] = 'While the tab is open, a service ping request is sent every 30 seconds.';
$lang['sviat_order_manager_control__point_ttl'] = 'A manager is considered active for 90 seconds after the last ping.';
$lang['sviat_order_manager_control__point_states'] = 'If only the current manager is active, the badge stays in calm state; if there are several managers, it switches to busy state.';
$lang['sviat_order_manager_control__point_inactive'] = 'When the tab is inactive or closed, requests stop, and the record disappears automatically after TTL expires.';
