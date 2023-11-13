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
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

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

    #[Route('/auto/import/importation/{type}', name: 'importation')]
    public function importation(Request $request, $type, AuthorizationCheckerInterface $authorizationChecker): Response
    {
        // dd($type);
        // if (!$authorizationChecker->isGranted('ROLE_ADMIN')) {
        // return new Response('', 200);
        // }
        $this->em->getRepository(SituationSync::class)->find(1)->setSync(1);
        $this->em->flush();
        $dateSeance = $request->get('date') != "" ? $request->get('date') : date('Y-m-d');
        // dd($dateSeance);
        $machines = $this->em->getRepository(Machines::class)->findBy(['active' => 1, 'type' => $type]);
        dd($machines);
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
        return new Response('Pointage Importer: ' . $countPointage . ', Success Pointeuse: ' . $EndWithSucces . ', Error Pointeuse: ' . $EndWithError, 200);
        // return new jsonResponse(['Pointage Importer: '.$countPointage.', Success Pointeuse: '.$EndWithSucces.', Error Pointeuse: '.$EndWithError,200]);
        dd('done');
    }
}
