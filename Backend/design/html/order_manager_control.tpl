{if !empty($order->id)}
    <div class="ml-h hidden-xs-down order_toolbar__managers" id="omc-order-manager-control" data-order-id="{$order->id|escape}" data-current-manager-id="{$manager->id|escape}">
        <span class="omc-badge {if $order->omc_is_busy}omc-badge--busy{else}omc-badge--self{/if} fn_omc_badge">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="omc-badge__icon icon icon-tabler icons-tabler-outline icon-tabler-headset"><path stroke="none" d="M0 0h24v24H0z" fill="none" /><path d="M4 14v-3a8 8 0 1 1 16 0v3" /><path d="M18 19c0 1.657 -2.686 3 -6 3" /><path d="M4 14a2 2 0 0 1 2 -2h1a2 2 0 0 1 2 2v3a2 2 0 0 1 -2 2h-1a2 2 0 0 1 -2 -2v-3" /><path d="M15 14a2 2 0 0 1 2 -2h1a2 2 0 0 1 2 2v3a2 2 0 0 1 -2 2h-1a2 2 0 0 1 -2 -2v-3" /></svg>
            <span class="fn_omc_managers_text omc-badge__text">
                {if !empty($order->omc_active_managers_text)}
                    {$order->omc_active_managers_text|escape}
                {else}
                    —
                {/if}
            </span>
        </span>
    </div>

    {literal}
    <script>
        (function () {
            var container = document.getElementById('omc-order-manager-control');
            if (!container || typeof window.jQuery === 'undefined') {
                return;
            }

            var orderId = parseInt(container.getAttribute('data-order-id'), 10);
            if (!orderId) {
                return;
            }

            var endpoint = {/literal}'{url controller="Sviat.OrderManagerControl.OrderManagerControlAdmin@ping"}'{literal};
            var currentManagerId = parseInt(container.getAttribute('data-current-manager-id'), 10);
            var $ = window.jQuery;
            var $text = $(container).find('.fn_omc_managers_text');
            var $badge = $(container).find('.fn_omc_badge');
            var pingIntervalMs = 30000;
            var pingTimer = null;
            var inFlight = null;

            var renderManagers = function (managers) {
                if (!Array.isArray(managers) || managers.length === 0) {
                    $text.text('—');
                    $badge.removeClass('omc-badge--busy').addClass('omc-badge--self');
                    return;
                }

                var logins = managers.map(function (manager) {
                    return manager.login || '';
                }).filter(Boolean);
                $text.text(logins.join(', ') || '—');

                var isOnlyCurrentManager = managers.length === 1 && managers[0].id === currentManagerId;
                $badge.toggleClass('omc-badge--self', isOnlyCurrentManager)
                      .toggleClass('omc-badge--busy', !isOnlyCurrentManager);
            };

            var sendPing = function () {
                if (inFlight) {
                    return;
                }
                inFlight = $.ajax({
                    type: 'POST',
                    dataType: 'json',
                    url: endpoint,
                    // session_id у форку — CSRF-токен адмінки, і backend/index.php
                    // відхиляє без нього будь-який POST у бекенд (403).
                    // Вихід із літерального блоку обов'язковий: усередині нього
                    // Smarty нічого не підставляє, і в JS ішла сама назва змінної —
                    // backend/index.php бачив невалідний токен і віддавав 403.
                    // Назву тега тут не писати: Smarty 5 рахує вкладеність навіть
                    // усередині JS-коментаря і падає на зайвому закритті.
                    data: { order_id: orderId, session_id: {/literal}'{$smarty.session.id}'{literal} },
                    timeout: 10000
                }).done(function (response) {
                    if (response && response.success) {
                        renderManagers(response.managers || []);
                    }
                }).fail(function (xhr) {
                    // Сесія адмінки скінчилась (або менеджер вийшов у сусідній
                    // вкладці) - токен на цій сторінці вже ніколи не підійде.
                    // Без зупинки вкладка, лишена відкритою, б'є кожні 30 секунд
                    // і кожен удар лягає в лог як "Session expired".
                    if (!xhr || xhr.status === 0 || xhr.status === 401 || xhr.status === 403) {
                        stopPinging();
                        document.removeEventListener('visibilitychange', handleVisibilityChange);
                    }
                }).always(function () {
                    inFlight = null;
                });
            };

            var startPinging = function () {
                if (pingTimer !== null) {
                    return;
                }
                sendPing();
                pingTimer = window.setInterval(sendPing, pingIntervalMs);
            };

            var stopPinging = function () {
                if (pingTimer !== null) {
                    window.clearInterval(pingTimer);
                    pingTimer = null;
                }
            };

            var handleVisibilityChange = function () {
                if (document.hidden) {
                    stopPinging();
                } else {
                    startPinging();
                }
            };

            if (!document.hidden) {
                startPinging();
            }
            document.addEventListener('visibilitychange', handleVisibilityChange);
            window.addEventListener('pagehide', stopPinging);
        })();
    </script>
    {/literal}
{/if}
