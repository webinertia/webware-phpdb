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
        $identifier = $factory->backup(AclSchema::Role);

        self::assertSame('bck_acl_role', $identifier->getTable());
        self::assertSame('backup', $identifier->getSchema());
    }

    #[Test]
    public function backupFallsBackToLiveSchemaWhenNoBackupSchema(): void
    {
        $factory = new SchemaFactory([
            'schema'        => 'public',
            'backup_prefix' => 'bck',
        ]);
        $identifier = $factory->backup(AclSchema::Role);

        self::assertSame('public', $identifier->getSchema());
    }

    #[Test]
    public function backupIgnoresLivePrefix(): void
    {
        $factory = new SchemaFactory([
            'prefix'        => 'ww',
            'prefixes'      => ['acl_role' => 'acl'],
            'backup_prefix' => 'bck',
        ]);
        $identifier = $factory->backup(AclSchema::Role);

        self::assertSame('bck_acl_role', $identifier->getTable());
    }

    #[Test]
    public function backupPrefersCallTimePrefixOverBackupPrefix(): void
    {
        $factory    = new SchemaFactory(['backup_prefix' => 'bck']);
        $identifier = $factory->backup(AclSchema::Role, prefix: 'tmp');

        self::assertSame('tmp_acl_role', $identifier->getTable());
    }

    #[Test]
    public function backupPrefersCallTimeSchemaOverPerTableSchema(): void
    {
        $factory = new SchemaFactory([
            'backup_prefix' => 'bck',
            'schemas'       => ['acl_role' => 'tenant'],
        ]);
        $identifier = $factory->backup(AclSchema::Role, schemaName: 'archive');

        self::assertSame('archive', $identifier->getSchema());
    }

    #[Test]
    public function backupPrefersCallTimeSeparatorOverConfigured(): void
    {
        $factory = new SchemaFactory([
            'backup_prefix' => 'bck',
            'separator'     => '__',
        ]);
        $identifier = $factory->backup(AclSchema::Role, separator: '--');

        self::assertSame('bck--acl_role', $identifier->getTable());
    }

    #[Test]
    public function backupPrefersPerTableSchemaOverBackupSchema(): void
    {
        $factory = new SchemaFactory([
            'backup_prefix' => 'bck',
            'backup_schema' => 'backup',
            'schemas'       => ['acl_role' => 'tenant'],
        ]);
        $identifier = $factory->backup(AclSchema::Role);

        self::assertSame('tenant', $identifier->getSchema());
    }

    #[Test]
    public function backupSupportsCallTimeSchemaRedirect(): void
    {
        $factory = new SchemaFactory([
            'backup_prefix' => 'bck',
            'backup_schema' => 'backup',
        ]);
        $identifier = $factory->backup(AclSchema::Role, schemaName: 'archive');

        self::assertSame('archive', $identifier->getSchema());
    }

    #[Test]
    public function itAppliesCallTimeOverrides(): void
    {
        $factory = new SchemaFactory([
            'prefix' => 'ww',
            'schema' => 'public',
        ]);
        $identifier = $factory(AclSchema::Role, schemaName: 'backup', prefix: 'tmp');

        self::assertSame('backup', $identifier->getSchema());
        self::assertSame('tmp', $identifier->getPrefix());
        self::assertSame('tmp_acl_role', $identifier->getTable());
    }

    #[Test]
    public function itAppliesConfiguredPrefix(): void
    {
        $factory    = new SchemaFactory(['prefix' => 'ww']);
        $identifier = $factory(AclSchema::Role);

        self::assertSame('ww_acl_role', $identifier->getTable());
        self::assertSame('acl_role', $identifier->getUnprefixedTable());
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
            'prefixes' => ['acl_role' => 'acl'],
        ]);
        $identifier = $factory(AclSchema::Role);

        self::assertSame('acl_acl_role', $identifier->getTable());
    }

    #[Test]
    public function itPrefersPerTableSchemaOverAppWideSchema(): void
    {
        $factory = new SchemaFactory([
            'schema'  => 'public',
            'schemas' => ['acl_role' => 'tenant'],
        ]);
        $identifier = $factory(AclSchema::Role);

        self::assertSame('tenant', $identifier->getSchema());
    }

    #[Test]
    public function itPreservesSchemaFromEnumConstant(): void
    {
        $factory    = new SchemaFactory(['prefix' => 'ww']);
        $identifier = $factory(PublicSchema::Role);

        self::assertSame('public', $identifier->getSchema());
    }

    #[Test]
    public function itRejectsEmptyEnumValue(): void
    {
        $this->expectException(AssertException::class);

        new SchemaFactory()(EmptySchema::Role);
    }

    #[Test]
    public function itRejectsNonStringEnumValue(): void
    {
        $this->expectException(AssertException::class);

        new SchemaFactory()(IntSchema::Role);
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
        $identifier = $factory(AclSchema::Role);

        self::assertSame('ww__acl_role', $identifier->getTable());
    }
}
