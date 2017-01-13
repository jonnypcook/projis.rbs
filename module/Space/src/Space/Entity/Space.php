<?php
namespace Space\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Zend\Form\Annotation; // !!!! Absolutely neccessary

use Zend\InputFilter\Factory as InputFactory;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterAwareInterface;
use Zend\InputFilter\InputFilterInterface; 

/** 
 * @ORM\Table(name="Space")
 * @ORM\Entity 
 * @ORM\Entity(repositoryClass="Space\Repository\Space")
 */
class Space implements InputFilterAwareInterface
{
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", nullable=false)
     */
    private $name;
    
    
    /**
     * @var string
     *
     * @ORM\Column(name="notes", type="text", nullable=true)
     */
    private $notes;
    

    /**
     * @var integer
     *
     * @ORM\Column(name="quantity", type="integer", nullable=false)
     */
    private $quantity;
    
    
    /**
     * @var integer
     *
     * @ORM\Column(name="floor", type="integer", nullable=true)
     */
    private $floor;    

    
    /**
     * @var integer
     *
     * @ORM\Column(name="dimx", type="integer", nullable=true)
     */
    private $dimx;

    
    /**
     * @var integer
     *
     * @ORM\Column(name="dimy", type="integer", nullable=true)
     */
    private $dimy;
    
    
    /**
     * @var integer
     *
     * @ORM\Column(name="dimh", type="integer", nullable=true)
     */
    private $dimh;
    
    
    
    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="Space\Entity\Ceiling")
     * @ORM\JoinColumn(name="space_ceiling_id", referencedColumnName="space_ceiling_id", nullable=true)
     */
    private $ceiling;
    
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="metric", type="boolean", nullable=true)
     */
    private $metric;
    
    
    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="Space\Entity\TileSize")
     * @ORM\JoinColumn(name="space_tile_size_id", referencedColumnName="space_tile_size_id", nullable=true)
     */
    private $tileSize;
    
    
    /**
     * @var string
     *
     * @ORM\Column(name="tile_type", type="string", length=100, nullable=true)
     */
    private $tileType;
    
    
    /**
     * @var float
     * 
     * @ORM\Column(name="void_dimension", type="decimal", scale=2, nullable=true)
     */
    private $voidDimension;
    
    
    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="Space\Entity\ElectricConnector")
     * @ORM\JoinColumn(name="space_electric_connector_id", referencedColumnName="space_electric_connector_id", nullable=true)
     */
    private $electricConnector;
    
    
    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="Space\Entity\Grid")
     * @ORM\JoinColumn(name="space_grid_id", referencedColumnName="space_grid_id", nullable=true)
     */
    private $grid;
    
    
    /**
     * @var float
     * 
     * @ORM\Column(name="lux_level", type="decimal", scale=2, nullable=true)
     */
    private $luxLevel;
    
    
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="root", type="boolean", nullable=false)
     */
    private $root;    
    

    /**
     * @var boolean
     *
     * @ORM\Column(name="deleted", type="boolean", nullable=false)
     */
    private $deleted;    
    

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime", nullable=false)
     */
    private $created; 
    
    
    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="Space\Entity\SpaceType")
     * @ORM\JoinColumn(name="space_type_id", referencedColumnName="space_type_id", nullable=false)
     */
    private $spaceType;     
    
    
    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="Project\Entity\Project")
     * @ORM\JoinColumn(name="project_id", referencedColumnName="project_id", nullable=false)
     */
    private $project; 
    

    /**
     * @var integer
     *
     * @ORM\ManyToOne(targetEntity="Client\Entity\Building")
     * @ORM\JoinColumn(name="building_id", referencedColumnName="building_id", nullable=true)
     */
    private $building; 
    

    /**
     * @var integer
     *
     * @ORM\Column(name="space_id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $spaceId;
    
    
    /** 
     * @ORM\OneToMany(targetEntity="Space\Entity\SpaceHazard", mappedBy="project") 
     */
    protected $hazards; 
    
    /** 
     * @ORM\OneToMany(targetEntity="Job\Entity\Serial", mappedBy="space") 
     */
    protected $serials; 
    
  
    public function __construct()
	{
        $this->setRoot(false);
        $this->setDeleted(false);
        $this->setMetric(true);
        
        //$this->spaceType = new ArrayCollection();
        $this->client = new ArrayCollection();
        $this->sector = new ArrayCollection();
        $this->status = new ArrayCollection();
        $this->type = new ArrayCollection();
        $this->financeYears = new ArrayCollection();
        $this->financeProvider = new ArrayCollection();
		$this->setCreated(new \DateTime());
        
        $this->collaborators = new ArrayCollection();
        $this->contacts = new ArrayCollection();
        
        $this->serials = new ArrayCollection();
        $this->hazards = new ArrayCollection();
        $this->setQuantity(1);
	}
    
    
    public function getQuantity() {
        return $this->quantity;
    }

    
    public function setQuantity($quantity) {
        $this->quantity = $quantity;
        return $this;
    }

        
    public function findHazard($hazardId, $first=false) {
        $return = array();
        foreach ($this->hazards as $hazard) {
            if ($hazardId==$hazard->getHazard()->getHazardId()) {
               $return[]=$hazard;
               if ($first) {
                   break;
               }
            }
        }
        
        return $return;
    }
    
    public function getHazards() {
        return $this->hazards;
    }

    public function setHazards($hazards) {
        $this->hazards->clear();
        foreach ($hazards as $hazard) {
            $this->hazards[] = $hazard;
        }
        
        return $this;
    }
    
    /**
     * Add one role to roles list
     * @param Collection $collaborators
     */
    public function addHazards(Collection $hazards)
    {
        foreach ($hazards as $hazard) {
            $this->hazards->add($hazard);
        }
    }
    
    /**
     * @param Collection $collaborators
     */
    public function removeHazards(Collection $hazards)
    {
        foreach ($hazards as $hazard) {
            $this->hazards->removeElement($hazard);
        }
    }
    
    
    public function getCeiling() {
        return $this->ceiling;
    }

    public function getMetric() {
        return $this->metric;
    }

    public function getTileSize() {
        return $this->tileSize;
    }

    public function getTileType() {
        return $this->tileType;
    }

    public function getVoidDimension() {
        return $this->voidDimension;
    }

    public function getElectricConnector() {
        return $this->electricConnector;
    }

    public function getGrid() {
        return $this->grid;
    }

    public function getLuxLevel() {
        return $this->luxLevel;
    }

    public function setCeiling($ceiling) {
        $this->ceiling = $ceiling;
        return $this;
    }

    public function setMetric($metric) {
        $this->metric = $metric;
        return $this;
    }

    public function setTileSize($tileSize) {
        $this->tileSize = $tileSize;
        return $this;
    }

    public function setTileType($tileType) {
        $this->tileType = $tileType;
        return $this;
    }

    public function setVoidDimension($voidDimension) {
        $this->voidDimension = $voidDimension;
        return $this;
    }

    public function setElectricConnector($electricConnector) {
        $this->electricConnector = $electricConnector;
        return $this;
    }

    public function setGrid($grid) {
        $this->grid = $grid;
        return $this;
    }

    public function setLuxLevel($luxLevel) {
        $this->luxLevel = $luxLevel;
        return $this;
    }

    
    public function getSpaceType() {
        return $this->spaceType;
    }

    public function setSpaceType($type) {
        $this->spaceType = $type;
        return $this;
    }

        
    public function getSerials() {
        return $this->serials;
    }

    public function setSerials($serials) {
        $this->serials = $serials;
        return $this;
    }
    
    public function getFloor() {
        return $this->floor;
    }

    public function getDimx() {
        return $this->dimx;
    }

    public function getDimy() {
        return $this->dimy;
    }

    public function getDimh() {
        return $this->dimh;
    }

    public function setFloor($floor) {
        $this->floor = $floor;
        return $this;
    }

    public function setDimx($dimx) {
        $this->dimx = $dimx;
        return $this;
    }

    public function setDimy($dimy) {
        $this->dimy = $dimy;
        return $this;
    }

    public function setDimh($dimh) {
        $this->dimh = $dimh;
        return $this;
    }

        
    public function getName() {
        return $this->name;
    }

    public function getNotes() {
        return $this->notes;
    }

    public function getRoot() {
        return $this->root;
    }

    public function getCreated() {
        return $this->created;
    }

    public function getProject() {
        return $this->project;
    }

    public function getBuilding() {
        return $this->building;
    }

    public function getSpaceId() {
        return $this->spaceId;
    }

        
    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    public function setNotes($notes) {
        $this->notes = $notes;
        return $this;
    }

    public function setRoot($root) {
        $this->root = $root;
        return $this;
    }
    
    public function setCreated(\DateTime $created) {
        $this->created = $created;
        return $this;
    }

    public function setProject($project) {
        $this->project = $project;
        return $this;
    }

    public function setBuilding($building) {
        $this->building = $building;
        return $this;
    }

    public function setSpaceId($spaceId) {
        $this->spaceId = $spaceId;
        return $this;
    }

    public function getDeleted() {
        return $this->deleted;
    }

    public function setDeleted($deleted) {
        $this->deleted = $deleted;
        return $this;
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
            
            //example in google under search: "zf2 doctrine Album example"
            $inputFilter->add($factory->createInput(array(
                'name'     => 'name', // 'usr_name'
                'required' => true,
                'filters'  => array(
                    array('name' => 'StripTags'),
                    array('name' => 'StringTrim'),
                ),
                'validators' => array(
                    array(
                        'name'    => 'StringLength',
                        'options' => array(
                            'encoding' => 'UTF-8',
                            'min'      => 1,
                            'max'      => 255,
                        ),
                    ),
                ), 
            )));
            
            $inputFilter->add($factory->createInput(array(
                'name'     => 'quantity', // 'usr_name'
                'required' => false,
                'filters'  => array(),
                'validators' => array(
                    array(
                        'name'    => 'Int',
                    ),
                    array(
                        'name'    => 'GreaterThan',
                        'options' => array(
                            'min'      => 0,
                            'inclusive' => false
                        ),
                    )
                ), 
            )));

            $inputFilter->add($factory->createInput(array(
                'name'     => 'building', // 'usr_name'
                'required' => true,
                'filters'  => array(),
                'validators' => array(), 
            )));
            
            $inputFilter->add($factory->createInput(array(
                'name'     => 'ceiling', // 'usr_name'
                'required' => false,
                'filters'  => array(),
                'validators' => array(), 
            )));
            
            $inputFilter->add($factory->createInput(array(
                'name'     => 'electricConnector', // 'usr_name'
                'required' => false,
                'filters'  => array(),
                'validators' => array(), 
            )));
            
            $inputFilter->add($factory->createInput(array(
                'name'     => 'grid', // 'usr_name'
                'required' => false,
                'filters'  => array(),
                'validators' => array(), 
            )));
            
            $inputFilter->add($factory->createInput(array(
                'name'     => 'tileSize', // 'usr_name'
                'required' => false,
                'filters'  => array(),
                'validators' => array(), 
            )));
            
            $inputFilter->add($factory->createInput(array(
                'name'     => 'metric', // 'usr_name'
                'required' => false,
                'filters'  => array(),
                'validators' => array(), 
            )));
            
            $this->inputFilter = $inputFilter;        
        }
 
        return $this->inputFilter;
    } 

}


