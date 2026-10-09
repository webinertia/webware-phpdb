<?php

declare(strict_types=1);

namespace WebwareTest\PhpDb;

use PhpDb\SchemaFactory;
use PhpDb\Sql\TableIdentifier;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psl\Type\Exception\AssertException;

#[CoversClass(SchemaFactory::class)]
#[CoversMethod(SchemaFactory::class, '__construct')]
#[CoversMethod(SchemaFactory::class, '__invoke')]
#[CoversMethod(SchemaFactory::class, 'backup')]
#[CoversMethod(SchemaFactory::class, 'getPrefix')]
#[CoversMethod(SchemaFactory::class, 'getSchema')]
#[CoversMethod(SchemaFactory::class, 'getSeparator')]
#[CoversMethod(SchemaFactory::class, 'getBackupPrefix')]
#[CoversMethod(SchemaFactory::class, 'getBackupSchema')]
final class SchemaFactoryTest extends TestCase
{
    #[Test]
    public function backupAppliesBackupPrefixAndSchema(): void
    {
        $factory = new SchemaFactory([
            'prefix'        => 'ww',
            'schema'        => 'public',
            'backup_prefix' => 'bck',
            'backup_schema' => 'backup',
        ]);
        $identifier = $factory->backup(DefaultSchema::Session);

        self::assertSame('bck_session', $identifier->getTable());
        self::assertSame('backup', $identifier->getSchema());
    }

    #[Test]
    public function backupFallsBackToLiveSchemaWhenNoBackupSchema(): void
    {
        $factory = new SchemaFactory([
            'schema'        => 'public',
            'backup_prefix' => 'bck',
        ]);
        $identifier = $factory->backup(DefaultSchema::Session);

        self::assertSame('public', $identifier->getSchema());
    }

    #[Test]
    public function backupIgnoresLivePrefix(): void
    {
        $factory = new SchemaFactory([
            'prefix'        => 'ww',
            'prefixes'      => ['session' => 'sess'],
            'backup_prefix' => 'bck',
        ]);
        $identifier = $factory->backup(DefaultSchema::Session);

        self::assertSame('bck_session', $identifier->getTable());
    }

    #[Test]
    public function backupPrefersCallTimePrefixOverBackupPrefix(): void
    {
        $factory    = new SchemaFactory(['backup_prefix' => 'bck']);
        $identifier = $factory->backup(DefaultSchema::Session, prefix: 'tmp');

        self::assertSame('tmp_session', $identifier->getTable());
    }

    #[Test]
    public function backupPrefersCallTimeSchemaOverPerTableSchema(): void
    {
        $factory = new SchemaFactory([
            'backup_prefix' => 'bck',
            'schemas'       => ['session' => 'tenant'],
        ]);
        $identifier = $factory->backup(DefaultSchema::Session, schemaName: 'archive');

        self::assertSame('archive', $identifier->getSchema());
    }

    #[Test]
    public function backupPrefersCallTimeSeparatorOverConfigured(): void
    {
        $factory = new SchemaFactory([
            'backup_prefix' => 'bck',
            'separator'     => '__',
        ]);
        $identifier = $factory->backup(DefaultSchema::Session, separator: '--');

        self::assertSame('bck--session', $identifier->getTable());
    }

    #[Test]
    public function backupPrefersPerTableSchemaOverBackupSchema(): void
    {
        $factory = new SchemaFactory([
            'backup_prefix' => 'bck',
            'backup_schema' => 'backup',
            'schemas'       => ['session' => 'tenant'],
        ]);
        $identifier = $factory->backup(DefaultSchema::Session);

        self::assertSame('tenant', $identifier->getSchema());
    }

    #[Test]
    public function backupSupportsCallTimeSchemaRedirect(): void
    {
        $factory = new SchemaFactory([
            'backup_prefix' => 'bck',
            'backup_schema' => 'backup',
        ]);
        $identifier = $factory->backup(DefaultSchema::Session, schemaName: 'archive');

        self::assertSame('archive', $identifier->getSchema());
    }

