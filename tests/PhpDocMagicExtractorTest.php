<?php

namespace Hartmann\PropertyInfo\Tests;


use Hartmann\PropertyInfo\Extractor\PhpDocMagicExtractor;
use Hartmann\PropertyInfo\Tests\Fixtures\Dummy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Type;

class PhpDocMagicExtractorTest extends TestCase
{
    private $extractor;

    public function setUp()
    {
        $this->extractor = new PhpDocMagicExtractor();
    }

    /**
     * @dataProvider propertyProvider
     *
     * @param            $property
     * @param            $types
     * @param            $shortDesc
     * @param            $longDesc
     */
    public function testExtract($property, $types, $shortDesc, $longDesc): void
    {
        $this->assertEquals($types, $this->extractor->getTypes(Dummy::class, $property));
        $this->assertSame($shortDesc, $this->extractor->getShortDescription(Dummy::class, $property));
        $this->assertSame($longDesc, $this->extractor->getLongDescription(Dummy::class, $property));
    }

    /**
     * @dataProvider propertyReadabilityProvider
     *
     * @param $property
     * @param $readable
     */
    public function testIsReadable($property, $readable): void
    {
        $this->assertSame($readable, $this->extractor->isReadable(Dummy::class, $property));
    }

    /**
     * @dataProvider propertyWriteabilityProvider
     *
     * @param $property
     * @param $writeable
     */
    public function testIsWriteable($property, $writeable): void
    {
        $this->assertSame($writeable, $this->extractor->isWritable(Dummy::class, $property));
    }

    public function testGetProperties(): void
    {
        $this->assertEquals([
            'description',
            'tags',
            'foo',
        ], $this->extractor->getProperties(Dummy::class));
    }

    /**
     * @return array[]
     */
    public function propertyProvider(): array
    {
        return [
            ['description', [new Type(Type::BUILTIN_TYPE_STRING)], null, null],
            [
                'tags',
                [
                    new Type(Type::BUILTIN_TYPE_ARRAY, false, false, true, new Type(Type::BUILTIN_TYPE_INT),
                        new Type(Type::BUILTIN_TYPE_STRING)),
                ],
                'Array with tags',
                'Array with tags',
            ],
            [
                'updatedAt',
                null,
                null,
                null,
            ],
        ];
    }

    public function propertyReadabilityProvider(): array
    {
        return [
            ['description', true],
            ['tags', true],
            ['foo', false],
            ['updatedAt', null],
        ];
    }

    public function propertyWriteabilityProvider(): array
    {
        return [
            ['description', true],
            ['tags', false],
            ['foo', true],
            ['updatedAt', null],
        ];
    }
}