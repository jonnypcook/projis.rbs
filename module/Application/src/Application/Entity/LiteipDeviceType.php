<?php
namespace Application\Entity;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface;

use Zend\Form\Annotation; // !!!! Absolutely neccessary

/**
 * @ORM\Entity
 * @ORM\Table(name="LiteIP_Device_Type")
 */
class LiteipDeviceType implements InputFilterAwareInterface
{
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100, nullable=false)
     */
    private $name;


    /**
     * @var boolean
     *
     * @ORM\Column(name="control", type="boolean", nullable=false)
     */
    private $control;

    /**
     * @var integer
     *
     * @ORM\Column(name="DeviceTypeID", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $deviceTypeId;


    public function __construct()
    {
        $this->setControl(false);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return boolean
     */
    public function isControl()
    {
        return $this->control;
    }

    /**
     * @param boolean $control
     */
    public function setControl($control)
    {
        $this->control = $control;
    }

    /**
     * @return int
     */
    public function getDeviceTypeId()
    {
        return $this->deviceTypeId;
    }

    /**
     * @param int $deviceTypeId
     */
    public function setDeviceTypeId($deviceTypeId)
    {
        $this->deviceTypeId = $deviceTypeId;
    }




    /**
     * Populate from an array.
     *
     * @param array $data
     */
    public function populate($data = array())
    {
        //print_r($data);die();
        foreach ($data as $name=>$value) {
            $fn = "set{$name}";
            try {
                $this->$fn($value);
            } catch (\Exception $ex) {

            }
        }
    }/**/

    /**
     * Convert the object to an array.
     *
     * @return array
     */
    public function getArrayCopy()
    {
        return get_object_vars($this);
    }


    protected $inputFilter;

    /**
     * set the input filter- only in forstructural and inheritance purposes
     * @param \Zend\InputFilter\InputFilterInterface $inputFilter
     * @throws \Exception
     */
    public function setInputFilter(InputFilterInterface $inputFilter)
    {
        throw new \Exception("Not used");
    }


    /**
     *
     * @return Zend\InputFilter\InputFilter
     */
    public function getInputFilter()
    {
        if (!$this->inputFilter) {
            $inputFilter = new InputFilter();

            $factory = new InputFactory();

            $this->inputFilter = $inputFilter;
        }

        return $this->inputFilter;
    }
}
