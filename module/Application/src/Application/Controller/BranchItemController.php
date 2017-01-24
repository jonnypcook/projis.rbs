<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2014 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Application\Entity\Address,    Application\Entity\Projects;

use Zend\Mvc\MvcEvent;
use Zend\View\Model\JsonModel;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator;
use Zend\Paginator\Paginator;

use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;

class BranchItemController extends AuthController
{
    /**
     * @var \Project\Entity\Project
     */
    protected $project;

    public function onDispatch(MvcEvent $e) {
        $pid = (int) $this->params()->fromRoute('id', 0);

        if (empty($pid)) {
            return $this->redirect()->toRoute('clients');
        }

        if (!($project=$this->getEntityManager()->find('Project\Entity\Project', $pid))) {
            $this->flashMessenger()->addMessage(array(
                'The specified branch could not be found', 'Important Message'
            ));
            return $this->redirect()->toRoute('home');
        }

        // should have liteip project assigned
        if (!$project->getLipProject()) {
            $this->flashMessenger()->addMessage(array(
                'The branch is not linked into commissioned project. Please contact customer support for assistance.', 'Important Message'
            ));
            return $this->redirect()->toRoute('home');
        }

        if ($project->getCancelled() === true) {
            $this->flashMessenger()->addMessage(array(
                'The branch has been cancelled and cannot be viewed at this time. Please contact customer support for assistance.', 'Important Message'
            ));
            return $this->redirect()->toRoute('home');
        }

        // check privileges
        if (!$this->isGranted('branch.read')) {
            $this->flashMessenger()->addMessage(array(
                'You do not have the necessary permissions to view this branch. Please contact customer support for assistance.', 'Important Message'
            ));
            return $this->redirect()->toRoute('home');
        }

        $this->setProject($project);
        $this->amendNavigation();

        return parent::onDispatch($e);
    }

    /**
     * @return \Project\Entity\Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @return \Application\Entity\LiteipProject
     */
    public function getLiteIpProject() {
        return $this->getProject()->getLipProject();
    }

    /**
     * @param \Project\Entity\Project $project
     */
    public function setProject($project)
    {
        $this->getView()->setVariable('project', $project);
        $this->getView()->setVariable('client', $project->getClient());
        $this->getView()->setVariable('liteIp', $project->getLipProject());
        $this->project = $project;
    }


    /**
     * get LiteIp Service
     * @return \Application\Service\LiteIpService
     */
    public function getLiteIpService() {
        return $this->getServiceLocator()->get('LiteIpService');
    }


    /**
     * Amend navigational elements
     */
    public function amendNavigation() {
        // check current location
        $action = $this->params('action');

        // get client
        $project = $this->getProject();
        $client = $project->getClient();

        // grab navigation object
        $navigation = $this->getServiceLocator()->get('navigation');

        $pages = array(
            array(
                'label' => 'Dashboard',
                'active'=> ($action == 'index'),
                'uri'=> '/branch-' . $project->getProjectId() . '/',
                'title' => ucwords($project->getName()).' Overview',
            ),

            array(
                'active'=> ($action == 'devicelayout'),
                'label' => 'Device Layout',
                'uri'=> '/branch-' . $project->getProjectId() . '/devicelayout',
                'title' => ucwords($project->getName()).' Device Layout',
            ),

            array(
                'active'=> ( $action == 'emergencyreport'),
                'label' => 'Emergency Report',
                'uri'=> '/branch-' . $project->getProjectId() . '/emergencyreport',
                'title' => ucwords($project->getName()).' Emergency Report',
            )
        );

        $navigation->addPage(array(
            'type' => 'uri',
            'active'=>true,
            'ico'=> 'icon-user',
            'order'=>1,
            'uri'=> '/branches/',
            'label' => 'Branches',
            'skip' => true,
            'pages' => array(
                array (
                    'type' => 'uri',
                    'active'=>true,
                    'ico'=> 'icon-tags',
                    'uri'=> '/branch-' . $project->getProjectId() . '/',
                    'label' => $project->getName(),
                    'mlabel' => 'Branch: ' . $project->getLipProject()->getPostCode(true),
                    'pages' => $pages
                )
            )
        ));
    }


