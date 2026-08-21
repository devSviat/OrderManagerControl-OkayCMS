<?php

namespace Modules\Sviat\OrderManagerControl;

use PHPUnit\Framework\TestCase;

/**
 * Блок вбудовується в картку замовлення й мусить компілюватись і на форку
 * (Smarty 5), і на стоковій 4.5.2 (Smarty 3.1).
 *
 * Ці версії по-різному дивляться на виклик функції в позиції тега: Smarty 5
 * шукає модифікатор і мовчки знаходить, Smarty 3 бачить PHP-функцію й звіряє
 * її з `Design::$allowedPhpFunctions`. `join` там немає, тож на стоку картка
 * замовлення падала з `PHP function 'join' not allowed by security setting` -
 * фатально, сторінка не відкривалась узагалі.
 *
 * Правило просте: шаблон нічого не обчислює, готовий рядок дає екстендер.
 */
class TemplatePortabilityTest extends TestCase
{
    private const TEMPLATE = 'Okay/Modules/Sviat/OrderManagerControl/Backend/design/html/order_manager_control.tpl';

    /** Функції, які Smarty 3 віддасть на суд політики безпеки. */
    private const RISKY = ['join', 'implode', 'in_array', 'array_map', 'count', 'explode'];

    public function testTemplateCallsNoPhpFunctionInTagPosition(): void
    {
        foreach (self::RISKY as $function) {
            $this->assertStringNotContainsString(
                '{' . $function . '(',
                $this->markup(),
                "шаблон кличе {$function}() - на Smarty 3 це впаде під політикою безпеки"
            );
        }
    }

    public function testTemplateUsesNoPhpFunctionAsModifier(): void
    {
        foreach (self::RISKY as $function) {
            $this->assertDoesNotMatchRegularExpression(
                '~\|@?' . $function . '\b~',
                $this->markup(),
                "шаблон вживає |{$function} - на Smarty 3 це та сама політика безпеки"
            );
        }
    }

    /** Готовий рядок мусить приходити з PHP, інакше шаблон знову почне рахувати. */
    public function testExtenderProvidesTheJoinedList(): void
    {
        $extender = file_get_contents(
            $this->root() . '/Okay/Modules/Sviat/OrderManagerControl/Extenders/BackendExtender.php'
        );

        $this->assertStringContainsString('omc_active_managers_text', $extender);
        $this->assertStringContainsString('implode(', $extender);
        $this->assertStringContainsString('omc_active_managers_text', $this->markup());
    }

    /**
     * Порожній список показується прочерком, і це теж не має рахуватись у
     * шаблоні: `|@count` на Smarty 3 знову впирається в політику безпеки.
     */
    public function testBusyFlagComesFromPhpToo(): void
    {
        $this->assertStringContainsString('omc_is_busy', $this->markup());
        $this->assertDoesNotMatchRegularExpression('~\|@?count\b~', $this->markup());
    }

    private function markup(): string
    {
        return file_get_contents($this->root() . '/' . self::TEMPLATE);
    }

    private function root(): string
    {
        return dirname(__DIR__, 4);
    }
}
