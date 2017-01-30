<?php
namespace Report\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Application\Entity\User,    Application\Entity\Address,    Application\Entity\Projects;

use Zend\Mvc\MvcEvent;
use Zend\View\Model\JsonModel;

use Application\Controller\AuthController;

class ReportController extends AuthController
{
    
    public function indexAction()
    {
        $this->setCaption('System Reports');
        
        $em = $this->getEntityManager();
        
        $qb = $em->createQueryBuilder();
        $qb
            ->select('r')
            ->from('Report\Entity\Report', 'r')
            ->join('r.group', 'g')
            ->orderBy('g.groupId', 'ASC');
            
        
        $query  = $qb->getQuery();
        $reports = $query->getResult();
        $reportsFiltered = array();
        foreach ($reports as $report) {
            if ($report->getPermission()) {
                if (!$this->isGranted($report->getPermission())) {
                    continue;
                }
            }
            if (empty($reportsFiltered[$report->getGroup()->getName()])) {
                $reportsFiltered[$report->getGroup()->getName()] = array(
                    'icon'=>$report->getGroup()->getIcon(),
                    'colour'=>$report->getGroup()->getColour(),
                    'data'=>array()
                );
            }
            $reportsFiltered[$report->getGroup()->getName()]['data'][$report->getReportId()] = array(
                $report->getName(),
                $report->getDescription(),
            );
        }

        $this->getView()->setVariable('groups', $reportsFiltered);
        return $this->getView();
    }
    
