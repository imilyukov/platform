<?php

namespace Oro\Bundle\EntityMergeBundle\Tests\Unit\Model\Strategy;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityMergeBundle\Model\Accessor\RelationAccessor;
use Oro\Bundle\EntityMergeBundle\Model\MergeModes;
use Oro\Bundle\EntityMergeBundle\Model\Strategy\MergeStategy;
use Oro\Bundle\EntityMergeBundle\Tests\Unit\Stub\CollectionItemStub;
use Oro\Bundle\EntityMergeBundle\Tests\Unit\Stub\EntityStub;

class MergeStrategyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MergeStategy $strategy
     */
    protected $strategy;

    /**
     * @var
     */
    protected $entityManager;

    public function setUp()
    {
        $this->entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->setMethods(['getMetadataFactory', 'getRepository'])
            ->getMock();

        $accessor = new RelationAccessor($this->entityManager);

        $this->strategy = new MergeStategy($accessor, $this->entityManager);
    }

    public function testNotSupports()
    {
        $fieldData = $this->createFieldData();
        $fieldData
            ->expects($this->once())
            ->method('getMode')
            ->will($this->returnValue(MergeModes::REPLACE));

        $this->assertFalse($this->strategy->supports($fieldData));
    }

    public function testSupports()
    {
        $fieldData         = $this->createFieldData();
        $fieldMetadataData = $this->createFieldMetadata();
        $doctrineMetadata  = $this->createDoctrineMetadata();

        $fieldData
            ->expects($this->once())
            ->method('getMode')
            ->will($this->returnValue(MergeModes::MERGE));

        $fieldMetadataData
            ->expects($this->once())
            ->method('getDoctrineMetadata')
            ->will($this->returnValue($doctrineMetadata));

        $fieldData
            ->expects($this->once())
            ->method('getMetadata')
            ->will($this->returnValue($fieldMetadataData));

        $this->assertTrue($this->strategy->supports($fieldData));
    }

    public function testMerge()
    {
        $fieldData         = $this->createFieldData();
        $fieldMetadataData = $this->createFieldMetadata();
        $entityData        = $this->createEntityData();
        $masterEntity      = new EntityStub(1);
        $sourceEntity      = new EntityStub(2);
        $collectionItem1   = new CollectionItemStub(1);
        $collectionItem2   = new CollectionItemStub(2);
        $masterEntity->addCollectionItem($collectionItem1);
        $sourceEntity->addCollectionItem($collectionItem2);

        $entities = [$masterEntity, $sourceEntity];

        $fieldData
            ->expects($this->once())
            ->method('getEntityData')
            ->will($this->returnValue($entityData));

        $fieldData
            ->expects($this->once())
            ->method('getMetadata')
            ->will($this->returnValue($fieldMetadataData));

        $entityData
            ->expects($this->once())
            ->method('getEntities')
            ->will($this->returnValue($entities));

        $entityData
            ->expects($this->once())
            ->method('getMasterEntity')
            ->will($this->returnValue($masterEntity));

        $doctrineMetadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->setMethods([])
            ->disableOriginalConstructor()
            ->getMock();

        $metadataFactory = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadataFactory')
            ->setMethods([])
            ->disableOriginalConstructor()
            ->getMock();

        $metadataFactory->expects($this->any())
            ->method('getMetadataFor')
            ->will($this->returnValue($doctrineMetadata));

        $doctrineMetadata->expects($this->at(0))
            ->method('getIdentifierValues')
            ->will($this->returnValue([1]));

        $doctrineMetadata->expects($this->at(1))
            ->method('getIdentifierValues')
            ->will($this->returnValue([2]));

        $this->entityManager->expects($this->any())
            ->method('getMetadataFactory')
            ->will($this->returnValue($metadataFactory));

        $fieldDoctrineMetadata = $this->createDoctrineMetadata();
        $fieldMetadataData
            ->expects($this->any())
            ->method('getDoctrineMetadata')
            ->will($this->returnValue($fieldDoctrineMetadata));

        $fieldMetadataData
            ->expects($this->any())
            ->method('getFieldName')
            ->will($this->returnValue('collection'));

        $fieldMetadataData
            ->expects($this->any())
            ->method('has')
            ->will($this->returnValue(true));

        $fieldMetadataData
            ->expects($this->any())
            ->method('get')
            ->will($this->returnValue('setEntityStub'));

        $repository = $this
            ->getMockBuilder('Doctrine\Common\Persistence\ObjectRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityManager->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValue($repository));

        $repository
            ->expects($this->at(0))
            ->method('findBy')
            ->will($this->returnValue([$masterEntity->getCollection()->first()]));

        $repository
            ->expects($this->at(1))
            ->method('findBy')
            ->will($this->returnValue([$sourceEntity->getCollection()->first()]));

        $this->strategy->merge($fieldData);

        $this->assertEquals($masterEntity, $collectionItem1->getEntityStub());
        $this->assertEquals($masterEntity, $collectionItem2->getEntityStub());
    }

    public function testGetName()
    {
        $this->markTestIncomplete();
    }

    protected function createFieldData()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Data\FieldData')
            ->setMethods([])
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function createFieldMetadata()
    {
        return $this
            ->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata')
            ->setMethods([])
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function createDoctrineMetadata()
    {
        return $this
            ->getMockBuilder('Oro\Bundle\EntityMergeBundle\Metadata\DoctrineMetadata')
            ->disableOriginalConstructor()
            ->getMock();
    }

    protected function createEntityData()
    {
        return $this->getMockBuilder('Oro\Bundle\EntityMergeBundle\Data\EntityData')
            ->setMethods([])
            ->disableOriginalConstructor()
            ->getMock();
    }
}
