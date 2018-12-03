<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    /**
     * @Route("/inscription")
     */
    public function register(
        Request $request,
        UserPasswordEncoderInterface $passwordEncoder
    ) {
        $em = $this->getDoctrine()->getManager();

        $user = new User();

        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        if($form->isSubmitted()){
            // encode le mot de passe, à partir de la config "encoders" de security.yaml
            if( $form->isValid() ){
                $password = $passwordEncoder->encodePassword(
                    $user,
                    $user->getPlainPassword()
                );
                // on récupère et on sette la valeur du mot de passe encryptée
                $user->setPassword($password);
                $em->persist($user);
                $em->flush();

                $this->addFlash('success', 'Votre compte est créé');

                return $this->redirectToRoute("app_index_index");

            } else {
                $this->addFlash('error', 'Le formulaire contient des erreurs');
            }
        }

        return $this->render('security/register.html.twig',
            [
                'form' => $form->createView()
            ]
        );
    }

    /**
     * @Route("/connexion")
     */
    public function login(AuthenticationUtils $authenticationUtils)
    {
        // traitement du formulaire par Security
        // recupere le post, synchro en base de données, le traitement du login et password, et validation
        // et si tout est ok = pas d'erreur
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        if (!empty($error)){
            $this->addFlash('error', 'Identifiants incorrect');
        }


        return $this->render('security/login.html.twig',
            [
                'last_username' => $lastUsername
            ]);
    }
}