    private function getReportData(\Report\Entity\Report $report, $options=array()) {
        $data = array();
        switch ($report->getReportId()) {
            case 16:
                $data = array(
                    'projects' => array(),
                    'count' => array(
                        'project' => 0,
                        'drawing' => 0,
                        'device' => 0,
                        'passed' => 0,
                        'error' => 0,
                        'warning' => 0
                    )
                );
                $config = $this->getServiceLocator()->get('Config');

                if (empty($config) || !is_array($config['liteip']) || empty($config['liteip']['client'])) {
                    return $data;
                }

                $clientGroup = $config['liteip']['client'];
                $qb2  = $this->getEntityManager()->createQueryBuilder();
                $qb2->select('lip.ProjectID')
                    ->from('Project\Entity\Project', 'p')
                    ->innerJoin('p.lipProject', 'lip')
                    ->where($qb2->expr()->in('p.client', $clientGroup))
                    ->andWhere('p.test != true')
                    ->andWhere('p.cancelled != true');


                $qb = $this->getEntityManager()->createQueryBuilder();
                $qb->select('d')
                    ->from('Application\Entity\LiteipDevice', 'd')
                    ->innerJoin('d.drawing', 'dr')
                    ->where('d.IsE3=true')
                    ->andWhere($qb->expr()->in('dr.project', $qb2->getDQL()))
                    ->addOrderBy('dr.project', 'ASC')
                    ->addOrderBy('dr.DrawingID', 'ASC');

                $devices = $qb->getQuery()->getResult();

                $now = new \DateTime('now');
                foreach ($devices as $device) {
                    $projectName = $device->getDrawing()->getProject()->getProjectDescription();
                    $drawingName = $device->getDrawing()->getDrawing(true);

                    if (empty($data['projects'][$projectName])) {
                        $data['projects'][$projectName] = array(
                            'drawings' => array(),
                            'count' => array (
                                'drawing' => 0,
                                'device' => 0,
                                'passed' => 0,
                                'error' => 0,
                                'warning' => 0
                            )
                        );

                        $data['count']['project']++;
                    }

                    if (empty($data['projects'][$projectName]['drawings'][$drawingName])) {
                        $data['projects'][$projectName]['drawings'][$drawingName] = array(
                            'errors' => array(),
                            'warnings' => array(),
                        );

                        $data['count']['drawing']++;
                        $data['projects'][$projectName]['count']['drawing']++;
                    }

                    $data['projects'][$projectName]['count']['device']++;
                    $data['count']['device']++;

                    $timestamp = empty($device->getLastE3StatusDate()) ? 0 : $device->getLastE3StatusDate()->getTimestamp();
                    $diff = $now->getTimestamp() - $timestamp;

                    if ($device->getStatus() && $device->getStatus()->isFault()) {
                        $data['count']['error']++;
                        $data['projects'][$projectName]['count']['error']++;

                        $data['projects'][$projectName]['drawings'][$drawingName]['errors'][] = array(
                            $device->getDeviceID(),
                            $device->getDeviceSN(),
                            $device->getStatus()->getDescription(),
                            empty($device->getLastE3StatusDate()) ? 'Unknown' : $device->getLastE3StatusDate()->format('d/m/Y H:i:s'),
                            floor($diff / (60 * 60 * 24))
                        );
                    } else {
                        $data['count']['passed']++;
                        $data['projects'][$projectName]['count']['passed']++;
                    }

                    if($device->isIsE3() && (floor($diff / (60 * 60 * 24)) > 0)) { // if not tested for 24 hours
                        $data['count']['warning']++;
                        $data['projects'][$projectName]['count']['warning']++;

                        $data['projects'][$projectName]['drawings'][$drawingName]['warnings'][] = array(
                            $device->getDeviceID(),
                            $device->getDeviceSN(),
                            floor($diff / (60 * 60 * 24)),
                            empty($device->getLastE3StatusDate()) ? '' : $device->getLastE3StatusDate()->format('d/m/Y H:i:s'),
                            floor($diff / (60 * 60 * 24))
                        );
                    }
                }

                if (!empty($options['headers'])) {
                    $csv = array();
                    $csv[] = array('"Projects Polled"', $data['count']['project']);
                    $csv[] = array('"Floors Polled"', $data['count']['drawing']);
                    $csv[] = array('"Devices Polled"', $data['count']['device']);
                    $csv[] = array('"Error Count"', $data['count']['error']);
                    $csv[] = array();

                    $csv[] = array('"Device Error Breakdown"');
                    $csv[] = array('"Project"', '"Floor"', '"Serial"', '"Status"', '"Last Tested"');

                    foreach ($data['projects'] as $projectName => $project) {
                        if (empty($project['drawings'])) {
                            continue;
                        }

                        foreach ($project['drawings'] as $drawingName => $drawing) {
                            if (empty($drawing['errors'])) {
                                continue;
                            }

                            foreach ($drawing['errors'] as $error) {
                                $csv[] = array('"' . $projectName . '"', '"' . $drawingName . '"', '"' . $error[1] . '"', '"' . $error[2] . '"', '"' . $error[3] . '"');
                            }
                        }
                    }

                    if ($this->isGranted('branch.warnings.read')) {
                        $csv[] = array();
                        $csv[] = array('"Device Warning Breakdown"');
                        $csv[] = array('"Project"', '"Floor"', '"Serial"', '"Status"', '"Last Tested"');
                        foreach ($data['projects'] as $projectName => $project) {
                            if (empty($project['drawings'])) {
                                continue;
                            }

                            foreach ($project['drawings'] as $drawingName => $drawing) {
                                if (empty($drawing['warnings'])) {
                                    continue;
                                }

                                foreach ($drawing['warnings'] as $warning) {
                                    $csv[] = array('"' . $projectName . '"', '"' . $drawingName . '"', '"' . $warning[1] . '"', '"' . $warning[4] . ' Days Untested"', '"' . $warning[3] . '"');
                                }
                            }
                        }
                    }

                    return $csv;
                }

                return $data;
            case 5:
                $sql = "SELECT 
                    c.`client_id`, p.`project_id`,
                    c.`name` as `cname`,
                    p.`name` as `pname`,
                    t1.`price`,
                    p.`propertyCount`,
                    ROUND(t1.`price`/p.`propertyCount`, 2) as `ppp`
                    FROM `Project` p 
                    INNER JOIN `Client` c ON c.`client_id` = p.`client_id`
                    INNER JOIN (
                        SELECT SUM(ROUND((sys.`quantity` * sys.`ppu`)*(1-(p.`mcd` * pr.`mcd`)), 2)) AS `price`, p.`project_id`
                        FROM `System` sys
                        INNER JOIN `Product` pr ON pr.`product_id` = sys.`product_id`
                        inner Join `Space` s on s.`space_id` = sys.`space_id`
                        INNER JOIN `Project` p ON p.`project_id` = s.`project_id`
                        WHERE s.`deleted`!=1 
                        GROUP BY s.`project_id`
                    ) t1 ON t1.`project_id` = p.`project_id` 
                    WHERE 
                        p.`propertyCount`>1 AND 
                        p.`propertyCount` IS NOT NULL AND
                        p.`project_status_id`=1 
                    ORDER BY c.`client_id`, p.`client_id` ASC
                    ";
                $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
                $stmt->execute();

                $data = $stmt->fetchAll();
                if (!empty($options['headers'])) {
                    $tmp = array();
                    $tmp[] = array('"Project Reference"', '"Client Name"', '"Project Name"', 'Value', '"Property Count"', '"Price Per Property"');
                    foreach ($data as $item) {
                        $tmp[] = array (
                            $item['client_id'].'-'.$item['project_id'],
                            '"'.$item['cname'].'"',
                            '"'.$item['pname'].'"',
                            $item['price'],
                            $item['propertyCount'],
                            $item['ppp'],
                        );
                    }

                    return $tmp;
                }
                return $data;
            case 15:
                $sql = "select s.`state_id`, s.`name`, p.`project_id`, p.`name` AS `projectName`
from `Project` p
inner join `Project_State` ps ON ps.`project_id` = p.`project_id`
inner join `State` s on s.`state_id` = ps.`state_id`
order by s.`state_id`
";
                $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
                $stmt->execute();
                
                $tmpData = $stmt->fetchAll();
                $stateData = array();
                $projectData = array();
                $totalProjects = 0;
                foreach ($tmpData as $dat) {
                    if (empty($stateData[$dat['state_id']])) {
                        $stateData[$dat['state_id']] = array (
                            'total' => 0,
                            'name' => $dat['name'],
                            'projects' => array(),
                        );
                    }
                    
                    if (empty($projectData[$dat['project_id']])) {
                        $projectData[$dat['project_id']] = array(
                            'name' => $dat['projectName'],
                            'states' => array()
                        );
                        $totalProjects++;
                    }
                    
                    $stateData[$dat['state_id']]['total']++;
                    $stateData[$dat['state_id']]['projects'][] = $dat['project_id'];
                    $projectData[$dat['project_id']]['states'][$dat['state_id']] = true;
                }
                
                if (!empty($options['headers'])) {
                    $tmp = array();
                    $tmp[] = array('"State"', '"Project Count"', '"Project Percentage"');
                    foreach ($stateData as $state) {
                        $tmp[] = array(
                            '"' . $state['name'] . '"',
                            $state['total'],
                            '"' . number_format(($state['total'] / $totalProjects) * 100, 2) . '%"',
                        );
                    }
                    
                    $tmp[] = array('""');
                    $tmp[] = array('"Project"', '"States"');
                    foreach ($projectData as $project) {
                        $states = array();
                        foreach ($project['states'] as $stateId => $state) {
                            $states[] = $stateData[$stateId]['name'];
                        }
                        
                        $tmp[] = array(
                            '"' . $project['name'] . '"',
                            implode(', ', $states),
                        );
                    }
                    
                    return $tmp;
                }
                
                $data['state'] = $stateData;
                $data['project'] = $projectData;
                $data['totals'] = array (
                    'projects' => $totalProjects
                );
                
//                echo '<pre>', print_r($data), '</pre>'; die();
                return $data;
        }
        
        throw new \Exception ('Unsupported report');
    }
    
