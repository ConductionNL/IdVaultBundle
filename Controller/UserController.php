<?php
// src/Controller/DefaultController.php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Knp\Bundle\MarkdownBundle\MarkdownParserInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use App\Service\CommonGroundService;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use App\Security\User\CommongroundUser;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Class UserController
 * @package App\Controller
 *
 */
class UserController extends AbstractController
{
    /**
     * @Route("/login")
     * @Template
     */
    public function login(Request $request, CommonGroundService $commonGroundService,  ParameterBagInterface $params, EventDispatcherInterface $dispatcher)
    {
        return $this->render('login/index.html.twig');
    }

    /**
     * @Route("/logout")
     * @Template
     */
    public function logout(Request $request, CommonGroundService $commonGroundService,  ParameterBagInterface $params, EventDispatcherInterface $dispatcher)
    {
        return [];
    }

    /**
     * @Route("/register")
     * @Template
     */
    public function registerAction(Request $request, CommonGroundService $commonGroundService, ParameterBagInterface $params, EventDispatcherInterface $dispatcher)
    {
        // Kijken of het formulier is getriggerd
        if ($request->isMethod('POST')) {

            // Lets check on required values
            $requiredValues = ['givenName','familyName','password','password2','username'];
            $error = false;
            foreach($requiredValues as $requiredValue){

                if(!$request->request->get($requiredValue)|| $request->request->get($requiredValue) == null){
                    $this->addFlash('danger', $requiredValue.' is a required value');
                    $error = true;
                }
            }


            if($request->request->get('password') != $request->request->get('password2')){
                $this->addFlash('danger','Password and repeat password do not match');
                $error = true;
            }


            $users = $commonGroundService->getResourceList($params->get('auth_provider_user').'/users',["username"=> $request->request->get('username')], true);
            $users = $users["hydra:member"];

            if($users && count($users) >= 1){
                $this->addFlash('danger','Username is already taken');
                $error = true;
            }

            if($error){
                return [];
            }


            $application = $commonGroundService->getApplication();

            // contact persoon aanmaken op order
            $contact['givenName'] = $request->request->get('givenName');
            $contact['familyName'] = $request->request->get('familyName');
            $contact['emails'] = [];
            $contact['emails'][] = ["name" => "primary", "email" => $request->request->get('username')];
            //$contact['organization'] = 'https://wrc.larping.eu'.$application['organization']['@id'];
            $contact = $commonGroundService->createResource($contact, 'https://cc.larping.eu/people'); /*@todo awfulle specific */

            //  Create the uses
            $user = [];
            $user['username'] =  $request->request->get('username');
            $user['password'] =  $request->request->get('password');
            $user['organization'] = 'https://wrc.larping.eu'.$application['organization']['@id'];
            $user['person'] = 'https://cc.larping.eu'.$contact['@id'];
            //$contact['organization'] = 'https://wrc.larping.eu'.$application['organization']['@id'];
            $user= $commonGroundService->createResource($user, 'https://uc.larping.eu/users'); /*@todo awfulle specific */


            $user = new CommongroundUser($user['username'], $request->request->get('password'), null, ['user'] );

            // Manually authenticate user in controller
            $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
            $this->get('security.token_storage')->setToken($token);
            $this->get('session')->set('_security_main', serialize($token));


            //  Fire the login event manually
            $event = new InteractiveLoginEvent($request, $token);
            $dispatcher->dispatch($event);

            return $this->redirect($this->generateUrl('app_user_confirm'));
        }

        return [];
    }


    /**
     * @Route("/register-confirm")
     * @Template
     */
    public function confirmAction(Request $request)
    {
        return [];
    }

    /**
     * @Route("/reminder")
     * @Template
     */
    public function reminderAction(Request $request, EntityManagerInterface $em)
    {
        // Kijken of het formulier is getriggerd
        if ($request->isMethod('POST')) {

            // Lets check on required values
            $requiredValues = ['givenName','familyName','street','street','houseNumber','postalCode','locality','email'];
            $error = false;
            foreach($requiredValues as $requiredValue){
                if(!$request->request->get($requiredValue)|| $request->request->get($requiredValue) == null){
                    $this->addFlash('danger', $requiredValue.' is a required value');
                    $error = true;
                }
            }
        }

        return [];
    }

    /**
     * @Route("user/profile")
     * @Template
     */
    public function profileAction(Request $request)
    {
        return [];
    }

    /**
     * @Route("user/dashboard")
     * @Template
     */
    public function dashboardAction(Request $request)
    {
        return $this->redirect($this->generateUrl('app_wrc_templates'));
    }

    /**
     * @Route("user/characters")
     * @Template
     */
    public function charactersAction(Request $request)
    {
        return[];
    }

    /**
     * @Route("user/orders")
     * @Template
     */
    public function ordersAction(Request $request)
    {
        return[];
    }

    /**
     * @Route("user/reviews")
     * @Template
     */
    public function reviewsAction(Request $request)
    {
        return[];
    }

    /**
     * @Route("user/organizations")
     * @Template
     */
    public function organizationsAction(Request $request)
    {
        return[];
    }

    /**
     * @Route("user/settings")
     * @Template
     */
    public function settingsAction(Request $request)
    {
        return[];
    }

}
