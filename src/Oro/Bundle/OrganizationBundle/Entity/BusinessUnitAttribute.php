<?php

namespace Oro\Bundle\OrganizationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use IMilyukov\Bundle\EavSchemaBundle\Entity\EavSubjectAttribute;

/**
 * Class DiscountAttribute
 * @package Bundle\DiscountBundle\Entity
 *
 * @ORM\Table(
 *      name="oro_business_unit_attribute",
 *      options={
 *          "collate"="utf8_general_ci",
 *          "charset"="utf8",
 *          "engine"="InnoDB"
 *      }
 * )
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class BusinessUnitAttribute extends EavSubjectAttribute implements BusinessUnitAttributeInterface
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var BusinessUnit
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\OrganizationBundle\Entity\BusinessUnitInterface", inversedBy="attributes")
     * @ORM\JoinColumn(name="business_unit_id", referencedColumnName="id")
     */
    protected $subject;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getAttribute()
            ? $this->getValue() . '(' . $this->getAttribute()->getName() . ')' : 'New Discount Attribute';
    }
}