<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserRegistrationType;
use DateTime;
use Exception;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
                $user->setCreatedAt(new DateTime());


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
     * @Route("/user/forgotPassword", name="forgot_password")
     * @throws Exception
     */
    public function forgotPassword(Request $request, \Swift_Mailer $mailer, UserPasswordEncoderInterface $passwordEncoder): RedirectResponse
    {
        $email = $request->get('email');

        $userRepository = $this->getDoctrine()->getRepository(User::class);
        $user = $userRepository->findOneBy(array('email' => $email));
        if (!empty($user) && $user instanceof User):
            $randomPassword = $this->generateRandomPassword();
            $message = (new \Swift_Message('Recuperación de contraseña'))
                ->setFrom('cinefily@gmail.com')
                ->setTo('sergiocantero8@gmail.com')
                ->setBody(
                    'Hemos recibido tu solicitud de que no recuerdas la contraseña. No te preocupes tu nueva contraseña
                    es ' . $randomPassword,
                    'text/plain'
                );
            if ($mailer->send($message)):
                $user->setPassword($passwordEncoder->encodePassword($user, $randomPassword));
                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($user);
                $entityManager->flush();
                $this->addFlash('success', 'Te hemos enviado un email a tu correo con la nueva contraseña');
            else:
                $this->addFlash('error', 'No ha sido posible enviarte el email de recuperación de contraseña');
            endif;
        else:
            $this->addFlash('error', 'El email que has introducido no existe');
            $this->redirectToRoute('app_login');
        endif;


        return $this->redirectToRoute('home');
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
    public function userProfile(Request $request, UserPasswordEncoderInterface $passwordEncoder): Response
    {

        if (!$this->getUser()) :
            return $this->redirectToRoute('home');
        endif;


        $user = $this->getUser() ?? new User();

        $form = $this->createFormBuilder(array('csrf_protection' => FALSE))
            ->setMethod(Request::METHOD_POST)
            ->setAction($this->generateUrl(static::ROUTE_USER_PROFILE))
            ->add('name', TextType::class, array(
                'required' => FALSE,
                'label' => 'Nombre',
                'data' => $user->getName(),
            ))
            ->add('surname', TextType::class, array(
                'required' => FALSE,
                'label' => 'Apellidos',
                'data' => $user->getSurname()
            ))
            ->add('email', TextType::class, array(
                'required' => FALSE,
                'label' => 'Email',
                'data' => $user->getUsername()
            ))
            ->add('password', TextType::class, array(
                'required' => FALSE,
                'label' => 'Contraseña',
            ))
            ->add('privileges', TextType::class, array(
                'required' => FALSE,
                'label' => 'Privilegios',
                'help' => 'Solo el administrador puede cambiar los privilegios de un usuario',
                'data' => self::convertPrivilegesToString($user->getPrivileges()),
                'attr' => array(
                    'readonly' => true,
                ),
            ))
            ->add('phone_number', TextType::class, array(
                'required' => FALSE,
                'label' => 'Teléfono',
                'data' => $user->getPhoneNumber() ?? '',
            ))
            ->add('profile_photo', FileType::class, array(
                'label' => 'Foto de perfil',
                'data_class' => null,
                'required' => false,
                'attr' => array(
                    'placeholder' => 'Para actualizar la foto de perfil...'
                )
            ))
            ->add('submit', SubmitType::class, array(
                'label' => 'Guardar',
            ))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();


            $img = $formData['profile_photo'];

            if ($img) {
                $fileName = md5(uniqid('', false)) . '' . $img->guessExtension();

                try {
                    $img->move(
                        $this->getParameter('images_directory'),
                        $fileName
                    );
                } catch (FileException $e) {
                    $this->createNotFoundException('Directorio images no encontrado');
                }

                $user->setPhoto($fileName);

            }

            $user->setName($formData['name']);
            $user->setSurname($formData['surname']);

            if ($formData['password'] !== NULL):
                $user->setPassword($user->setPassword($passwordEncoder->encodePassword($user, $formData['password'])));
            endif;
            $user->setEmail($formData['email']);

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();


        }

        $template = 'user/profile.html.twig';

        $data = array(
            'form' => $form->createView(),
            'image' => $this->getUser()->getPhoto(),
            'name' => $this->getUser()->getName()
        );

        return $this->render($template, $data);

    }

    # ------------------------------------------------- METHODS ------------------------------------------------------ #

    /**
     * Genera una contraseña aleatoria dependiendo de la longitud que se le pase
     * @param int $length
     * @return string
     * @throws Exception
     */
    public function generateRandomPassword(int $length = 8): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $count = mb_strlen($chars);

        for ($i = 0, $result = ''; $i < $length; $i++) {
            $index = random_int(0, $count - 1);
            $result .= mb_substr($chars, $index, 1);
        }

        return $result;
    }

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

        return $value;
    }

}
