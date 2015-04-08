<?php

namespace OroCRM\Bundle\MailChimpBundle\Tests\Unit\Entity;

use OroCRM\Bundle\MailChimpBundle\Entity\ExtendedMergeVar;
use Symfony\Component\PropertyAccess\PropertyAccess;

class ExtendedMergeVarTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ExtendedMergeVar
     */
    private $entity;

    protected function setUp()
    {
        $this->entity = new ExtendedMergeVar();
    }

    public function testObjectInitialization()
    {
        $entity = new ExtendedMergeVar();

        $this->assertEquals(ExtendedMergeVar::STATE_ADD, $entity->getState());
        $this->assertEquals(ExtendedMergeVar::TAG_FIELD_TYPE, $entity->getFieldType());
        $this->assertFalse($entity->getRequire());
        $this->assertNull($entity->getName());
        $this->assertNull($entity->getLabel());
        $this->assertNull($entity->getTag());
    }

    public function testGetId()
    {
        $this->assertNull($this->entity->getId());

        $value = 8;
        $idReflection = new \ReflectionProperty(get_class($this->entity), 'id');
        $idReflection->setAccessible(true);
        $idReflection->setValue($this->entity, $value);
        $this->assertEquals($value, $this->entity->getId());
    }

    /**
     * @dataProvider settersAndGettersDataProvider
     * @param string $property
     * @param mixed $value
     * @param mixed $default
     */
    public function testSettersAndGetters($property, $value, $default = null)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        $this->assertEquals(
            $default,
            $propertyAccessor->getValue($this->entity, $property)
        );

        $propertyAccessor->setValue($this->entity, $property, $value);

        $this->assertEquals(
            $value,
            $propertyAccessor->getValue($this->entity, $property)
        );
    }

    /**
     * @return array
     */
    public function settersAndGettersDataProvider()
    {
        return [
            ['staticSegment', $this->getMock('OroCRM\\Bundle\\MailChimpBundle\\Entity\\StaticSegment')],
            ['label', 'Dummy Label'],
            ['state', ExtendedMergeVar::STATE_SYNCED, ExtendedMergeVar::STATE_ADD]
        ];
    }

    /**
     * @dataProvider setNameDataProvider
     * @param mixed $value
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Name must be not empty string.
     */
    public function testSetNameWhenInputIsWrong($value)
    {
        $this->entity->setName($value);
    }

    public function setNameDataProvider()
    {
        return array(
            array(''),
            array(123),
            array(array()),
            array(new \ArrayIterator(array()))
        );
    }

    public function testSetAndGetName()
    {
        $this->assertNull($this->entity->getName());
        $name = 'total';
        $expectedTag = ExtendedMergeVar::TAG_PREFIX . strtoupper($name);
        $this->entity->setName($name);

        $this->assertEquals($name, $this->entity->getName());
        $this->assertEquals($expectedTag, $this->entity->getTag());
    }

    /**
     * @dataProvider tagGenerationDataProvider
     * @param string $value
     * @param string $expected
     */
    public function testTagGenerationWithDifferentNameLength($value, $expected)
    {
        $this->entity->setName($value);

        $this->assertEquals($expected, $this->entity->getTag());
    }

    public function tagGenerationDataProvider()
    {
        return array(
            array('total', ExtendedMergeVar::TAG_PREFIX . 'TOTAL'),
            array('entity_total', ExtendedMergeVar::TAG_PREFIX . 'NTTY_TTL'),
            array('anyEntityAttr', ExtendedMergeVar::TAG_PREFIX . 'NYNTTYTT')
        );
    }

    public function testIsAddState()
    {
        $this->entity->setState(ExtendedMergeVar::STATE_ADD);
        $this->assertTrue($this->entity->isAddState());
    }

    public function testIsRemoveState()
    {
        $this->entity->setState(ExtendedMergeVar::STATE_REMOVE);
        $this->assertTrue($this->entity->isRemoveState());
    }

    public function testSetSyncedState()
    {
        $this->entity->setSyncedState();
        $this->assertEquals(ExtendedMergeVar::STATE_SYNCED, $this->entity->getState());
    }

    public function testSetDroppedState()
    {
        $this->entity->setDroppedState();
        $this->assertEquals(ExtendedMergeVar::STATE_DROPPED, $this->entity->getState());
    }
}
