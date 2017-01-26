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

class BranchController extends AuthController
{
    protected $clients;

    public function onDispatch(MvcEvent $e) {
        $config = $this->getServiceLocator()->get('Config');

        if (empty($config) || !is_array($config['liteip']) || empty($config['liteip']['client'])) {
            return $this->redirect()->toRoute('home');
        }

        $this->setClients($config['liteip']['client']);

        return parent::onDispatch($e);
    }

    /**
     * @return mixed
     */
    public function getClients()
    {
        return $this->clients;
    }

    /**
     * @param mixed $clients
     */
    public function setClients($clients)
    {
        $this->clients = $clients;
    }

    /**
     * list alerts
     * @return JsonModel
     * @throws \Exception
     */
    public function listAlertsAction() {
        try{
            $em = $this->getEntityManager();

            if (!$this->request->isXmlHttpRequest()) {
                throw new \Exception('illegal request type');
            }

            $qb = $em->createQueryBuilder();
            $qb
                ->select('lip.ProjectID, p.name, p.projectId')
                ->from('Project\Entity\Project', 'p')
                ->innerJoin('p.lipProject', 'lip')
                ->where($qb->expr()->in('p.client', ':cid'))
                ->andWhere('p.test != true')
                ->andWhere('p.cancelled != true')
                ->setParameter('cid', $this->getClients());
            $projects = array();

            foreach ($qb->getQuery()->getResult() as $result) {
                $projects[$result['ProjectID']] = array (
                    'name' =>$result['name'],
                    'projectId' =>$result['projectId'],
                );
            }

            $qb = $em->createQueryBuilder();
            $qb->select('p.ProjectID, p.ProjectDescription', 'COUNT(d) as alerts')
                ->from('Application\Entity\LiteipDevice', 'd')
                ->innerJoin('d.drawing', 'dr')
                ->innerJoin('dr.project', 'p')
                ->innerJoin('d.status', 's')
                ->groupBy('p.ProjectID')
                ->andWhere('s.fault = true');

            $branches = array();
            $totalAlerts = 0;
            foreach ($qb->getQuery()->getResult() as $result) {
                if (empty($projects[$result['ProjectID']])) {
                    continue;
                }

                $totalAlerts += $result['alerts'];
                $branches[] = $projects[$result['ProjectID']] + array('count' => $result['alerts']);
            }

            return new JsonModel(array ('error' => false, 'branches' => $branches, 'total' => $totalAlerts));

        }
        catch (\Exception $ex) {
            return new JsonModel(array('error' => true, 'info' => $ex->getMessage()));
        }
    }

