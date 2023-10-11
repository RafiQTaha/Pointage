<?php

namespace App\Controller;


// use App\Entity\AcEtablissement;

use App\Entity\Checkinout;
use App\Entity\PSalles;
use App\Entity\Machines;
// use App\Entity\PlEmptime;
use DateTime;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Controller\DatatablesController;
use App\Entity\SituationSync;

require '../zklibrary.php';

// #[Route('/assiduite/traitement')]
class ImportationController extends AbstractController
{
    private $em;
    private $emAssiduite;
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
        // $this->emAssiduite = $doctrine->getManager('assiduite');
    }
    #[Route('/', name: 'app_importation')]
    public function index(): Response
    {
        return $this->render('importation/index.html.twig', [
            'etablissements' => [],
            'salles' => [],
        ]);
    }

    #[Route('/list', name: 'pointage_list')]
    public function list(Request $request)
    {
        $params = $request->query;
        // dd($params);
        $where = $totalRows = $sqlRequest = "";
        // $filtre = " where 1 = 1 and date(ch.checktime) = '2023-09-26' AND HOUR(ch.created) = 3 AND DATE_FORMAT(ch.created, '%p') = 'AM' ";   
        $filtre = " where 1 = 1  ";   
        $day = date('Y-m-d');
        if (!empty($params->all('columns')[0]['search']['value'])) {
            $day= $params->all('columns')[0]['search']['value'];
        }
        $filtre .= " and date(ch.created) = '" . $day . "' ";

        if (!empty($params->all('columns')[1]['search']['value'])) {
            $hdebut = $day." ".$params->all('columns')[1]['search']['value'].":00";
            $filtre .= " and ch.created >= '" . $hdebut . "' ";
        }
        if (!empty($params->all('columns')[2]['search']['value'])) {
            $hfin = $day." ".$params->all('columns')[2]['search']['value'].":59";
            $filtre .= " and ch.created <= '" . $hfin . "' ";
        }


        $columns = array(
            array( 'db' => 'mach.id','dt' => 0),
            array( 'db' => 'mach.IP','dt' => 1),
            array( 'db' => 'UPPER(mach.sn)','dt' => 2),
            array( 'db' => 'ch.checktime','dt' => 3),
            array( 'db' => 'ch.userid','dt' => 4),
            // array( 'db' => 'ch.checktime','dt' => 4),
        );
        $sql = "SELECT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
        FROM machines mach
        inner join checkinout ch on ch.machine_id = mach.id
        $filtre  ";
        // $groupBY = "GROUP BY mach.id, mach.IP, UPPER(mach.sn), DATE_FORMAT(ch.created, '%H:%i')";
        // $having = "HAVING COUNT(ch.checktime)";
        
        $sqlRequest .= $sql;
        // unset($columns[4]);
        // search 
        $where = DatatablesController::Search($request, $columns);
        if (isset($where) && $where != '') {
            $sqlRequest .= $where;
        }

        $stmt = $this->em->getConnection()->prepare($sqlRequest);
        $newstmt = $stmt->executeQuery();
        $totalRecords = count($newstmt->fetchAll());


        // dd($sqlRequest);
        

        $sqlRequest .= DatatablesController::Order($request, $columns);
        $stmt = $this->em->getConnection()->prepare($sqlRequest);
        $resultSet = $stmt->executeQuery();
        $result = $resultSet->fetchAll();
        

        $data = array();
        // dd($result);
        $i = 1;
        foreach ($result as $key => $row) {
            $nestedData = array();
            $cd = $row['id'];
            foreach (array_values($row) as $key => $value) {
                if ($key == 4) {
                    $nestedData[] = $value;
                    $machine = $this->em->getRepository(Machines::class)->find($cd);
                    $zk = new \ZKLibrary($machine->getIP(), 4370);
                    $zk->connect();
                    // dd($zk->connect());
                    // dd($zk->getAttendance($dateSeance));
                    try {
                        $PointeuseUsers = $zk->getUser();
                        foreach ($PointeuseUsers as $PointeuseUser) {
                            if ($PointeuseUser[0] == $value) {
                                $value = $PointeuseUser[1];
                                continue;
                            }
                        }
                        // dd($PointeuseUsers);
        
                    } catch (\Throwable $th) {
                        $value = '-';
                        //dump($machine);
                        // $EndWithError++;
                        // continue;
                    }
                    $zk->disconnect();
                }
                $nestedData[] = $value;
            }
            $nestedData["DT_RowId"] = $cd;
            // $nestedData["DT_RowClass"] = "green";

            $data[] = $nestedData;
            $i++;
        }
        // dd($data);
        $json_data = array(
            "draw" => intval($params->get('draw')),
            "recordsTotal" => intval($totalRecords),
            "recordsFiltered" => intval($totalRecords),
            "data" => $data   
        );
        // die;
        return new Response(json_encode($json_data));
    }
    
    // #[Route('/list', name: 'pointage_list')]
    // public function list(Request $request)
    // {
    //     $params = $request->query;
    //     // dd($params);
    //     $where = $totalRows = $sqlRequest = "";
    //     // $filtre = " where 1 = 1 and date(ch.checktime) = '2023-09-26' AND HOUR(ch.created) = 3 AND DATE_FORMAT(ch.created, '%p') = 'AM' ";   
    //     $filtre = " where 1 = 1  ";   
    //     $day = date('Y-m-d');
    //     if (!empty($params->all('columns')[0]['search']['value'])) {
    //         $day= $params->all('columns')[0]['search']['value'];
    //     }
    //     $filtre .= " and date(ch.created) = '" . $day . "' ";

    //     if (!empty($params->all('columns')[1]['search']['value'])) {
    //         $hdebut = $day." ".$params->all('columns')[1]['search']['value'].":00";
    //         $filtre .= " and ch.created >= '" . $hdebut . "' ";
    //     }
    //     if (!empty($params->all('columns')[2]['search']['value'])) {
    //         $hfin = $day." ".$params->all('columns')[2]['search']['value'].":59";
    //         $filtre .= " and ch.created <= '" . $hfin . "' ";
    //     }


    //     $columns = array(
    //         array( 'db' => 'mach.id','dt' => 0),
    //         array( 'db' => 'mach.IP','dt' => 1),
    //         array( 'db' => 'UPPER(mach.sn)','dt' => 2),
    //         array( 'db' => 'DATE_FORMAT(ch.created, "%H:%i")','dt' => 3),
    //         array( 'db' => 'COUNT(ch.checktime)','dt' => 4),
    //         // array( 'db' => 'ch.checktime','dt' => 4),
    //     );
    //     $sql = "SELECT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
    //     FROM machines mach
    //     inner join checkinout ch on ch.machine_id = mach.id
    //     $filtre  ";
    //     $groupBY = "GROUP BY mach.id, mach.IP, UPPER(mach.sn), DATE_FORMAT(ch.created, '%H:%i')";
    //     $having = "HAVING COUNT(ch.checktime)";
        
    //     $sqlRequest .= $sql;
    //     unset($columns[4]);
    //     // search 
    //     $where = DatatablesController::Search($request, $columns);
    //     if (isset($where) && $where != '') {
    //         $sqlRequest .= $where.' '.$groupBY.' '.$having;
    //     }else {
    //         $sqlRequest .= $groupBY;
    //     }

    //     $stmt = $this->em->getConnection()->prepare($sqlRequest);
    //     $newstmt = $stmt->executeQuery();
    //     $totalRecords = count($newstmt->fetchAll());


    //     // dd($sqlRequest);
        

    //     $sqlRequest .= DatatablesController::Order($request, $columns);
    //     $stmt = $this->em->getConnection()->prepare($sqlRequest);
    //     $resultSet = $stmt->executeQuery();
    //     $result = $resultSet->fetchAll();
        

    //     $data = array();
    //     // dd($result);
    //     $i = 1;
    //     foreach ($result as $key => $row) {
    //         $nestedData = array();
    //         $cd = $row['id'];
    //         foreach (array_values($row) as $key => $value) {
    //             $nestedData[] = $value;
    //         }
    //         $nestedData["DT_RowId"] = $cd;
    //         // $nestedData["DT_RowClass"] = "green";

    //         $data[] = $nestedData;
    //         $i++;
    //     }
    //     // dd($data);
    //     $json_data = array(
    //         "draw" => intval($params->get('draw')),
    //         "recordsTotal" => intval($totalRecords),
    //         "recordsFiltered" => intval($totalRecords),
    //         "data" => $data   
    //     );
    //     // die;
    //     return new Response(json_encode($json_data));
    // }

    #[Route('/importation', name: 'importation')]
    public function importation(Request $request): Response
    {
        $this->em->getRepository(SituationSync::class)->find(1)->setSync(1);
        $this->em->flush();
        $dateSeance = $request->get('date') != "" ? $request->get('date') : date('Y-m-d');
        // dd($dateSeance);
        $machines = $this->em->getRepository(Machines::class)->findBy(['active'=>1]);
        // dd($machines);
        // $machines = $this->em->getRepository(Machines::class)->findBy(['id'=>[955,954]]);
        $EndWithSucces = 0;
        $EndWithError = 0;
        $countPointage = 0;
        foreach ($machines as $machine) {
            // if ($machine->getSn() != "AIOR200360236") {
            //     continue;
            // }
            $attendances =[];
            $zk = new \ZKLibrary($machine->getIP(), 4370);
            $zk->connect();
            // dd($zk->connect());
            // dd($zk->getAttendance($dateSeance));
            try {
                $attendances = $zk->getAttendance($dateSeance);
                $EndWithSucces++;

            } catch (\Throwable $th) {
                //dump($machine);
                $EndWithError++;
                continue;
            }
            $zk->disconnect();
            // dd($attendances);
            if ($attendances) {
                foreach ($attendances as $attendance) {
                    $checkIIN = $this->em->getRepository(Checkinout::class)->findOneBy([
                        'sn' => $machine->getSn(),
                        'USERID' => $attendance['id'],
                        'CHECKTIME' => new DateTime($attendance['timestamp']),
                    ]);
                    if (!$checkIIN) {
                        $checkin = new Checkinout();
                        $checkin->setUSERID($attendance['id']);
                        $checkin->setCHECKTIME(new DateTime($attendance['timestamp']));
                        $checkin->setMemoinfo('work');
                        $checkin->setSN($machine->getSn());
                        $checkin->setCreated(new DateTime('now'));
                        $checkin->setMachine($machine);
                        $this->em->persist($checkin);
                        $this->em->flush();
                        $countPointage++;
                    }
                }
            }
        }
        $this->em->getRepository(SituationSync::class)->find(1)->setSync(0);
        $this->em->flush();
        return new Response('Pointage Importer: '.$countPointage.', Success Pointeuse: '.$EndWithSucces.', Error Pointeuse: '.$EndWithError,200);
        // return new jsonResponse(['Pointage Importer: '.$countPointage.', Success Pointeuse: '.$EndWithSucces.', Error Pointeuse: '.$EndWithError,200]);
        dd('done');
    }

    
    #[Route('/importationTemp', name: 'importationTemp')]
    public function importationTemp(Request $request): Response
    {
        $dateSeance = $request->get('date') != "" ? $request->get('date') : date('Y-m-d');
        // $dateSeance = '2023-10-02';
        // dd($dateSeance);
        // $machines = $this->em->getRepository(Machines::class)->findall();
        // dd($machines);
        $machines = $this->em->getRepository(Machines::class)->findBy(['id'=>[986]]);
        
        // $cc=0;
        // foreach ($machines as $machine) {
        //     // dd($machine);
        //     $checkinouts = $this->em->getRepository(Checkinout::class)->findBy(['ip'=>$machine->getSn()]);
        //     foreach ($checkinouts as  $checkinout) {
        //         $checkinout->setMachine($machine);
        //         $cc++;
        //     }
            
        // }
        // $this->em->flush();
        // dd($cc);
        $EndWithSucces = 0;
        $EndWithError = 0;
        $countPointage = 0;
        foreach ($machines as $machine) {
            $attendances =[];
            $zk = new \ZKLibrary($machine->getIP(), 4370);
            $zk->connect();
            // dd($zk->connect());
            // dd($zk->getAttendance($dateSeance));
            try {
                $attendances = $zk->getAttendance($dateSeance);
                $EndWithSucces++;

            } catch (\Throwable $th) {
                //dump($machine);
                $EndWithError++;
                continue;
            }
            // $zk->getUser();
            // dd($zk->getUser());
            $zk->disconnect();
            dd($attendances);
            if ($attendances) {
                foreach ($attendances as $attendance) {
                    $checkIIN = $this->em->getRepository(Checkinout::class)->findOneBy([
                        'sn' => $machine->getSn(),
                        'USERID' => $attendance['id'],
                        'CHECKTIME' => new DateTime($attendance['timestamp']),
                    ]);
                    if ($attendance['id'] == 4825) {
                        dd($attendance,$checkIIN);
                    }
                    // if (!$checkIIN) {
                    //     $checkin = new Checkinout();
                    //     $checkin->setUSERID($attendance['id']);
                    //     $checkin->setCHECKTIME(new DateTime($attendance['timestamp']));
                    //     $checkin->setMemoinfo('work');
                    //     $checkin->setSN($machine->getSn());
                    //     $checkin->setCreated(new DateTime('now'));
                    //     $checkin->setMachine($machine);
                    //     $this->em->persist($checkin);
                    //     $this->em->flush();
                    //     $countPointage++;
                    // }
                }
            }
        }
        // $this->em->getRepository(SituationSync::class)->find(1)->setSync(0);
        // $this->em->flush();
        return new Response('Pointage Importer: '.$countPointage.', Success Pointeuse: '.$EndWithSucces.', Error Pointeuse: '.$EndWithError,200);
        // return new jsonResponse(['Pointage Importer: '.$countPointage.', Success Pointeuse: '.$EndWithSucces.', Error Pointeuse: '.$EndWithError,200]);
        dd('done');
    }
}