    /**
     * list devices for liteip drawing
     * @return JsonModel
     * @throws \Exception
     */
    public function devicelistAction() {
        $em = $this->getEntityManager();

        if (!$this->request->isXmlHttpRequest()) {
//            throw new \Exception('illegal request type');
        }

        $drawingId = $this->params()->fromQuery('fDrawingId', false);
        $plotMode = $this->params()->fromQuery('plot', false);
        $queryBuilder = $em->createQueryBuilder();

        if ($plotMode) {
            $queryBuilder
                ->select('d')
                ->from('Application\Entity\LiteipDevice', 'd')
                ->where('d.drawing = :did')
                ->setParameter('did', $drawingId);

            $deviceData = array();
            $devices = $queryBuilder->getQuery()->getResult();

            foreach($devices as $device) {
                $deviceData[] = array(
                    $device->getDeviceID(),
                    $device->getDeviceSN(),
                    $device->IsIsE3(),
                    !empty($device->getStatus()) ? $device->getStatus()->isFault() : false,
                    !empty($device->getStatus()) ? $device->getStatus()->getName() : 'n\a',
                    !empty($device->getLastE3StatusDate()) ? $device->getLastE3StatusDate()->format('Y-m-d H:i:s') : '',
                    $device->getPosLeft(),
                    $device->getPosTop()
                );
            }

            return new JsonModel(array('error' => false, 'devices' => $deviceData));
        }

        $queryBuilder
            ->select('d')
            ->from('Application\Entity\LiteipDevice', 'd');

        if (empty($drawingId)) {
            // First get the EM handle
            // and call the query builder on it
            $queryBuilder2  = $this->getEntityManager()->createQueryBuilder();
            $queryBuilder2->select('dr.DrawingID')
                ->from('Application\Entity\LiteipDrawing', 'dr')
                ->where('dr.project = ' . $this->getProject()->getLipProject()->getProjectID());

            $queryBuilder->where($queryBuilder->expr()->in('d.drawing', $queryBuilder2->getDQL()));

        } else {
            $queryBuilder->where('d.drawing = :did')
                ->setParameter('did', $drawingId);
        }


        /*
        * Filtering
        * NOTE this does not match the built-in DataTables filtering which does it
        * word by word on any field. It's possible to do here, but concerned about efficiency
        * on very large tables, and MySQL's regex functionality is very limited
        */
        $devices = trim($this->params()->fromQuery('sSearch',''));
        if (!empty($devices)) {
            $devices = explode(',', preg_replace('/[,]+/', ',', str_replace(' ', ',',trim($devices))));
            $queryBuilder->andWhere($queryBuilder->expr()->in('d.DeviceID', '?1'))
                ->setParameter(1, $devices);
        }


        /*
         * Ordering
         */
        $aColumns = array('d.DeviceID','d.IsE3','d.status');
        $orderByP = $this->params()->fromQuery('iSortCol_0',false);
        $orderBy = array();
        if ($orderByP!==false)
        {
            for ( $i=0 ; $i<intval($this->params()->fromQuery('iSortingCols',0)) ; $i++ )
            {
                $j = $this->params()->fromQuery('iSortCol_'.$i);

                if ( $this->params()->fromQuery('bSortable_'.$j, false) == "true" )
                {
                    $dir = $this->params()->fromQuery('sSortDir_'.$i,'ASC');
                    if (is_array($aColumns[$j])) {
                        foreach ($aColumns[$j] as $ac) {
                            $orderBy[] = $ac." ".$dir;
                        }
                    } else {
                        $orderBy[] = $aColumns[$j]." ".($dir);
                    }
                }
            }

        }

        if (empty($orderBy)) {
            $orderBy[] = 'd.DeviceID ASC';
        }

        foreach ($orderBy as $ob) {
            $queryBuilder->add('orderBy', $ob);
        }

        /**/

        // Create the paginator itself
        $paginator = new Paginator(
            new DoctrinePaginator(new ORMPaginator($queryBuilder))
        );

        $length = $this->params()->fromQuery('iDisplayLength', 10);
        $start = $this->params()->fromQuery('iDisplayStart', 1);
        $start = (floor($start / $length)+1);


        $paginator
            ->setCurrentPageNumber($start)
            ->setItemCountPerPage($length);

        $data = array(
            "sEcho" => intval($this->params()->fromQuery('sEcho', false)),
            "iTotalDisplayRecords" => $paginator->getTotalItemCount(),
            "iTotalRecords" => $paginator->getcurrentItemCount(),
            "aaData" => array()
        );/**/


        foreach ($paginator as $page) {
            //$url = $this->url()->fromRoute('client',array('id'=>$page->getclientId()));
            $data['aaData'][] = array (
                '<a class="serial-trigger" data-device-serial="' . $page->getDeviceSN() . '">' . $page->getDeviceSN() . '</a>',
                $page->isIsE3() ? 'Yes' : 'No',
                empty($page->getStatus()) ? 'n\a' : $page->getStatus()->getName(),
                $page->getDrawing()->getDrawing(true),
                $page->getDrawing()->getProject()->getProjectDescription()
            );
        }

        return new JsonModel($data);/**/
    }


