<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityController extends AbstractController
{
    private UserPasswordHasherInterface $passwordEncoder;

    public function __construct(UserPasswordHasherInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }
    /**
     * @Route("/", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_importation');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }
    /**
     * @Route("/register", name="app_register")
     */
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, ManagerRegistry $doctrine): Response
    {
        // dd($request);
        $userExist = $doctrine->getRepository(User::class)->findOneBy(['username' => $request->get('username')]);
        if ($userExist) {
            return new JsonResponse('Username déja exist !', 500);
        }
        $user = new User();
        $user->setNom($request->get('nom'));
        $user->setPrenom($request->get('prenom'));
        $user->setUsername($request->get('username'));
        $user->setEnable(0);
        $user->setPassword($passwordHasher->hashPassword(
            $user,
            $request->get('password')
        ));
        $user->setRoles(['ROLE_USER']);

        $doctrine->getManager()->persist($user);
        $doctrine->getManager()->flush();

        return new JsonResponse('good');
    }

    /**
     * @Route("/passwordchange", name="app_passwordchange")
     */
    public function passwordchange(Request $request, ManagerRegistry $doctrine)
    {
        $em = $doctrine->getManager();
        $user = $em->getRepository(User::class)->find($this->getUser()->getId());
        if (!$this->passwordEncoder->isPasswordValid($user, $request->get("an_password"))) {
            return new JsonResponse("Votre mot de passe est incorrect !", 500);
        }
        $user->setPassword($this->passwordEncoder->hashPassword(
            $user,
            $request->get('nv_password')
        ));

        $em->flush();
        return new JsonResponse("Bien Enregistre!", 200);
    }

    /**
     * @Route("/reinitialiser/{user}", name="app_reinitialiser")
     */
    public function reinitialiser(Request $request, User $user, ManagerRegistry $doctrine)
    {
        $em = $doctrine->getManager();
        $user->setPassword($this->passwordEncoder->hashPassword(
            $user,
            '0123456789'
        ));

        $em->flush();
        return new JsonResponse("Bien Réinitialiser!", 200);
    }
    /**
     * @Route("/logout", name="app_logout")
     */
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
