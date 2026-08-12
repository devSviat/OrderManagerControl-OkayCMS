<?php

namespace Modules\Sviat\OrderManagerControl;

use Okay\Modules\Sviat\OrderManagerControl\Entities\OrderManagerControlEntity;
use Okay\Modules\Sviat\OrderManagerControl\Helpers\OrderManagerControlHelper as Helper;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\DataProvider;

require_once __DIR__ . '/Support/ModuleTestCase.php';

use Support\ModuleTestCase;

/**
 * Позначка «цю картку зараз відкрив менеджер X». Показується іншим менеджерам,
 * щоб двоє не обробляли одне замовлення одночасно. Два менеджери справді
 * відкривають картку в ту саму мілісекунду, тож гілка гонки при вставці —
 * не теоретична.
 */
#[AllowMockObjectsWithoutExpectations]
class OrderManagerControlHelperTest extends ModuleTestCase
{
    /** @var object[] */
    private array $calls = [];

    private function buildHelper(array $behaviour = []): Helper
    {
        $this->calls = [];
        $calls = &$this->calls;

        $entity = $this->mockEntity(
            OrderManagerControlEntity::class,
            ['find', 'findOne', 'add', 'update', 'delete']
        );

        $entity->method('find')->willReturnCallback(
            static function (array $filter) use (&$calls, $behaviour): array {
                $calls[] = ['find', $filter];
                return $behaviour['find'] ?? [];
            }
        );

        $findOneResults = $behaviour['findOne'] ?? [null];
        $entity->method('findOne')->willReturnCallback(
            static function (array $filter) use (&$calls, &$findOneResults) {
                $calls[] = ['findOne', $filter];
                return count($findOneResults) > 1 ? array_shift($findOneResults) : ($findOneResults[0] ?? null);
            }
        );

        $entity->method('add')->willReturnCallback(
            static function (object $row) use (&$calls, $behaviour) {
                $calls[] = ['add', $row];
                if (!empty($behaviour['addThrows'])) {
                    throw new \RuntimeException('Duplicate entry for key order_id_manager_id');
                }
                return 1;
            }
        );

        $entity->method('update')->willReturnCallback(
            static function ($id, $data) use (&$calls) {
                $calls[] = ['update', $id, $data];
                return 1;
            }
        );

        $entity->method('delete')->willReturnCallback(
            static function ($ids) use (&$calls) {
                $calls[] = ['delete', $ids];
                return 1;
            }
        );

        return new Helper($this->mockEntityFactory([OrderManagerControlEntity::class => $entity]));
    }

    /** @return array<int, array> */
    private function callsOf(string $method): array
    {
        return array_values(array_filter($this->calls, static fn (array $c) => $c[0] === $method));
    }

    // --- активні менеджери --------------------------------------------------

    /**
     * Поріг свіжості рахується від поточного часу, тож звіряємо мітку, а не
     * рядок: тест і код можуть розійтись на межі секунди.
     */
    public function testActiveManagersAreFilteredByTheFreshnessThreshold(): void
    {
        $helper = $this->buildHelper();
        $helper->getActiveManagers(42);

        $filter = $this->callsOf('find')[0][1];

        self::assertSame(42, $filter['order_id']);
        self::assertEqualsWithDelta(time() - Helper::ACTIVE_TTL, strtotime($filter['updated_at_min']), 2);
    }

    /**
     * Один менеджер із двома вкладками дає два рядки — у списку він мусить бути
     * один, інакше колега побачить «картку відкрито двома людьми».
     */
    public function testTheSameManagerIsListedOnlyOnce(): void
    {
        $helper = $this->buildHelper([
            'find' => [
                (object) ['manager_id' => 7, 'manager_login' => 'olena'],
                (object) ['manager_id' => 7, 'manager_login' => 'olena'],
                (object) ['manager_id' => 9, 'manager_login' => 'petro'],
            ],
        ]);

        self::assertSame(
            [
                ['id' => 7, 'login' => 'olena'],
                ['id' => 9, 'login' => 'petro'],
            ],
            $helper->getActiveManagers(42)
        );
    }

    /** Список має бути з послідовними ключами — інакше json_encode віддасть об'єкт замість масиву. */
    public function testResultIsAListNotAMapKeyedByManagerId(): void
    {
        $helper = $this->buildHelper([
            'find' => [(object) ['manager_id' => 77, 'manager_login' => 'olena']],
        ]);

        self::assertSame([0], array_keys($helper->getActiveManagers(42)));
    }

    public function testNoActiveManagersGivesEmptyList(): void
    {
        self::assertSame([], $this->buildHelper()->getActiveManagers(42));
    }