    /**
     * get liteip drawing image action
     * @return \Zend\Http\Response\Stream
     */
    public function drawingimageAction() {
        try
        {
            $drawingId = $this->params()->fromQuery( 'drawingId', false );

            $filename = $this->getLiteIpService()->findDrawingUrl($drawingId);

            if ($filename === false) {
                throw new \Exception('Could not find drawing');
            }

            if ( !file_exists( $filename ) )
            {
                throw new \Exception( 'file does not exist: ' . $filename);
            }

            $imageType = strtolower(preg_replace( '/^[\s\S]+[.]([^.]+)$/', '$1', $filename ));

            $response = new \Zend\Http\Response\Stream();
            $response->setStream( fopen( $filename, 'r' ) );
            $response->setStatusCode( 200 );

            $headers = new \Zend\Http\Headers();
            $headers->addHeaderLine( 'Content-Type', "image/{$imageType}" )
                ->addHeaderLine( 'Content-Length', filesize( $filename ) );

            $response->setHeaders( $headers );

            return $response;
        }
        catch ( \Exception $ex )
        {
            echo $ex->getMessage();
            exit; // just exit as file does not exist
        }
    }


    public function indexAction() {
        return $this->getView();
    }

    /**
     * device layout action
     * @return \Zend\View\Model\ViewModel
     */
    public function deviceLayoutAction() {
        $this->setCaption('Device Layout');

        $em = $this->getEntityManager();
        $qb = $em->createQueryBuilder();

        $qb->select('d')
            ->from('Application\Entity\LiteipDrawing', 'd')
            ->where('d.project=?1')
            ->andWhere('d.Activated=true')
            ->add('orderBy', 'd.Drawing ASC')
            ->setParameter(1, $this->getLiteIpProject()->getProjectID());

        $drawings = $qb->getQuery()->getResult();

        return $this->getView()->setVariable('drawings', $drawings);
    }

    /**
     * emergency report action
     * @return \Zend\View\Model\ViewModel
     */
    public function emergencyReportAction() {
        if (!$this->request->isXmlHttpRequest()) {
            $this->setCaption('Emergency Report');
            return $this->getView();
        }

        try {
            $em = $this->getEntityManager();
            $devicesPolled = 0;
            $errors = array();
            $warnings = array();
            $warningCount = 0;
            $errorCount = 0;
            $noFaultCount = 0;
            $synchronize = $this->params()->fromQuery('synchronize', false);



            if (!empty($synchronize)) {
                $this->getLiteIpService()->synchronizeDevicesData(false, $this->getProject()->getLipProject()->getProjectID());
            }

            $now = new \DateTime('now');
            $qb = $em->createQueryBuilder();
            $qb->select('d')
                ->from('Application\Entity\LiteipDevice', 'd')
                ->innerJoin('d.drawing', 'dr')
                ->where('dr.project=?1')
                ->andWhere('d.IsE3=true')
                ->setParameter(1, $this->getProject()->getLipProject()->getProjectID());

            $devices = $qb->getQuery()->getResult();

            foreach ($devices as $device) {

                $devicesPolled++;
                if ($device->getStatus() && $device->getStatus()->isFault()) {
                    $errorCount++;
                    $errors[$device->getDrawing()->getDrawing(true)][] =  array(
                        $device->getDeviceID(),
                        $device->getDeviceSN(),
                        $device->getStatus()->getDescription(),
                        empty($device->getLastE3StatusDate()) ? '' : $device->getLastE3StatusDate()->format('d/m/Y H:i:s')
                    );
                } else {
                    $noFaultCount ++;
                }

                $timestamp = empty($device->getLastE3StatusDate()) ? 0 : $device->getLastE3StatusDate()->getTimestamp();
                $diff = $now->getTimestamp() - $timestamp;
                if($device->isIsE3() && (floor($diff / (60 * 60 * 24)) > 0)) { // if not tested for 24 hours
                    $warningCount++;
                    $warnings[$device->getDrawing()->getDrawing(true)][] = array(
                        $device->getDeviceID(),
                        $device->getDeviceSN(),
                        floor($diff / (60 * 60 * 24)),
                        empty($device->getLastE3StatusDate()) ? '' : $device->getLastE3StatusDate()->format('d/m/Y H:i:s')
                    );
                }
            }

            return new JsonModel(array('error' => false, 'report' => array(
                'errors' => $errors,
                'warnings' => $warnings,
                'count' => array (
                    'errors' => $errorCount,
                    'nofault' => $noFaultCount,
                    'warnings' => $warningCount,
                    'devices' => $devicesPolled
                )
            )));
        } catch (\Exception $ex) {
            return new JsonModel(array('error' => true, 'info' => $ex->getMessage()));
        }


    }

}
