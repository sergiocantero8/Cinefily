<?php /** @noinspection PhpParamsInspection */
/** @noinspection NotOptimalIfConditionsInspection */

/** @noinspection PhpUndefinedMethodInspection */

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Coupon;
use App\Entity\LogInfo;
use App\Entity\Session;
use App\Entity\Ticket;
use App\Entity\User;
use App\Form\UserRegistrationType;
use DateTime;
use Exception;
use Knp\Component\Pager\PaginatorInterface;
use LogicException;
use Swift_Mailer;
use Swift_Message;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
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
use function count;
use function in_array;

class UserController extends AbstractController
{

    # -------------------------------------------------- CONST ------------------------------------------------------- #

    public const ROUTE_USER_PROFILE = 'user_profile';
    public const ROUTE_USER_TICKETS = 'user_tickets';
    public const ROUTE_FORGOT_PASSWORD = 'forgot_password';
    public const ROUTE_LOGIN = 'app_login';
    public const ROUTE_LOGOUT = 'app_logout';
    public const ROUTE_USER_REGISTRATION = 'user_registration';
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
            $email = $data_form['email'];
            $users = $this->getDoctrine()->getRepository(User::class)->findBy(array('email' => $email));

            if ($data_form['password'] === $data_form['password_repeated'] && empty($users) &&
                filter_var($email, FILTER_VALIDATE_EMAIL)):
                # Creamos un nuevo usuario
                $user = new User();

                # Seteamos todas las propiedades no nullables
                $user->setName($data_form['name']);
                $user->setSurname($data_form['surname']);
                $user->setPassword($passwordEncoder->encodePassword($user, $data_form['password']));
                $user->setEmail($email);
                $user->setCreatedAt(new DateTime());


                if ($data_form['phone_number'] !== NULL):
                    $user->setPhoneNumber($data_form['phone_number']);
                endif;

                if ($data_form['city'] !== null):
                    $user->setCity($data_form['city']);
                endif;

                $entityManager = $this->getDoctrine()->getManager();
                $entityManager->persist($user);
                $entityManager->flush();

                $coupon = new Coupon();
                $coupon->setUser($user);
                $coupon->setActive(true);
                $coupon->setCode('BIENVENIDO');
                $coupon->setDiscount(10);

                $entityManager->persist($coupon);
                $entityManager->flush();

