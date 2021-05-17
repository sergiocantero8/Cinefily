<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserRegistrationType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class UserController extends AbstractController
{

    # -------------------------------------------------- CONST ------------------------------------------------------- #

    public const ROUTE_USER_PROFILE = 'user_profile';
    # ----------------------------------------------- PROPERTIES ----------------------------------------------------- #

    # ------------------------------------------- GETTERS AND SETTERS ------------------------------------------------ #

    # ------------------------------------------------ CONSTRUCT ----------------------------------------------------- #

    # ------------------------------------------------- ROUTES ------------------------------------------------------- #


    /**
     * @Route("/user/registration", name="user_registration")
     */
    public function registerUser(Request $request, UserPasswordEncoderInterface $passwordEncoder): Response
    {
        $form = $this->createForm(UserRegistrationType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()):
            $data_form = $form->getData();

            $userRepository = $this->getDoctrine()->getRepository(User::class);
            $users_array = $userRepository->findBy(array('email' => $data_form['email']));

            if ($data_form['password'] === $data_form['password_repeated'] && empty($users_array)):
                # Creamos un nuevo usuario
                $user = new User();

                # Seteamos todas las propiedades no nullables
                $user->setName($data_form['name']);
                $user->setSurname($data_form['surname']);
                $user->setPassword($passwordEncoder->encodePassword($user, $data_form['password']));
                $user->setEmail($data_form['email']);

                if ($data_form['phone_number'] !== NULL):
                    $user->setPhoneNumber($data_form['phone_number']);
                endif;


                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($user);
                $entityManager->flush();
                $this->addFlash('success', '¡Te has registrado correctamente!');
            else:
                $this->addFlash('error', 'Ha ocurrido un error al registrarte ');
            endif;

        endif;

        $template = 'user/registration.html.twig';

        $data = array(
            'form' => $form->createView()
        );
        return $this->render($template, $data);


    }

    /**
     * @Route("/user/login", name="app_login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) :
            return $this->redirectToRoute('home');
        endif;

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/user/logout", name="app_logout")
     */
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * @Route("/user/profile", name="user_profile")
     */
    public function userProfile(Request $request): Response
    {

        if (!$this->getUser()) :
            return $this->redirectToRoute('home');
        endif;


        $form = $this->createFormBuilder(array('csrf_protection' => FALSE))
            ->setMethod(Request::METHOD_POST)
            ->setAction($this->generateUrl(static::ROUTE_USER_PROFILE))
            ->add('name', TextType::class, array(
                'required' => FALSE,
                'label' => 'Nombre',
                'data' => $this->getUser()->getName(),
            ))
            ->add('surname', TextType::class, array(
                'required' => FALSE,
                'label' => 'Apellidos',
                'data' => $this->getUser()->getSurname()
            ))
            ->add('email', TextType::class, array(
                'required' => FALSE,
                'label' => 'Email',
                'data' => $this->getUser()->getUsername()
            ))
            ->add('password', TextType::class, array(
                'required' => FALSE,
                'label' => 'Contraseña',
                'data' => $this->getUser()->getSurname()
            ))
            ->add('privileges', TextType::class, array(
                'required' => FALSE,
                'label' => 'Privilegios',
                'data' => self::convertPrivilegesToString($this->getUser()->getPrivileges()),
                'attr' => array(
                    'readonly' => true,
                ),
            ))
            ->add('phone_number', TextType::class, array(
                'required' => FALSE,
                'label' => 'Teléfono',
                'data' => $this->getUser()->getPhoneNumber() ?? '',
            ))
            ->add('submit', SubmitType::class, array(
                'label' => 'Guardar',
            ))
            ->getForm();

        $form->handleRequest($request);

        $template = 'user/profile.html.twig';

        $data = array(
            'form' => $form->createView()
        );

        return $this->render($template, $data);

    }

    # ------------------------------------------------- METHODS ------------------------------------------------------ #


    # --------------------------------------------- PRIVATE METHODS -------------------------------------------------- #


    # ---------------------------------------------- STATIC METHODS -------------------------------------------------- #
    /**
     * Recibe como parámetro el privilegio del usuario y devuelve el string asociado
     * @param int $number
     * @return string
     */
    public static function convertPrivilegesToString(int $number): string
    {
        switch ($number):

            case User::ROLE_ADMIN:
                $value = 'ADMIN';
                break;
            case User::ROLE_USER:
                $value = 'USER';
                break;
            case User::ROLE_MODERATOR:
                $value = 'MODERATOR';
                break;
            default:
                $value = 'UNDEFINED';
                break;

        endswitch;

        return  $value;
    }

}
