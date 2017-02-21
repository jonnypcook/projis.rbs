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
 * @ORM\Table(name="LiteIP_Device_History")
 */
class LiteipDeviceHistory implements InputFilterAwareInterface
{
    /**
     * @var \DateTime
     *
     * @ORM\Column(name="TestedDate", type="datetime", nullable=true)
     */
    private $TestedDate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="LastE3StatusDate", type="datetime", nullable=true)
     */
    private $LastE3StatusDate;


    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="Application\Entity\LiteipDeviceStatus")
     * @ORM\JoinColumn(name="DeviceStatusID", referencedColumnName="DeviceStatusID", nullable=true)
     */
    private $status;

    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="Application\Entity\LiteipDevice")
     * @ORM\JoinColumn(name="DeviceID", referencedColumnName="DeviceID", nullable=false)
     */
    private $device;


    /**
     * @var integer
     *
     * @ORM\Column(name="DeviceHistoryID", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $DeviceHistoryID;


    public function __construct()
    {
        $this->device = new ArrayCollection();
        $this->setTestedDate(new \DateTime());
    }

    /**
     * @return \DateTime
     */
    public function getTestedDate()
    {
        return $this->TestedDate;
    }

    /**
     * @param \DateTime $TestedDate
     */
    public function setTestedDate($TestedDate)
    {
        $this->TestedDate = $TestedDate;
    }

    /**
     * @return \DateTime
     */
    public function getLastE3StatusDate()
    {
        return $this->LastE3StatusDate;
    }

    /**
     * @param \DateTime $LastE3StatusDate
     */
    public function setLastE3StatusDate($LastE3StatusDate)
    {
        $this->LastE3StatusDate = $LastE3StatusDate;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param int $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return int
     */
    public function getDevice()
    {
        return $this->device;
    }

    /**
     * @param int $device
     */
    public function setDevice($device)
    {
        $this->device = $device;
    }

    /**
     * @return int
     */
    public function getDeviceHistoryID()
    {
        return $this->DeviceHistoryID;
    }

    /**
     * @param int $DeviceHistoryID
     */
    public function setDeviceHistoryID($DeviceHistoryID)
    {
        $this->DeviceHistoryID = $DeviceHistoryID;
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