    public function downloadAction() {
        $group = $this->params()->fromRoute('group', false);
        $rid = $this->params()->fromRoute('report', false);
        
        if (empty($group)) {
            throw new \Exception('illegal group route');
        }
        
        if (empty($rid)) {
            throw new \Exception('illegal report route');
        }
        
        $report = $this->getEntityManager()->find('Report\Entity\Report', $rid);
        
        $data = $this->getReportData($report, array('headers'=>true));
        
        $filename = strtolower($report->getName()).' report.csv';
        
        $response = $this->prepareCSVResponse($data, $filename);
        
        return $response;
    }
    
    public function viewAction() {
        $group = $this->params()->fromRoute('group', false);
        $rid = $this->params()->fromRoute('report', false);
        
        if (empty($group)) {
            throw new \Exception('illegal group route');
        }
        
        if (empty($rid)) {
            throw new \Exception('illegal report route');
        }

        $report = $this->getEntityManager()->find('Report\Entity\Report', $rid);

        $data = $this->getReportData($report);
        $this->setCaption($report->getName());
        $this->getView()
            ->setVariable('report', $report)
            ->setVariable('partialScript', strtolower(preg_replace('/[ .-]/i', '', $report->getName())));


        
        $this->getView()->setVariable('data', $data);
        return $this->getView();
    }
    
         
}