    #[Test]
    public function itAppliesCallTimeOverrides(): void
    {
        $factory = new SchemaFactory([
            'prefix' => 'ww',
            'schema' => 'public',
        ]);
        $identifier = $factory(DefaultSchema::Session, schemaName: 'backup', prefix: 'tmp');

        self::assertSame('backup', $identifier->getSchema());
        self::assertSame('tmp', $identifier->getPrefix());
        self::assertSame('tmp_session', $identifier->getTable());
    }

    #[Test]
    public function itAppliesConfiguredPrefix(): void
    {
        $factory    = new SchemaFactory(['prefix' => 'ww']);
        $identifier = $factory(DefaultSchema::Session);

        self::assertSame('ww_session', $identifier->getTable());
        self::assertSame('session', $identifier->getUnprefixedTable());
        self::assertSame('ww', $identifier->getPrefix());
        self::assertNull($identifier->getSchema());
    }

    #[Test]
    public function itDefaultsSeparatorToUnderscore(): void
    {
        $factory = new SchemaFactory();

        self::assertSame(TableIdentifier::SEPARATOR, $factory->getSeparator());
    }

    #[Test]
    public function itExposesConfiguredValuesViaGetters(): void
    {
        $factory = new SchemaFactory([
            'prefix'        => 'ww',
            'separator'     => '__',
            'schema'        => 'public',
            'backup_prefix' => 'bck',
            'backup_schema' => 'backup',
        ]);

        self::assertSame('ww', $factory->getPrefix());
        self::assertSame('__', $factory->getSeparator());
        self::assertSame('public', $factory->getSchema());
        self::assertSame('bck', $factory->getBackupPrefix());
        self::assertSame('backup', $factory->getBackupSchema());
    }

    #[Test]
    public function itPrefersPerTablePrefixOverAppWidePrefix(): void
    {
        $factory = new SchemaFactory([
            'prefix'   => 'ww',
            'prefixes' => ['session' => 'sess'],
        ]);
        $identifier = $factory(DefaultSchema::Session);

        self::assertSame('sess_session', $identifier->getTable());
    }

    #[Test]
    public function itPrefersPerTableSchemaOverAppWideSchema(): void
    {
        $factory = new SchemaFactory([
            'schema'  => 'public',
            'schemas' => ['session' => 'tenant'],
        ]);
        $identifier = $factory(DefaultSchema::Session);

        self::assertSame('tenant', $identifier->getSchema());
    }

    #[Test]
    public function itPreservesSchemaFromEnumConstant(): void
    {
        $factory    = new SchemaFactory(['prefix' => 'ww']);
        $identifier = $factory(WebwareSchema::Session);

        self::assertSame('webware', $identifier->getSchema());
    }

    #[Test]
    public function itRejectsEmptyEnumValue(): void
    {
        $this->expectException(AssertException::class);

        new SchemaFactory()(EmptySchema::Session);
    }

    #[Test]
    public function itRejectsNonStringEnumValue(): void
    {
        $this->expectException(AssertException::class);

        new SchemaFactory()(IntSchema::Session);
    }

    #[Test]
    public function itRejectsUnknownConfigKey(): void
    {
        $this->expectException(AssertException::class);

        new SchemaFactory(['unknown' => 'value']);
    }

    #[Test]
    public function itThrowsOnEmptyBackupPrefix(): void
    {
        $this->expectException(AssertException::class);

        new SchemaFactory(['backup_prefix' => '']);
    }

    #[Test]
    public function itThrowsOnEmptyBackupSchema(): void
    {
        $this->expectException(AssertException::class);

        new SchemaFactory(['backup_schema' => '']);
    }

    #[Test]
    public function itThrowsOnEmptyPrefix(): void
    {
        $this->expectException(AssertException::class);

        new SchemaFactory(['prefix' => '']);
    }

    #[Test]
    public function itThrowsOnEmptySchema(): void
    {
        $this->expectException(AssertException::class);

        new SchemaFactory(['schema' => '']);
    }

    #[Test]
    public function itThrowsOnEmptySeparator(): void
    {
        $this->expectException(AssertException::class);

        new SchemaFactory(['separator' => '']);
    }

    #[Test]
    public function itUsesCustomSeparator(): void
    {
        $factory = new SchemaFactory([
            'prefix'    => 'ww',
            'separator' => '__',
        ]);
        $identifier = $factory(DefaultSchema::Session);

        self::assertSame('ww__session', $identifier->getTable());
    }
}
