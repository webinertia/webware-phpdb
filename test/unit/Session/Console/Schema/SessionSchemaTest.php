<?php

declare(strict_types=1);

namespace WebwareTest\PhpDb\Session\Console\Schema;

use PhpDb\Adapter\Driver\ConnectionInterface;
use PhpDb\Adapter\Driver\DriverInterface;
use PhpDb\Mysql\AdapterPlatform;
use PhpDb\Mysql\Sql\Platform;
use PhpDb\Session\Console\Schema\SessionSchema;
use PhpDb\Sql\Ddl\Column\ColumnInterface;
use PhpDb\Sql\Ddl\Constraint\ConstraintInterface;
use PhpDb\Sql\Ddl\Constraint\PrimaryKey;
use PhpDb\Sql\Ddl\CreateTable;
use PhpDb\Sql\Ddl\DropTable;
use PhpDb\Sql\Ddl\Index\Index;
use PhpDb\Sql\Literal;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_keys;
use function array_map;
use function preg_replace;

#[CoversClass(SessionSchema::class)]
#[CoversMethod(SessionSchema::class, 'sessionTable')]
#[CoversMethod(SessionSchema::class, 'dropTable')]
final class SessionSchemaTest extends TestCase
{
    #[Test]
    public function dropTableRendersDropIfExists(): void
    {
        $sql = $this->renderSql(new SessionSchema()->dropTable('session'));

        self::assertStringContainsString('DROP TABLE IF EXISTS `session`', $sql);
    }

    #[Test]
    public function sessionTableCarriesThePrimaryKeyAndTheExpiryIndex(): void
    {
        $constraints = $this->constraints(new SessionSchema()->sessionTable('session'));

        self::assertCount(2, $constraints);
        self::assertInstanceOf(PrimaryKey::class, $constraints[0]);
        self::assertInstanceOf(Index::class, $constraints[1]);
        self::assertSame('idx_expires_at', $constraints[1]->getName());
    }

    #[Test]
    public function sessionTableHasExactlyTheColumnsBothClassesWrite(): void
    {
        $names = array_map(
            static fn(ColumnInterface $column): string => $column->getName(),
            $this->columns(new SessionSchema()->sessionTable('session')),
        );

        self::assertSame(['id', 'payload', 'modified_at', 'expires_at'], $names);
    }

    #[Test]
    public function sessionTableOptionsSetEngineCharsetAndCollation(): void
    {
        $options = new SessionSchema()->sessionTable('session')
            ->getOptions();

        self::assertSame(['engine', 'default charset', 'collate'], array_keys($options));
        self::assertInstanceOf(Literal::class, $options['engine']);
        self::assertSame('InnoDB', $options['engine']->getLiteral());
        self::assertSame('utf8mb4', $options['default charset']->getLiteral());
        self::assertSame('utf8mb4_unicode_ci', $options['collate']->getLiteral());
    }

    #[Test]
    public function sessionTableRendersTheExactStatement(): void
    {
        $sql = (string) preg_replace(
            pattern    : '/\s+/',
            replacement: ' ',
            subject    : $this->renderSql(new SessionSchema()->sessionTable('session')),
        );

        self::assertSame(
            'CREATE TABLE IF NOT EXISTS `session` ( '
                . '`id` VARCHAR(64) NOT NULL, '
                . '`payload` MEDIUMTEXT NOT NULL, '
                . '`modified_at` DATETIME NOT NULL, '
                . '`expires_at` DATETIME NOT NULL , '
                . 'PRIMARY KEY (`id`), '
                . 'INDEX `idx_expires_at`(`expires_at`) '
                . ') ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci',
            $sql,
        );
    }

    #[Test]
    public function sessionTableUsesTheTableNameItIsGiven(): void
    {
        $sql = $this->renderSql(new SessionSchema()->sessionTable('app_session'));

        self::assertStringContainsString('`app_session`', $sql);
    }

    /**
     * @return list<ColumnInterface>
     */
    private function columns(CreateTable $table): array
    {
        /** @var list<ColumnInterface> */
        return $table->getRawState('columns');
    }

    /**
     * @return list<ConstraintInterface>
     */
    private function constraints(CreateTable $table): array
    {
        /** @var list<ConstraintInterface> */
        return $table->getRawState('constraints');
    }

    private function renderSql(CreateTable|DropTable $sql): string
    {
        $driver     = $this->createStub(DriverInterface::class);
        $connection = $this->createStub(ConnectionInterface::class);
        $driver->method('getConnection')->willReturn($connection);

        $platform = new Platform();
        $platform->setSubject($sql);

        return $platform->getSqlString(new AdapterPlatform($driver));
    }
}
