<?php

$lang['sviat_order_manager_control__title'] = 'Order Manager Control';
$lang['sviat_order_manager_control__how_it_works_title'] = 'Як працює модуль';
$lang['sviat_order_manager_control__intro'] = 'Модуль додає індикатор у панель замовлення та показує, хто з менеджерів зараз працює з цим замовленням.';
$lang['sviat_order_manager_control__point_badge'] = 'На сторінці замовлення відображається бейдж з логінами активних менеджерів.';
$lang['sviat_order_manager_control__point_ping'] = 'Під час відкритої вкладки кожні 30 секунд відправляється службовий запит (ping).';
$lang['sviat_order_manager_control__point_ttl'] = 'Менеджер вважається активним протягом 90 секунд після останнього ping.';
$lang['sviat_order_manager_control__point_states'] = 'Якщо активний тільки поточний менеджер - бейдж у спокійному стані; якщо менеджерів кілька - у зайнятому стані.';
$lang['sviat_order_manager_control__point_inactive'] = 'Коли вкладка неактивна або закрита, запити зупиняються, і запис автоматично зникає після завершення TTL.';
