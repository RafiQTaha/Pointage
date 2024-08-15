<?php

namespace App\Controller;

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
use App\Entity\Userinfo;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use PhpOffice\PhpSpreadsheet\Reader\Ods as OdsReader;

require '../zklibrary.php';

class AutoImportController extends AbstractController
{
    private $em;
    private $emAssiduite;
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->em = $doctrine->getManager();
        // $this->emAssiduite = $doctrine->getManager('assiduite');
    }
    #[Route('/auto/import', name: 'app_auto_import')]
    public function index(): Response
    {
        return $this->render('auto_import/index.html.twig', [
            'controller_name' => 'AutoImportController',
        ]);
    }

    #[Route('/excel', name: 'app_auto_import_excel')]
    public function importExcel(): Response
    {
        // dd("hi");
        $odsReader = new OdsReader();
        
        $odsFilePath = 'C:\Users\DEV\Downloads\pointage111.ods';
        $spreadsheet = $odsReader->load($odsFilePath);

        $worksheet = $spreadsheet->getActiveSheet();
        $highestRow = $worksheet->getHighestRow();

        for ($row = 2; $row <= $highestRow; ++$row) {
            $rowData = $worksheet->rangeToArray('A' . $row . ':F' . $row, null, true, true, true);
            $machine = $this->em->getRepository(Machines::class)->findOneBy(["sn" => $rowData[$row]['D']]);
            // dd($machine);
            // Assuming your entity has setters for each column
            $checkinout = new Checkinout();
            $checkinout->setUSERID($rowData[$row]['A']);
            // $checkinout->setUSERID($rowData[$row]['A']);
            $checkinout->setCHECKTIME(new DateTime($rowData[$row]['B']));
            $checkinout->setMemoinfo($rowData[$row]['C']);
            $checkinout->setSn($rowData[$row]['D']);
            $checkinout->setCreated(new DateTime($rowData[$row]['E']));
            $checkinout->setMachine($machine);
            // Set other columns accordingly

            $this->em->persist($checkinout);
        }
        $this->em->flush();

        dd("done");
    }

    #[Route('/auto/import/importation/cr', name: 'importationCR')]
    public function importationCR(Request $request, AuthorizationCheckerInterface $authorizationChecker): Response
    {
        // dd($type);
        // if (!$authorizationChecker->isGranted('ROLE_ADMIN')) {
        // return new Response('', 200);
        // }
        $this->em->getRepository(SituationSync::class)->find(1)->setSync(1);
        $this->em->flush();
        $dateSeance = $request->get('date') != "" ? $request->get('date') : date('Y-m-d');
        // dd($dateSeance);
        $machines = $this->em->getRepository(Machines::class)->findBy(['active' => 1,'type' => 'cr']);
        // dd($machines);
        // $machines = $this->em->getRepository(Machines::class)->findBy(['id'=>[955,954]]);
        $EndWithSucces = 0;
        $EndWithError = 0;
        $countPointage = 0;
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
        return new Response('Pointage Importer CR: ' . $countPointage . ', Success Pointeuse: ' . $EndWithSucces . ', Error Pointeuse: ' . $EndWithError, 200);
        // return new jsonResponse(['Pointage Importer: '.$countPointage.', Success Pointeuse: '.$EndWithSucces.', Error Pointeuse: '.$EndWithError,200]);
        dd('done');
    }

    #[Route('/auto/import/importation/stg', name: 'importationSTG')]
    public function importationSTG(Request $request, AuthorizationCheckerInterface $authorizationChecker): Response
    {
        // dd($type);
        // if (!$authorizationChecker->isGranted('ROLE_ADMIN')) {
        // return new Response('', 200);
        // }
        $this->em->getRepository(SituationSync::class)->find(1)->setSync(1);
        $this->em->flush();
        $dateSeance = $request->get('date') != "" ? $request->get('date') : date('Y-m-d');

        // dd($dateSeance);
        $machines = $this->em->getRepository(Machines::class)->findBy(['active' => 1, 'type' => 'stg']);
        // dd($machines);
        // $machines = $this->em->getRepository(Machines::class)->findBy(['id'=>[955,954]]);
        $EndWithSucces = 0;
        $EndWithError = 0;
        $countPointage = 0;
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
        return new Response('Pointage Importer STG: ' . $countPointage . ', Success Pointeuse: ' . $EndWithSucces . ', Error Pointeuse: ' . $EndWithError, 200);
        // return new jsonResponse(['Pointage Importer: '.$countPointage.', Success Pointeuse: '.$EndWithSucces.', Error Pointeuse: '.$EndWithError,200]);
        dd('done');
    }

    #[Route('/importationTemp', name: 'importationTemp')]
    public function importationTemp(Request $request): Response
    {
        // $dateSeance = $request->get('date') != "" ? $request->get('date') : date('Y-m-d');
        $dateSeance = '2023-10-04';
        $datedebut = "2023-12-27";
        $datefin = "2023-12-27";
        // dd($dateSeance);
        // $machines = $this->em->getRepository(Machines::class)->findall();
        // dd($machines);
        // $dates = ['24-05-2024', '25-05-2024', '26-05-2024','27-05-2024','28-05-2024' ];
        // $dates = ['2024-04-15','2024-04-16','2024-04-17','2024-04-23'];
        $dates = ['2024-08-15'];
        $dateSeance = '2024-08-15';


        // residanat
        // $ids = [1017,
        // 1018,
        // 1019,
        // 1020,
        // 1021,
        // 1022,
        // 1023,
        // 1043,
        // 1047,
        // 1048,
        // 1065,
        // 1066,
        // 1067,
        // 1068,
        // 1069,
        // 1070,
        // 1071,
        // 1099,
        // 1080,1099,
        // 1080];

         

        $ids = [1104];
        // $ids = [1061,1080, 1081, 1083, 1090, 1097];
        // $ids = [993,1061,1080, 1081, 1082, 1083, 1090, 1097];
        // $ids = [986,
        // 987,
        // 988,
        // 989,
        // 990,
        // 992,
        // 994,
        // 995,
        // 997,
        // 998,
        // 999,
        // 1158,
        // 1159];
        $EndWithSucces = 0;
        $EndWithError = 0;
        $countPointage = 0;
        // $ids = [959];
        $machines = $this->em->getRepository(Machines::class)->findBy(['id' => $ids]);
        // dd($machines);
        // $machines = $this->em->getRepository(Machines::class)->findBy(['active' => 1]);
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
        $messages = [];
        // foreach ($dates as $date) {
        //     $EndWithSucces = 0;
        //     $EndWithError = 0;
        //     $countPointage = 0;
        //     $machineError = [];
        //     $message = "";
        //     $mach = "";
        //     foreach ($machines as $machine) {
        //         // dd($machine->getIP());
                

        //         // $ipAddress = '172.20.10.254';
        //         // $ipAddress = $machine->getIP();
        //         // $port = 4370; // Replace with the actual port

        //         // $connection = @stream_socket_client("udp://$machine->getIP():4370", $errno, $errstr, 5);
        //         // dd($connection);
        //         // if ($connection) {
        //         //     fclose($connection);
        //         //     dd("conn");
        //         // } else {
        //         //     echo 'Connection failed';
        //         //     dd("nope");
        //         // }
        //         // dd($machine->getIP());
        //         // if ($machine->getSn() != "AIOR200360236") {
        //         //     continue;
        //         // }
        //         $attendances = [];
        //         $zk = new \ZKLibrary($machine->getIP(), 4370);
        //         // $zk = new \ZKLibrary($ipAddress, 44371);
        //         // $zk->disconnect();
        //         // dd($zk->getAttendance($date));
        //         // $zk->connect();
        //         // dd($zk->connect());
        //         // $zk->enableDevice();
        //         // dd($zk->connect());
        //         // dd($zk->getUser());
        //         // $zk->enableDevice();
        //         // $zk->getUser();
        //         // dd($zk->getAttendance($date));
        //         // $attendances = $zk->getAttendance($dateSeance);
        //         // dd($attendances);
        //         try {
        //             $attendances = $zk->getAttendance($date);
        //             // dd($zk->connect());
        //             // dd($attendances);
        //             // $attendances = $zk->getAttendanceByDate($datedebut, $datefin);
        //             $EndWithSucces++;
        //         } catch (\Throwable $th) {
        //             // dd($th);
        //             array_push($machineError, $machine);
        //             $EndWithError++;
        //             $mach .= '-'. $machine->getId();
        //             // dd($machine);
        //             continue;
        //         }
        //         $zk->disconnect();
        //         // dd($attendances);
        //         if ($attendances) {
        //             // dd($attendances);
        //             foreach ($attendances as $attendance) {
        //                 // dd("hi");
        //                 $checkIIN = $this->em->getRepository(Checkinout::class)->findOneBy([
        //                     'sn' => $machine->getSn(),
        //                     'USERID' => $attendance['id'],
        //                     'CHECKTIME' => new DateTime($attendance['timestamp']),
        //                 ]);
        //                 // dd($checkIIN);
        //                 if (!$checkIIN) {
        //                     // dd('hi');
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
        //     $message = 'date:'. $date .' -> Pointage Importer: ' . $countPointage . ', Success Pointeuse: ' . $EndWithSucces . ', Error Pointeuse: ' . $EndWithError . ' ' . $mach;
        //     array_push($messages, $message);
        // }
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
                $attendances = $zk->getAttendance($dateSeance);
                dd($attendances);
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
        // die();
        // $this->em->getRepository(SituationSync::class)->find(1)->setSync(0);
        // $this->em->flush();
        // dd($machineError);
        // return new Response('Pointage Importer: ' . $countPointage . ', Success Pointeuse: ' . $EndWithSucces . ', Error Pointeuse: ' . $EndWithError, 200);
        // return new jsonResponse(['Pointage Importer: '.$countPointage.', Success Pointeuse: '.$EndWithSucces.', Error Pointeuse: '.$EndWithError,200]);
        
        // dd("done",$messages);
        dd("done",'Pointage Importer STG: ' . $countPointage . ', Success Pointeuse: ' . $EndWithSucces . ', Error Pointeuse: ' . $EndWithError);
    }

    #[Route('/fixuserinfo', name: 'fixUserInfo')]
    public function fixUserInfo(Request $request, AuthorizationCheckerInterface $authorizationChecker)
    {
        $admission = "'ADM-FMA_ONC00008470',
        'ADM-FMA_RTH00008472',
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
        'ADM-FDA_FDA00002777',
        'ADM-FMA_END00008650',
        'ADM-FMA_GS00008651',
        'ADM-FMA_RUM00008649'";
        // $userinfos = $this->em->getRepository(Userinfo::class)->findAll();
        $requete = "SELECT * from userinfo where street in ($admission)";
        // $requete = "SELECT * from userinfo where id = 14018";
        $stmt = $this->em->getConnection()->prepare($requete);
        $newstmt = $stmt->executeQuery();
        $userinfos = $newstmt->fetchAll();
        // dd(count($userinfos));
        foreach($userinfos as $user){
        $name = $user["name"];
        $requete = 'SELECT * from userinfo where name like "' .$name. '" and id != '. $user["id"]  .' ;';
        $stmt = $this->em->getConnection()->prepare($requete);
        $newstmt = $stmt->executeQuery();
        $duplicates = $newstmt->fetchAll();
            // $duplicates = $this->em->getRepository(Userinfo::class)->findBy(["name" => $user->getName()]);
            if(count($duplicates) >= 1){
                // dd($duplicates, $user);
                foreach($duplicates as $dup){
                    // $checkIIN = $this->em->getRepository(Checkinout::class)->findOneBy([
                    //     'USERID' => $dup['badgenumber']
                    // ]);
                    $requete = "SELECT * from checkinout where userid = ". $dup['badgenumber'] ." ;";
                    $stmt = $this->em->getConnection()->prepare($requete);
                    $newstmt = $stmt->executeQuery();
                    $checkIIN = $newstmt->fetchAll();
                    if($checkIIN){
                        // dd($checkIIN);
                        foreach($checkIIN as $ch){
                            
                            $requete = "SELECT * from checkinout where userid = ". $user['badgenumber'] ." and checktime = '".$ch['checktime']."' ;";
                            $stmt = $this->em->getConnection()->prepare($requete);
                            $newstmt = $stmt->executeQuery();
                            $exist = $newstmt->fetchAll();

                            if(!$exist){
                                // dd("hello");

                                
                                $machine_id = $ch['machine_id'] ? $ch['machine_id'] : null;
                                if($machine_id == null){
                                    $requete = "INSERT INTO `checkinout`(`userid`, `checktime`, `sn`, `created`) VALUES ('".$user['badgenumber']."','".$ch['checktime']."','".$ch['sn']."','2024-01-09 16:48:55')";
                                }else{
                                    $requete = "INSERT INTO `checkinout`(`userid`, `checktime`, `sn`, `created`, `machine_id`) VALUES ('".$user['badgenumber']."','".$ch['checktime']."','".$ch['sn']."','2024-01-09 16:48:55',$machine_id)";
                                }
                                // continue;
                                $stmt = $this->em->getConnection()->prepare($requete);
                                $newstmt = $stmt->executeQuery();
                            }

                        }
                    }
                }
            }
        }
        dd("done");
    }

    #[Route('/auto/import/importation/minuit', name: 'importationMinuit')]
    public function importationMinuit(Request $request, AuthorizationCheckerInterface $authorizationChecker): Response
    {
        // dd($type);
        // if (!$authorizationChecker->isGranted('ROLE_ADMIN')) {
        // return new Response('', 200);
        // }
        $this->em->getRepository(SituationSync::class)->find(1)->setSync(1);
        $this->em->flush();
        $dateSeance = $request->get('date') != "" ? $request->get('date') : date('Y-m-d');

        // dd($dateSeance);
        $machines = $this->em->getRepository(Machines::class)->findBy(['active' => 1]);
        // dd($machines);
        // $machines = $this->em->getRepository(Machines::class)->findBy(['id'=>[955,954]]);
        $EndWithSucces = 0;
        $EndWithError = 0;
        $countPointage = 0;
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
        return new Response('Pointage Importer STG: ' . $countPointage . ', Success Pointeuse: ' . $EndWithSucces . ', Error Pointeuse: ' . $EndWithError, 200);
        // return new jsonResponse(['Pointage Importer: '.$countPointage.', Success Pointeuse: '.$EndWithSucces.', Error Pointeuse: '.$EndWithError,200]);
        dd('done');
    }


    #[Route('/auto/import/importation/minuitResidanat', name: 'minuitResidanat')]
    public function minuitResidanat(Request $request): Response
    {
        // $hier = new DateTime();
        // $hier->modify("-1 days")->format('Y-m-d');
        $date = date('Y-m-d',strtotime("-1 days"));;
        // $dates = [$hier];
        // dd($date);

        // residanat
        $ids = [1017,
        1018,
        1019,
        1020,
        1021,
        1022,
        1023,
        1043,
        1047,
        1048,
        1065,
        1066,
        1067,
        1068,
        1069,
        1070,
        1071,
        1099,
        1080,1099,
        1080];
        $machines = $this->em->getRepository(Machines::class)->findBy(['id' => $ids]);
        $messages = [];
        // foreach ($dates as $date) {
            $EndWithSucces = 0;
            $EndWithError = 0;
            $countPointage = 0;
            $machineError = [];
            $message = "";
            $mach = "";
            foreach ($machines as $machine) {
                $attendances = [];
                $zk = new \ZKLibrary($machine->getIP(), 4370);
                $zk->connect();
                try {
                    $attendances = $zk->getAttendance($date);
                    $EndWithSucces++;
                } catch (\Throwable $th) {
                    array_push($machineError, $machine);
                    $EndWithError++;
                    $mach .= '-'. $machine->getId();
                    continue;
                }
                $zk->disconnect();
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
            $message = 'date:'. $date .' -> Pointage Importer: ' . $countPointage . ', Success Pointeuse: ' . $EndWithSucces . ', Error Pointeuse: ' . $EndWithError . ' ' . $mach;
            // array_push($messages, $message);
        // }
        return new Response($message, 200);
        dd("done",$messages);
    }
}