    /**
     * list devices for liteip drawing
     * @return JsonModel
     * @throws \Exception
     */
    public function listAction() {
        $em = $this->getEntityManager();

        if (!$this->request->isXmlHttpRequest()) {
            throw new \Exception('illegal request type');
        }

        $queryBuilder = $em->createQueryBuilder();

        $queryBuilder
            ->select('p', 'MAX(s.weighting) as weighting')
            ->from('Project\Entity\Project', 'p')
            ->innerJoin('p.lipProject', 'lip')
            ->leftJoin('p.address', 'a')
            ->leftJoin('p.states', 's')
            ->groupBy('p.projectId')
            ->where($queryBuilder->expr()->in('p.client', ':cid'))
            ->andWhere('p.test != true')
            ->andWhere('p.cancelled != true')
            ->setParameter('cid', $this->getClients());

        $commissioned = $this->params()->fromQuery('commissioned',false);
        $pending = $this->params()->fromQuery('pending',false);
        if ($commissioned) {
            $queryBuilder->andWhere('s.weighting = 100');
        } elseif ($pending) {
            $qb2  = $em->createQueryBuilder();
            $qb2->select('prj.projectId')
                ->from('Project\Entity\Project', 'prj')
                ->innerJoin('prj.states', 'st')
                ->where('st.weighting >= 100');

            $queryBuilder->andWhere($queryBuilder->expr()->notIn('p.projectId', $qb2->getDQL()));
        }

        /*
        * Filtering
        * NOTE this does not match the built-in DataTables filtering which does it
        * word by word on any field. It's possible to do here, but concerned about efficiency
        * on very large tables, and MySQL's regex functionality is very limited
        */
        $projectName = trim($this->params()->fromQuery('sSearch',''));
        if (!empty($projectName)) {
            $queryBuilder->andWhere('p.name LIKE :name')
                ->setParameter('name', "%{$projectName}%");
        }


        /*
         * Ordering
         */
        $aColumns = array('p.name','a.line1','a.postcode', 'weighting');
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
            $orderBy[] = 'p.name ASC';
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


        foreach ($paginator as $pageGroup) {
            $page = $pageGroup[0];
            $weighting = empty($pageGroup['weighting']) ? 0 : $pageGroup['weighting'];

            if ($weighting < 20) {
                $statusCls = 'danger';
            } elseif ($weighting < 40) {
                $statusCls = 'danger';
            } elseif ($weighting < 60) {
                $statusCls = 'warning';
            } elseif ($weighting < 80) {
                $statusCls = 'info';
            } elseif ($weighting < 100) {
                $statusCls = 'striped';
            } else {
                $statusCls = 'success';
            }
            $statusHtml = '<span style="position: absolute; padding-top:12px">'. $weighting .'%</span><div class="progress progress-'.$statusCls.'"><div style="width: '. $weighting .'%;" class="bar"></div></div>';

            $data['aaData'][] = array (
                '<a href="/branch-' . $page->getProjectId() . '/">' . $page->getName() . '</a>',
                empty($page->getAddress()) ? '-' : $page->getAddress()->assemble(', ', array('postcode')),
                empty($page->getAddress()) ? '-' : $page->getAddress()->getPostcode(),
                $statusHtml
            );
        }

        return new JsonModel($data);/**/
    }

    /**
     * get LiteIp Service
     * @return \Application\Service\LiteIpService
     */
    public function getLiteIpService() {
        return $this->getServiceLocator()->get('LiteIpService');
    }


    public function mapAction() {
        $this->setCaption('Map');

        $em = $this->getEntityManager();
        $queryBuilder = $em->createQueryBuilder();

        $queryBuilder
            ->select('p')
            ->from('Project\Entity\Project', 'p')
            ->innerJoin('p.lipProject', 'lip')
            ->innerJoin('p.address', 'a')
            ->where($queryBuilder->expr()->in('p.client', ':cid'))
            ->andWhere('p.test != true')
            ->andWhere('p.cancelled != true')
            ->andWhere('a.lat IS NOT NULL')
            ->andWhere('a.lng IS NOT NULL')
            ->setParameter('cid', $this->getClients());

        $branches = $queryBuilder->getQuery()->getResult();

        $locations = array();
        foreach ($branches as $branch) {
            $locations[] = $branch->getAddress()->getLat() . ',' . $branch->getAddress()->getLng() . ', "' . $branch->getName() . '", "' . $branch->getAddress()->assemble() . '", ' . $branch->getProjectId();
        }

        return $this->getView()->setVariable('locations', $locations);
    }

    public function commissionedAction() {
        $this->setCaption('Commissioned Branches');
        return $this->getView();
    }

    public function pendingAction() {
        $this->setCaption('Pending Branches');
        return $this->getView();
    }

    /**
     * synchronize branches (liteip) action
     * @return JsonModel
     */
    public function synchronizeAction() {
        try {
            $projects = $this->params()->fromQuery('projects', false);
            $drawings = $this->params()->fromQuery('drawings', false);
            $devices = $this->params()->fromQuery('devices', false);

            $flash = $this->params()->fromQuery('flash', false);

            $liteIPService = $this->getLiteIpService();

            if ($projects !== false) {
                $liteIPService->synchronizeProjectsData();
            }

            if ($drawings !== false) {
                $liteIPService->synchronizeDrawingsData();
            }

            if ($devices !== false) {
                $liteIPService->synchronizeDevicesData();
            }

            if ($flash !== false) {
                $this->flashMessenger()->addMessage(array(
                    'The telemetry synchronization has completed successfully', 'Synchronization Completed'
                ));
            }

            return new JsonModel(array('error' => false));
        } catch (\Exception $ex) {
            return new JsonModel(array('error' => true, 'info' => $ex->getMessage()));
        }
    }

}