                $this->addFlash('success', '¡Te has registrado correctamente!');
                $this->redirectToRoute('home');
            else:
                $this->addFlash('error', 'Ha ocurrido un error al registrarte. Revisa que todos los campos 
                 están correctos.');
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
        if ($this->getUser()):
            $createdAt = $this->getUser()->getCreatedAt();
            $now = new DateTime();
            $found = $this->getDoctrine()->getRepository(Coupon::class)->findBy(array('user' => $this->getUser(), 'code' => '1MONTH'));

            if ($createdAt->diff($now)->m >= 1 && empty($found)):
                $coupon = new Coupon();
                $coupon->setDiscount(20);
                $coupon->setCode('1MONTH');
                $coupon->setUser($this->getUser());
                $coupon->setActive(true);

                $em = $this->getDoctrine()->getManager();
                $em->persist($coupon);
                $em->flush();
            endif;

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
    public function forgotPassword(Request $request, Swift_Mailer $mailer, UserPasswordEncoderInterface $passwordEncoder): RedirectResponse
    {
        $email = $request->get('email');

        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(array('email' => $email));
        if (!empty($user) && $user instanceof User):
            $randomPassword = $this->generateRandomPassword();
            $message = (new Swift_Message('Recuperación de contraseña'))
                ->setFrom('cinefily@gmail.com')
                ->setTo($email)
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
        throw new LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    /**
     * Ruta para visualizar todos los datos del perfil del usuario y con posibilidad de editarlos
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
            ->add('password', PasswordType::class, array(
                'required' => FALSE,
                'label' => 'Contraseña',
            ))
            ->add('privileges', TextType::class, array(
                'required' => FALSE,
                'label' => 'Privilegios',
                'help' => 'Solo el administrador puede cambiar los privilegios de un usuario',
                'data' => static::convertPrivilegesToString($user->getPrivileges()),
                'attr' => array(
                    'readonly' => true,
                ),
            ))
            ->add('phone_number', TextType::class, array(
                'required' => FALSE,
                'label' => 'Teléfono',
                'data' => $user->getPhoneNumber() ?? '',
            ))
            ->add('city', TextType::class, array(
                'required' => FALSE,
                'label' => 'Ciudad',
                'data' => $user->getCity() ?? '',
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

                # Comprobamos si ya tenía una foto de perfil, si es así eliminamos la antigua
                if ($user->getPhoto() !== null):
                    $fs = new Filesystem();
                    $fs->remove($this->getParameter('images_directory') . '/' . $user->getPhoto());
                endif;

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
                $user->setPassword($passwordEncoder->encodePassword($user, $formData['password']));
            endif;
            $user->setEmail($formData['email']);

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();


        }

        $template = 'user/profile.html.twig';
        $date = $this->getUser()->getCreatedAt()->format('d/m/Y');

        $stats = $this->getNItemsProfile();


        $data = array(
            'form' => $form->createView(),
            'user' => $user,
            'user_comments' => $stats['nComments'],
            'user_tickets' => $stats['nTickets'],
            'user_coupons' => $stats['nCoupons'],
            'created_at' => $date
        );

        return $this->render($template, $data);

    }


    /**
     * Ruta para visualizar todos los datos del perfil del usuario y con posibilidad de editarlos
     * @Route("/user/myTickets", name="user_tickets")
     */
    public function userTickets(Request $request, PaginatorInterface $paginator): Response
    {

        if (!$this->getUser()) :
            return $this->redirectToRoute('home');
        endif;


        $user = $this->getUser() ?? new User();

        $template = 'user/my_tickets.html.twig';

        $ticketsPagination = null;
        $myTicketsQuery = $this->getDoctrine()->getRepository(Ticket::class)->findByUser($this->getUser());


        if ($myTicketsQuery !== null):
            // Paginar los resultados de la consulta
            $ticketsPagination = $paginator->paginate(
            // Consulta Doctrine, no resultados
                $myTicketsQuery,
                // Definir el parámetro de la página
                $request->query->getInt('page', 1),
                // Items per page
                6
            );
        endif;

        $tickets = array();
        foreach ($ticketsPagination as $ticket):
            $session = $this->getDoctrine()->getRepository(Session::class)->findOneBy(array('id' => $ticket->getSession()));

            if ($session !== null):
                $tickets[] = array('session' => $session, 'event' => $session->getEvent(), 'room' => $session->getRoom(),
                    'cinema' => $session->getCinema(), 'seat' => $ticket->getSeatBooked(), 'ticket' => $ticket);
            endif;

        endforeach;


        $stats = $this->getNItemsProfile();


        return $this->render($template, array('tickets' => $tickets, 'user' => $user, 'user_tickets' => $stats['nComments'],
            'user_comments' => $stats['nComments'], 'user_coupons' => $stats['nCoupons'], 'tickets_pagination' => $ticketsPagination));

    }


    /**
     * Ruta para visualizar todos los cupones disponibles para el usuario
     * @Route("/user/myCoupons", name="user_coupons")
     */
    public function userCoupons(Request $request, PaginatorInterface $paginator): Response
    {

        if (!$this->getUser()) :
            return $this->redirectToRoute('home');
        endif;
        $user = $this->getUser();

        $template = 'user/my_coupons.html.twig';

        $myCouponsQuery = $this->getDoctrine()->getRepository(Coupon::class)->findByUser($this->getUser());


        if ($myCouponsQuery !== null):
            // Paginar los resultados de la consulta
            $couponsPagination = $paginator->paginate(
            // Consulta Doctrine, no resultados
                $myCouponsQuery,
                // Definir el parámetro de la página
                $request->query->getInt('page', 1),
                // Items per page
                4
            );
        endif;

        $stats = $this->getNItemsProfile();


        return $this->render($template, array('coupons' => $couponsPagination ?? null, 'user' => $user, 'user_tickets' => $stats['nTickets'],
            'user_comments' => $stats['nComments'], 'user_coupons' => $stats['nCoupons']));

    }

    /**
     * @Route("/comment/delete", name="comment_delete")
     * @param Request $request
     * @return RedirectResponse
     */
    public function commentDelete(Request $request): RedirectResponse
    {
        $eventPath = $request->get('path');
        $comment = $this->getDoctrine()->getRepository(Comment::class)->find($request->get('id_comment'));
        if (!$this->getUser() || $comment === null || ($comment->getUser() !== $this->getUser())) :
            return $this->redirectToRoute('home');
        endif;

        $em = $this->getDoctrine()->getManager();

        $em->remove($comment);
        $em->flush();

        $this->addFlash('success', 'El comentario se ha eliminado correctamente');
        return $this->redirect($eventPath);
    }


    /**
     * @Route("/admin/users", name="admin_users")
     * @param Request $request
     * @param PaginatorInterface $paginator
     * @return RedirectResponse
     */
    public function adminUsers(Request $request, PaginatorInterface $paginator): Response
    {

        if (!$this->getUser() || ($this->getUser() && !in_array(User::ROLE_ADMIN, $this->getUser()->getRoles(), true))):
            $this->addFlash('error', 'No tienes acceso a la ruta ' . $request->getBaseUrl());
            return $this->redirectToRoute('home');
        endif;


        $usersResults = null;
        $usersQuery = $this->getDoctrine()->getRepository(User::class)
            ->createQueryBuilder('u')
            ->getQuery();


        if ($usersQuery !== null):
            // Paginar los resultados de la consulta
            $usersResults = $paginator->paginate(
            // Consulta Doctrine, no resultados
                $usersQuery,
                // Definir el parámetro de la página
                $request->query->getInt('page', 1),
                // Items per page
                10
            );
        endif;


        return $this->render('user/admin_users.html.twig', array('results' => $usersResults));
    }

    /**
     * @Route("/admin/user/delete", name="delete_user")
     * @param Request $request
     * @return RedirectResponse
     */
    public function deleteUser(Request $request): RedirectResponse
    {

        if (!$this->getUser() || ($this->getUser() && !in_array(User::ROLE_ADMIN, $this->getUser()->getRoles(), true))):
            $this->addFlash('error', 'No tienes acceso a la ruta ' . $request->getBaseUrl());
            return $this->redirectToRoute('home');
        endif;

        $userID = $request->get('id_user');


        if ($userID !== null):
            $user = $this->getDoctrine()->getRepository(User::class)->find($userID);

            if ($user !== null && $user !== $this->getUser()):

                # Borramos la foto de perfil del servidor
                if ($user->getPhoto() !== null):
                    $fs = new Filesystem();
                    $fs->remove($this->getParameter('images_directory') . '/' . $user->getPhoto());
                endif;

                $em = $this->getDoctrine()->getManager();

                # Buscamos si tiene algún ticket comprado para setear a null el usuario asociado
                $tickets = $this->getDoctrine()->getRepository(Ticket::class)->findBy(array('user' => $user));

                foreach ($tickets as $ticket):
                    $ticket->setUser(null);
                    $em->persist($ticket);
                endforeach;
                $em->flush();

                # Borramos el usuario
                $em->remove($user);
                $em->flush();

                $logInfo = new LogInfo(LogInfo::TYPE_SUCCESS, 'Se ha eliminado el usuario con ID ' . $userID);
                $em->persist($logInfo);
                $em->flush();
                $this->addFlash('success', 'Se ha eliminado el usuario correctamente');
            else:
                $this->addFlash('error', 'Error al eliminar un usuario');
            endif;
        endif;


        return $this->redirectToRoute('home');
    }

    /**
     * @Route("/admin/user/details", name="user_details")
     * @param Request $request
     * @return Response
     */
    public function viewUserDetails(Request $request): Response
    {

        if (!$this->getUser() || ($this->getUser() && !in_array(User::ROLE_ADMIN, $this->getUser()->getRoles(), true))):
            $this->addFlash('error', 'No tienes acceso a la ruta ' . $request->getBaseUrl());
            return $this->redirectToRoute('home');
        endif;

        $userID = $request->get('id');


        if ($userID !== null):
            $user = $this->getDoctrine()->getRepository(User::class)->find($userID);
            if ($user !== null):
                $stats = $this->getNItemsProfile($user);
                $privileges = static::convertPrivilegesToString($user->getPrivileges());
            endif;
        endif;


        return $this->render('user/admin_profile_details.html.twig', array('user' => $user ?? null,
            'user_comments' => $stats['nComments'] ?? null, 'user_tickets' => $stats['nTickets'] ?? null,
            'privileges' => $privileges ?? null, 'user_coupons' => $stats['nCoupons'] ?? null,));
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

    /**
     * Obtiene el número de comentarios realizados, cupones y entradas compradas del usuario identificado
     * @return array
     */
    public function getNItemsProfile(User $user = null): ?array
    {

        $result = null;

        if ($user !== null):
            $result = array(
                'nTickets' => count($this->getDoctrine()->getRepository(Ticket::class)->findBy(
                    array('user' => $user->getId()))),
                'nComments' => count($this->getDoctrine()->getRepository(Comment::class)->findBy(
                    array('user' => $user->getId()))),
                'nCoupons' => count($this->getDoctrine()->getRepository(Coupon::class)->findBy(
                    array('user' => $user->getId())))
            );
        elseif($this->getUser()):
            $result = array(
                'nTickets' => count($this->getDoctrine()->getRepository(Ticket::class)->findBy(
                    array('user' => $this->getUser()->getId()))),
                'nComments' => count($this->getDoctrine()->getRepository(Comment::class)->findBy(
                    array('user' => $this->getUser()->getId()))),
                'nCoupons' => count($this->getDoctrine()->getRepository(Coupon::class)->findBy(
                    array('user' => $this->getUser()->getId())))
            );
        endif;

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