    // --- реєстрація присутності ---------------------------------------------

    /** @dataProvider invalidIdentifiersProvider */
    #[DataProvider('invalidIdentifiersProvider')]
    public function testInvalidIdentifiersAreRejectedWithoutTouchingTheDatabase(int $orderId, int $managerId): void
    {
        $helper = $this->buildHelper();

        self::assertFalse($helper->registerPresence($orderId, $managerId, 'olena'));
        self::assertSame([], $this->calls);
    }

    public static function invalidIdentifiersProvider(): array
    {
        return [
            'немає замовлення'  => [0, 7],
            'немає менеджера'   => [42, 0],
            'відʼємні'          => [-1, -1],
        ];
    }

    /** Повторний вхід у ту саму картку оновлює наявний рядок, а не плодить нові. */
    public function testExistingPresenceIsUpdatedNotDuplicated(): void
    {
        $helper = $this->buildHelper(['findOne' => [(object) ['id' => 15]]]);

        self::assertTrue($helper->registerPresence(42, 7, 'olena'));
        self::assertSame([], $this->callsOf('add'));

        [, $id, $data] = $this->callsOf('update')[0];
        self::assertSame(15, $id);
        self::assertSame('olena', $data['manager_login']);
        self::assertEqualsWithDelta(time(), strtotime($data['updated_at']), 2);
    }

    public function testFirstVisitInsertsARow(): void
    {
        $helper = $this->buildHelper(['findOne' => [null]]);

        self::assertTrue($helper->registerPresence(42, 7, 'olena'));

        $added = $this->callsOf('add')[0][1];
        self::assertSame(42, $added->order_id);
        self::assertSame(7, $added->manager_id);
        self::assertSame('olena', $added->manager_login);
        self::assertSame([], $this->callsOf('update'));
    }

    /**
     * Двоє менеджерів відкрили картку одночасно: обидва не знайшли рядка, обидва
     * пішли вставляти, другий отримав унікальний індекс в обличчя. Це має
     * закінчитись оновленням чужого рядка, а не винятком у відповіді ajax.
     */
    public function testConcurrentInsertFallsBackToUpdatingTheWinnersRow(): void
    {
        $helper = $this->buildHelper([
            'addThrows' => true,
            'findOne'   => [null, (object) ['id' => 21]],
        ]);

        self::assertTrue($helper->registerPresence(42, 7, 'olena'));

        self::assertCount(1, $this->callsOf('add'));
        self::assertCount(2, $this->callsOf('findOne'));
        self::assertSame(21, $this->callsOf('update')[0][1]);
    }

    /**
     * Якщо вставка впала, а рядка все одно немає — мовчки здаємось. Позначка
     * присутності не варта того, щоб валити відкриття картки замовлення.
     */
    public function testFailedInsertWithoutAWinnerIsSwallowed(): void
    {
        $helper = $this->buildHelper(['addThrows' => true, 'findOne' => [null, null]]);

        self::assertTrue($helper->registerPresence(42, 7, 'olena'));
        self::assertSame([], $this->callsOf('update'));
    }

    // --- прибирання ---------------------------------------------------------

    /** Поріг прибирання — TTL плюс запас, щоб не зносити рядки, які ще показуються. */
    public function testCleanupThresholdIsTtlPlusGrace(): void
    {
        $helper = $this->buildHelper();
        $helper->cleanupStale();

        $filter = $this->callsOf('find')[0][1];

        self::assertEqualsWithDelta(
            time() - Helper::ACTIVE_TTL - Helper::CLEANUP_GRACE,
            strtotime($filter['updated_at_max']),
            2
        );
    }

    public function testCleanupDeletesStaleRowsAndReportsTheCount(): void
    {
        $helper = $this->buildHelper([
            'find' => [(object) ['id' => 1], (object) ['id' => 2], (object) ['id' => 3]],
        ]);

        self::assertSame(3, $helper->cleanupStale());
        self::assertSame([1, 2, 3], $this->callsOf('delete')[0][1]);
    }

    /**
     * Без застарілих рядків delete() не викликається взагалі — інакше туди
     * пішов би порожній масив, а фільтр по порожньому масиву в цьому двигуні
     * вироджується у вибірку всієї таблиці.
     */
    public function testCleanupDoesNotCallDeleteWithAnEmptyList(): void
    {
        $helper = $this->buildHelper(['find' => []]);

        self::assertSame(0, $helper->cleanupStale());
        self::assertSame([], $this->callsOf('delete'));
    }
}
