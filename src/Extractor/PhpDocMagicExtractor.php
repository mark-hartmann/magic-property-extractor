<?php

namespace Hartmann\PropertyInfo\Extractor;


use InvalidArgumentException;
use LogicException;
use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\DocBlockFactory;
use phpDocumentor\Reflection\DocBlockFactoryInterface;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\PropertyInfo\PropertyAccessExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyDescriptionExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyListExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Util\PhpDocTypeHelper;

/**
 * Extracts magic properties using a PHPDoc parser.
 *
 * @author Mark Hartmann <contact@mark-hartmann.com>
 */
class PhpDocMagicExtractor implements PropertyDescriptionExtractorInterface, PropertyTypeExtractorInterface, PropertyAccessExtractorInterface, PropertyListExtractorInterface
{
    private $docBlocks = [];
    private $docBlockFactory;
    private $phpDocTypeHelper;

    /**
     * @param DocBlockFactoryInterface $docBlockFactory
     */
    public function __construct(DocBlockFactoryInterface $docBlockFactory = null)
    {

        if (!class_exists(DocBlockFactory::class)) {
            throw new LogicException(sprintf('Unable to use the "%s" class as the "phpdocumentor/reflection-docblock" package is not installed.',
                __CLASS__));
        }

        $this->docBlockFactory = $docBlockFactory ?: DocBlockFactory::createInstance();
        $this->phpDocTypeHelper = new PhpDocTypeHelper();
    }

    /**
     * {@inheritdoc}
     */
    public function getShortDescription($class, $property, array $context = []): ?string
    {
        /** @var $docBlock DocBlock */
        [$docBlock] = $this->getDocBlock($class);

        if (!$docBlock) {
            return null;
        }

        $properties = $this->getMagicProperties($docBlock);

        foreach ($properties as $prop) {
            /** @var \phpDocumentor\Reflection\DocBlock\Tags\Property $prop */
            if ($prop->getVariableName() === $property) {

                $description = $prop->getDescription();
                if ($description !== null && $description->render() !== '') {
                    return $description->render();
                }

                return null;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getLongDescription($class, $property, array $context = []): ?string
    {
        return $this->getShortDescription($class, $property, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function getTypes($class, $property, array $context = []): ?array
    {
        /** @var $docBlock DocBlock */
        [$docBlock] = $this->getDocBlock($class);

        if (!$docBlock || !in_array($property, $this->getProperties($class), true)) {
            return null;
        }

        $properties = $this->getMagicProperties($docBlock);

        foreach ($properties as $prop) {
            /** @var \phpDocumentor\Reflection\DocBlock\Tags\Property $prop */
            if ($prop->getVariableName() === $property) {
                if ($prop->getType() !== null) {
                    return $this->phpDocTypeHelper->getTypes($prop->getType());
                }

                return null;
            }
        }

        return null;
    }

    /**
     * Is the property readable?
     *
     * @param string $class
     * @param string $property
     * @param array  $context
     *
     * @return bool|null
     */
    public function isReadable($class, $property, array $context = []): ?bool
    {
        /** @var $docBlock DocBlock */
        [$docBlock] = $this->getDocBlock($class);

        if (!$docBlock || !in_array($property, $this->getProperties($class), true)) {
            return null;
        }

        foreach ($docBlock->getTagsByName('property') as $prop) {
            /** @var \phpDocumentor\Reflection\DocBlock\Tags\Property $prop */
            if ($prop->getVariableName() === $property) {
                return true;
            }
        }

        foreach ($docBlock->getTagsByName('property-read') as $prop) {
            /** @var \phpDocumentor\Reflection\DocBlock\Tags\PropertyRead $prop */
            if ($prop->getVariableName() === $property) {
                return true;
            }
        }

        return false;
    }

    /**
     * Is the property writable?
     *
     * @param string $class
     * @param string $property
     * @param array  $context
     *
     * @return bool|null
     */
    public function isWritable($class, $property, array $context = []): ?bool
    {
        /** @var $docBlock DocBlock */
        [$docBlock] = $this->getDocBlock($class);

        if (!$docBlock || !in_array($property, $this->getProperties($class), true)) {
            return null;
        }

        foreach ($docBlock->getTagsByName('property') as $prop) {
            /** @var \phpDocumentor\Reflection\DocBlock\Tags\Property $prop */
            if ($prop->getVariableName() === $property) {
                return true;
            }
        }

        foreach ($docBlock->getTagsByName('property-write') as $prop) {
            /** @var \phpDocumentor\Reflection\DocBlock\Tags\PropertyWrite $prop */
            if ($prop->getVariableName() === $property) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets the list of properties available for the given class.
     *
     * @param string $class
     * @param array  $context
     *
     * @return string[]|null
     */
    public function getProperties($class, array $context = []): ?array
    {
        /** @var $docBlock DocBlock */
        [$docBlock] = $this->getDocBlock($class);

        if (!$docBlock) {
            return null;
        }

        $propertyTags = $this->getMagicProperties($docBlock);

        $properties = [];
        foreach ($propertyTags as $property) {
            $properties[] = $property->getVariableName();
        }

        usort($properties, static function ($a, $b) use ($class) {
            $docComment = (new ReflectionClass($class))->getDocComment();

            return strpos($docComment, $a) > strpos($docComment, $b);
        });

        return $properties;
    }

    /**
     * @param \phpDocumentor\Reflection\DocBlock $docBlock
     *
     * @return \phpDocumentor\Reflection\DocBlock\Tags\Property[]
     */
    private function getMagicProperties(DocBlock $docBlock): array
    {
        return array_merge(
            $docBlock->getTagsByName('property'),
            $docBlock->getTagsByName('property-read'),
            $docBlock->getTagsByName('property-write')
        );
    }


    private function getDocBlock(string $class): array
    {
        $propertyHash = sprintf('%s', $class);

        if (isset($this->docBlocks[$propertyHash])) {
            return $this->docBlocks[$propertyHash];
        }

        $data = [null];
        if ($docBlock = $this->getDocBlockFromClass($class)) {
            $data = [$docBlock];
        }

        return $this->docBlocks[$propertyHash] = $data;
    }


    private function getDocBlockFromClass(string $class): ?DocBlock
    {
        try {
            $reflectionClass = new ReflectionClass($class);
        } catch (ReflectionException $e) {
            return null;
        }

        try {
            return $this->docBlockFactory->create($reflectionClass);
        } catch (InvalidArgumentException $e) {
            return null;
        }
    }
}