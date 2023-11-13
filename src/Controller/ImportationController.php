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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

require '../zklibrary.php';

#[Route('/pointage')]
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
            $day = $params->all('columns')[0]['search']['value'];
        }
        $filtre .= " and date(ch.created) = '" . $day . "' ";

        if (!empty($params->all('columns')[1]['search']['value'])) {
            $hdebut = $day . " " . $params->all('columns')[1]['search']['value'] . ":00";
            $filtre .= " and ch.created >= '" . $hdebut . "' ";
        }
        if (!empty($params->all('columns')[2]['search']['value'])) {
            $hfin = $day . " " . $params->all('columns')[2]['search']['value'] . ":59";
            $filtre .= " and ch.created <= '" . $hfin . "' ";
        }


        $columns = array(
            array('db' => 'mach.id', 'dt' => 0),
            array('db' => 'mach.IP', 'dt' => 1),
            array('db' => 'UPPER(mach.sn)', 'dt' => 2),
            array('db' => 'ch.checktime', 'dt' => 3),
            array('db' => 'ch.userid', 'dt' => 4),
            array('db' => 'us.name', 'dt' => 5),
        );
        $sql = "SELECT " . implode(", ", DatatablesController::Pluck($columns, 'db')) . "
        FROM machines mach
        inner join checkinout ch on ch.machine_id = mach.id
        inner join userinfo us on us.badgenumber = ch.userid
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
                // if ($key == 4) {
                //     $nestedData[] = $value;
                //     $machine = $this->em->getRepository(Machines::class)->find($cd);
                //     $zk = new \ZKLibrary($machine->getIP(), 4370);
                //     $zk->connect();
                //     // dd($zk->connect());
                //     // dd($zk->getAttendance($dateSeance));
                //     try {
                //         $PointeuseUsers = $zk->getUser();
                //         foreach ($PointeuseUsers as $PointeuseUser) {
                //             if ($PointeuseUser[0] == $value) {
                //                 $value = $PointeuseUser[1];
                //                 continue;
                //             }
                //         }
                //         // dd($PointeuseUsers);

                //     } catch (\Throwable $th) {
                //         $value = '-';
                //         //dump($machine);
                //         // $EndWithError++;
                //         // continue;
                //     }
                //     $zk->disconnect();
                // }
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

    // #[Route('/importation', name: 'importation')]
    // public function importation(Request $request, AuthorizationCheckerInterface $authorizationChecker): Response
    // {
    //     if (!$authorizationChecker->isGranted('ROLE_ADMIN')) {
    //         return new Response('', 500);
    //     }
    //     $this->em->getRepository(SituationSync::class)->find(1)->setSync(1);
    //     $this->em->flush();
    //     $dateSeance = $request->get('date') != "" ? $request->get('date') : date('Y-m-d');
    //     // dd($dateSeance);
    //     $machines = $this->em->getRepository(Machines::class)->findBy(['active' => 1]);
    //     // dd($machines);
    //     // $machines = $this->em->getRepository(Machines::class)->findBy(['id'=>[955,954]]);
    //     $EndWithSucces = 0;
    //     $EndWithError = 0;
    //     $countPointage = 0;
    //     foreach ($machines as $machine) {
    //         // if ($machine->getSn() != "AIOR200360236") {
    //         //     continue;
    //         // }
    //         $attendances = [];
    //         $zk = new \ZKLibrary($machine->getIP(), 4370);
    //         $zk->connect();
    //         // dd($zk->connect());
    //         // dd($zk->getAttendance($dateSeance));
    //         try {
    //             $attendances = $zk->getAttendance($dateSeance);
    //             $EndWithSucces++;
    //         } catch (\Throwable $th) {
    //             //dump($machine);
    //             $EndWithError++;
    //             continue;
    //         }
    //         $zk->disconnect();
    //         // dd($attendances);
    //         if ($attendances) {
    //             foreach ($attendances as $attendance) {
    //                 $checkIIN = $this->em->getRepository(Checkinout::class)->findOneBy([
    //                     'sn' => $machine->getSn(),
    //                     'USERID' => $attendance['id'],
    //                     'CHECKTIME' => new DateTime($attendance['timestamp']),
    //                 ]);
    //                 if (!$checkIIN) {
    //                     $checkin = new Checkinout();
    //                     $checkin->setUSERID($attendance['id']);
    //                     $checkin->setCHECKTIME(new DateTime($attendance['timestamp']));
    //                     $checkin->setMemoinfo('work');
    //                     $checkin->setSN($machine->getSn());
    //                     $checkin->setCreated(new DateTime('now'));
    //                     $checkin->setMachine($machine);
    //                     $this->em->persist($checkin);
    //                     $this->em->flush();
    //                     $countPointage++;
    //                 }
    //             }
    //         }
    //     }
    //     $this->em->getRepository(SituationSync::class)->find(1)->setSync(0);
    //     $this->em->flush();
    //     return new Response('Pointage Importer: ' . $countPointage . ', Success Pointeuse: ' . $EndWithSucces . ', Error Pointeuse: ' . $EndWithError, 200);
    //     // return new jsonResponse(['Pointage Importer: '.$countPointage.', Success Pointeuse: '.$EndWithSucces.', Error Pointeuse: '.$EndWithError,200]);
    //     dd('done');
    // }

    static function ping($ip)
    {
        $deviceIP = $ip;

        $devicePort = 4370; // Replace with the appropriate port number
        $timeout = 1; // Connection timeout in seconds

        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => $timeout, 'usec' => 0]);
        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, ['sec' => $timeout, 'usec' => 0]);

        // Try to connect to the device
        if (@socket_connect($socket, $deviceIP, $devicePort)) {
            $status = 'yes';
        } else {
            $status = 'no';
        }

        socket_close($socket);
        //  dd($status);
        return $status;
    }

    #[Route('/importationTemp', name: 'importationTemp')]
    public function importationTemp(Request $request): Response
    {
        // $dateSeance = $request->get('date') != "" ? $request->get('date') : date('Y-m-d');
        $dateSeance = '2023-11-09';
        $datedebut = "2023-11-09";
        $datefin = "2023-11-09";
        // dd($dateSeance);
        // $machines = $this->em->getRepository(Machines::class)->findall();
        // dd($machines);
        $ids = [1001,
        1002,
        1003,
        1004,
        1006,
        1007,
        1008,
        1009,
        1010,
        1011,
        1013,
        1014,
        1015,
        1016,
        1084,
        1093,
        1094];
        $machines = $this->em->getRepository(Machines::class)->findBy(['id' => $ids]);
        // dd($machines);
        // $machines = $this->em->getRepository(Machines::class)->findBy(['active'=>0]);

        // $ping =  self::ping($machines[0]->getIP());
        // dd($ping);
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
        $machineError = [];
        foreach ($machines as $machine) {
            // if ($machine->getSn() != "AIOR200360236") {
            //     continue;
            // }
            $attendances = [];
            $zk = new \ZKLibrary($machine->getIP(), 4370);
            $zk->connect();
            // dd($zk->connect());
            // dd($zk->getAttendance($dateSeance));
            try {
                // dd("hi");
                $attendances = $zk->getAttendance($dateSeance);
                // $attendances = $zk->getAttendanceByDate($datedebut, $datefin);
                $EndWithSucces++;
            } catch (\Throwable $th) {
                // dump($th);
                array_push($machineError, $machine);
                $EndWithError++;
                continue;
            }
            $zk->disconnect();
            dd($attendances);
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
        // die();
        // $this->em->getRepository(SituationSync::class)->find(1)->setSync(0);
        // $this->em->flush();
        // dd($machineError);
        return new Response('Pointage Importer: ' . $countPointage . ', Success Pointeuse: ' . $EndWithSucces . ', Error Pointeuse: ' . $EndWithError, 200);
        // return new jsonResponse(['Pointage Importer: '.$countPointage.', Success Pointeuse: '.$EndWithSucces.', Error Pointeuse: '.$EndWithError,200]);
        dd('done');
    }

    #[Route('/extractionResidanat/{db}/{fin}', name: 'app_residanat_extraction')]
    public function extractionResidanat($db, $fin): Response
    {
        // dd($db,$fin);
        $hour = date(' H:i:s');
        $date = date('Y-m-d');
        $dates = date('Y-m-d', strtotime('-1 day', strtotime($date)));

        $admission = "'ADM-FMA_ONC00008470',
        'ADM-FMA_RTH00008472',
        'ADM-FMA_RTH00008493',
        'ADM-FMA_PD00008398',
        'ADM-FMA_PD00008462',
        'ADM-FMA_HEC00008461',
        'ADM-FMA_HEC00008464',
        'ADM-FMA_HEC00008474',
        'ADM-FMA_NRO00008467',
        'ADM-FMA_CV00008473',
        'ADM-FMA_CAR00008488',
        'ADM-FMA_CAR00008492',
        'ADM-FMA_BM00008401',
        'ADM-FMA_BM00008458',
        'ADM-FMA_BM00008479',
        'ADM-FPA_BC00008403',
        'ADM-FPA_BC00008451',
        'ADM-FPA_BC00008452',
        'ADM-FPA_BC00008486',
        'ADM-FPA_BC00008489',
        'ADM-FMA_NP00008468',
        'ADM-FMA_NP00008481',
        'ADM-FMA_NP00008483',
        'ADM-FMA_REA00008456',
        'ADM-FMA_REA00008465',
        'ADM-FMA_REA00008466',
        'ADM-FMA_REA00008471',
        'ADM-FMA_PN00008120',
        'ADM-FMA_NC00008460',
        'ADM-FMA_DRM00008490',
        'ADM-FMA_CCV00008457',
        'ADM-FMA_ORL00008406',
        'ADM-FMA_ORL00008445',
        'ADM-FMA_OPH00008402',
        'ADM-FMA_OPH00008469',
        'ADM-FMA_OPH00008487',
        'ADM-FMA_RAD00008429',
        'ADM-FMA_RAD00008459',
        'ADM-FMA_RAD00008476',
        'ADM-FMA_GS00008393',
        'ADM-FMA_GS00008447',
        'ADM-FMA_ANP00008409',
        'ADM-FMA_ANP00008463',
        'ADM-FPA_PHI00008412',
        'ADM-FPA_PHH00008395',
        'ADM-FMDA_ODF00008400',
        'ADM-FMDA_PRT00008399',
        'ADM-FMA_DRM00008494',
        'ADM-FMA_ORL00003355',
        'ADM-FMA_OPH00003354',
        'ADM-FMA_CAR00004432',
        'ADM-FMA_DRM00005039',
        'ADM-FMA_CAR00004431',
        'ADM-FMA_MEI00005042',
        'ADM-FMA_DRM00004436',
        'ADM-FMA_ONC00003477',
        'ADM-FMA_RUM00004437',
        'ADM-FMA_TOR00004434',
        'ADM-FMA_NC00004442',
        'ADM-FMA_URL00004435',
        'ADM-FMA_CAR00006114',
        'ADM-FMA_PD00006115',
        'ADM-FMA_NP00006116',
        'ADM-FMA_ONC00006117',
        'ADM-FMA_URL00006119',
        'ADM-FMA_OPH00006120',
        'ADM-FMA_RAD00006122',
        'ADM-FMA_CAR00006124',
        'ADM-FMA_GS00006125',
        'ADM-FMA_BM00006126',
        'ADM-FPA_BC00006128',
        'ADM-FMA_NRO00006129',
        'ADM-FMA_RTH00006130',
        'ADM-FMA_RTH00006134',
        'ADM-FMA_RAD00006131',
        'ADM-FMA_NP00006142',
        'ADM-FMA_RAD00006135',
        'ADM-FMA_ORL00006133',
        'ADM-FMA_PD00006137',
        'ADM-FMA_BM00006139',
        'ADM-FMA_RAD00006138',
        'ADM-FMA_RTH00007269',
        'ADM-FMA_RAD00007193',
        'ADM-FMA_HEC00007263',
        'ADM-FMA_RAD00007220',
        'ADM-FMA_RTH00007270',
        'ADM-FMA_CAR00007214',
        'ADM-FMA_OPH00007189',
        'ADM-FMA_NP00007221',
        'ADM-FMA_DRM00007200',
        'ADM-FMA_BM00007222',
        'ADM-FMA_ONC00007196',
        'ADM-FPA_BC00007210',
        'ADM-FMA_OPH00007272',
        'ADM-FPA_BC00007211',
        'ADM-FMA_REA00007225',
        'ADM-FMA_OPH00007194',
        'ADM-FMA_CCV00007207',
        'ADM-FMA_CAR00007190',
        'ADM-FMA_GS00007226',
        'ADM-FMA_NP00007275',
        'ADM-FMA_RAD00007273',
        'ADM-FMA_ORL00007274',
        'ADM-FMA_REA00007051',
        'ADM-FMA_CAR00007195',
        'ADM-FMA_NRO00007218',
        'ADM-FMA_TOR00007045',
        'ADM-FMA_BM00007233',
        'ADM-FMA_PN00007228',
        'ADM-FMA_RTH00007206',
        'ADM-FMA_BM00007234',
        'ADM-FMA_DRM00007050',
        'ADM-FMA_NC00007105',
        'ADM-FMA_ONC00007264',
        'ADM-FMA_HEC00007049',
        'ADM-FMA_CAR00007202',
        'ADM-FMA_CV00007046',
        'ADM-FMA_ORL00007192',
        'ADM-FMA_PN00007048',
        'ADM-FMA_GO00007208',
        'ADM-FMA_NP00007230',
        'ADM-FMA_BM00007232',
        'ADM-FMA_CCV00007047',
        'ADM-FMA_FMA00002115',
        'ADM-FMA_FMA00001584',
        'ADM-FMA_FMA00001743',
        'ADM-FMA_FMA00001781',
        'ADM-FMA_FMA00001973',
        'ADM-FMA_FMA00001615',
        'ADM-FMA_FMA00001651',
        'ADM-FMA_FMA00002101',
        'ADM-FMA_FMA00001582',
        'ADM-FMA_FMA00001597',
        'ADM-FMA_FMA00001655',
        'ADM-FMA_FMA00001640',
        'ADM-FMA_FMA00002059',
        'ADM-FMA_FMA00001702',
        'ADM-FMA_FMA00001583',
        'ADM-FMA_FMA00001641',
        'ADM-FMA_FMA00001628',
        'ADM-FMA_MG00000096',
        'ADM-FMA_FMA00001670',
        'ADM-FMA_FMA00001998',
        'ADM-FMA_FMA00001818',
        'ADM-FMA_FMA00001842',
        'ADM-FMA_FMA00001999',
        'ADM-FMA_FMA00002131',
        'ADM-FMA_MG00000620',
        'ADM-FMA_FMA00001677',
        'ADM-FMA_FMA00001671',
        'ADM-FMA_FMA00001620',
        'ADM-FMA_MG00002804',
        'ADM-FMA_MG00002829',
        'ADM-FMA_MG00002843',
        'ADM-FMA_MG00002937',
        'ADM-FMA_FMA00001881',
        'ADM-FMA_MG00002827',
        'ADM-FMA_MG00002862',
        'ADM-FMA_MG00002910',
        'ADM-FMA_MG00002939',
        'ADM-FMA_MG00002850',
        'ADM-FMA_MG00002895',
        'ADM-FMA_MG00002918',
        'ADM-FMA_MG00002857',
        'ADM-FMA_MG00002888',
        'ADM-FMA_MG00002858',
        'ADM-FMA_MG00002931',
        'ADM-FMA_MG00002878',
        'ADM-FMA_FMA00001685',
        'ADM-FMA_MG00002841',
        'ADM-FMA_MG00002891',
        'ADM-FMA_FMA00001690',
        'ADM-FMA_FMA00001629',
        'ADM-FMA_MG00002825',
        'ADM-FMA_MG00002807',
        'ADM-FMA_MG00000203',
        'ADM-FMA_MG00003055',
        'ADM-FMA_MG00002926',
        'ADM-FMA_MG00002922',
        'ADM-FMA_MG00002881',
        'ADM-FMA_MG00002853',
        'ADM-FPA_FPA00001614',
        'ADM-FPA_FPA00001684',
        'ADM-FPA_FPA00001776',
        'ADM-FPA_FPA00001770',
        'ADM-FPA_FPA00001697',
        'ADM-FPA_FPA00001800',
        'ADM-FPA_PH00002685',
        'ADM-FPA_PH00002683',
        'ADM-FPA_PH00002689',
        'ADM-FPA_PH00002733',
        'ADM-FPA_PH00002692',
        'ADM-FPA_PH00002716',
        'ADM-FMDA_MD00003148',
        'ADM-FDA_FDA00002795',
        'ADM-FDA_FDA00002771',
        'ADM-FDA_FDA00002781',
        'ADM-FDA_FDA00002777'";
        $myArray = "'x',";

        $requete = "SELECT DISTINCT userinfo.name,userinfo.street,userinfo.badgenumber
        from userinfo
        WHERE userinfo.street in ($admission) order by name";
        $stmt = $this->em->getConnection()->prepare($requete);
        $newstmt = $stmt->executeQuery();
        $userinfo = $newstmt->fetchAll();
        // dd($requete);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'ADMISSION');
        $sheet->setCellValue('B1', 'NOM');
        $sheet->setCellValue('C1', 'date');
        $sheet->setCellValue('D1', 'HEUREDEPOINTAGEMINIMAL');
        $sheet->setCellValue('E1', 'HEUREDEPOINTAGEMaximal');

        $i = 2;
        $count = 1;
        foreach ($userinfo as $sn) {

            $requete = "SELECT
                c1.Dat,
                c1.checktime AS min_pointage,
                c2.checktime AS max_pointage
            FROM
                (
                    SELECT DATE_FORMAT(checktime, '%Y-%m-%d') AS Dat, MIN(TIME_FORMAT(checktime, '%H:%i:%s')) AS checktime
                    FROM checkinout
                    WHERE userid = " . $sn["badgenumber"] . "
                    AND date(checktime) >= '$db' AND  date(checktime) <= '$fin'
                    GROUP BY Dat
                ) c1
            LEFT JOIN
                (
                    SELECT DATE_FORMAT(checktime, '%Y-%m-%d') AS Dat, MAX(TIME_FORMAT(checktime, '%H:%i:%s')) AS checktime
                    FROM checkinout
                    WHERE userid = " . $sn["badgenumber"] . "
                    AND date(checktime) >= '$db' AND  date(checktime) <= '$fin'
                    GROUP BY Dat
                ) c2
            ON c1.Dat = c2.Dat;";
            // dd($requete);
            $stmt = $this->em->getConnection()->prepare($requete);
            $newstmt = $stmt->executeQuery();
            $pointage = $newstmt->fetchAll();

            foreach ($pointage as $p) {

                $sheet->setCellValue('A' . $i, $sn["street"]);
                $sheet->setCellValue('B' . $i, $sn["name"]);

                // dd($pointage);
                $sheet->setCellValue('C' . $i, ($p["Dat"]));
                $sheet->setCellValue('D' . $i, $p["min_pointage"]);
                $sheet->setCellValue('E' . $i, $p["max_pointage"]);

                $i++;
            }
        }
        $fileName = null;
        $writer = new Xlsx($spreadsheet);
        $fileName = 'extraction_residanat.xlsx';
        $temp_file = tempnam(sys_get_temp_dir(), $fileName);
        $writer->save($temp_file);
        return $this->file($temp_file, $fileName, ResponseHeaderBag::DISPOSITION_INLINE);

        // return $this->render('residanat/index.html.twig', [
        //     'etablissements' => $this->em->getRepository(AcEtablissement::class)->findBy(['active' => 1]),
        //     'controller_name' => 'ResidanatController',
        // ]);
    }
}
