<?php

// src/Security/TokenAuthenticator.php

/*
 * This authenticator authenticates against DigiSpoof
 *
 */

namespace Conduction\IdVaultBundle\Security;

use Conduction\IdVaultBundle\Event\IdVaultEvents;
use Conduction\IdVaultBundle\Event\LoggedInEvent;
use Conduction\IdVaultBundle\Event\NewUserEvent;
use Conduction\IdVaultBundle\Security\User\IdVaultUser;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Conduction\IdVaultBundle\Service\IdVaultService;
use Doctrine\ORM\EntityManagerInterface;
use mysql_xdevapi\Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class IdVaultAuthenticator extends AbstractGuardAuthenticator
{
    private $em;
    private $params;
    private $commonGroundService;
    private $csrfTokenManager;
    private $router;
    private $urlGenerator;
    private $idVaultService;
    private $eventDispatcher;

    public function __construct(EntityManagerInterface $em, ParameterBagInterface $params, IdVaultService $idVaultService, CommonGroundService $commonGroundService, CsrfTokenManagerInterface $csrfTokenManager, RouterInterface $router, UrlGeneratorInterface $urlGenerator, SessionInterface $session, EventDispatcherInterface $eventDispatcher)
    {
        $this->em = $em;
        $this->params = $params;
        $this->commonGroundService = $commonGroundService;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->router = $router;
        $this->urlGenerator = $urlGenerator;
        $this->session = $session;
        $this->idVaultService = $idVaultService;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning false will cause this authenticator
     * to be skipped.
     */
    public function supports(Request $request)
    {
        return 'app_user_idvault' === $request->attributes->get('_route')
            && $request->isMethod('GET') && $request->query->get('code');
    }

    /**
     * Called on every request. Return whatever credentials you want to
     * be passed to getUser() as $credentials.
     */
    public function getCredentials(Request $request)
    {
        $code = $request->query->get('code');

        $application = $this->commonGroundService->cleanUrl(['component'=>'wrc', 'type'=>'applications', 'id'=>$this->params->get('app_id')]);
        $providers = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'providers'], ['type' => 'id-vault', 'application' => $this->params->get('app_id')])['hydra:member'];
        $provider = $providers[0];

        $backUrl = $request->query->get('backUrl', false);
        if ($backUrl) {
            $this->session->set('backUrl', $backUrl);
        }

        $accessToken = $this->idVaultService->authenticateUser($code, $provider['configuration']['app_id'], $provider['configuration']['secret']);

        $json = base64_decode(explode('.', $accessToken['accessToken'])[1]);
        $json = json_decode($json, true);

        $credentials = [
            'username'      => $json['email'],
            'email'         => $json['email'],
            'givenName'     => $json['given_name'],
            'familyName'    => $json['family_name'],
            'id'            => $json['jti'],
            'authorization' => $accessToken['accessToken'],
            'newUser'       => $accessToken['newUser'],
            'groups'        => $json['groups'],
            'organizations' => $json['organizations']
        ];

        $request->getSession()->set(
            Security::LAST_USERNAME,
            $credentials['username']
        );

        return $credentials;
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $application = $this->commonGroundService->cleanUrl(['component'=>'wrc', 'type'=>'applications', 'id'=>$this->params->get('app_id')]);
        $providers = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'providers'], ['type' => 'id-vault', 'application' => $this->params->get('app_id')])['hydra:member'];
        $tokens = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'tokens'], ['token' => $credentials['username'], 'provider.name' => $providers[0]['name']])['hydra:member'];

        if (!$tokens || count($tokens) < 1) {
            $users = $this->commonGroundService->getResourceList(['component'=>'uc', 'type'=>'users'], ['username'=> $credentials['username']], true, false, true, false, false);
            $users = $users['hydra:member'];

            // User dosnt exist
            if (count($users) < 1) {
                if (isset($credentials['telephone'])) {
                    $telephone = [];
                    $telephone['name'] = $credentials['telephone'];
                    $telephone['telephone'] = $credentials['telephone'];
                }

                //create email
                $emailObect = [];
                $emailObect['name'] = $credentials['email'];
                $emailObect['email'] = $credentials['email'];

                //create person
                $person = [];
                $person['givenName'] = $credentials['givenName'];
                $person['familyName'] = $credentials['familyName'];
                $person['emails'] = [$emailObect];
                if (isset($credentials['telephone'])) {
                    $person['telephones'] = [$telephone];
                }

                $person = $this->commonGroundService->createResource($person, ['component' => 'cc', 'type' => 'people']);

                //create user
                $user = [];
                $user['username'] = $credentials['username'];
                $user['password'] = $credentials['id'];
                $user['person'] = $person['@id'];
                $user = $this->commonGroundService->createResource($user, ['component' => 'uc', 'type' => 'users']);
            } else {
                $user = $users[0];
            }

            //create token
            $token = [];
            $token['token'] = $credentials['username'];
            $token['user'] = 'users/'.$user['id'];
            $token['provider'] = 'providers/'.$providers[0]['id'];
            $token = $this->commonGroundService->createResource($token, ['component' => 'uc', 'type' => 'tokens']);

            $token = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'tokens'], ['token' => $credentials['username'], 'provider.name' => $providers[0]['name']])['hydra:member'];
        } else {
            $token = $tokens[0];
            // Deze $urls zijn een hotfix voor niet werkende @id's op de cgb cgs
            $userUlr = $this->commonGroundService->cleanUrl(['component'=>'uc', 'type'=>'users', 'id'=>$token['user']['id']]);
            $user = $this->commonGroundService->getResource($userUlr);
        }
        try {
            $person = $this->commonGroundService->getResource($user['person']);
        } catch (\Throwable $e) {
            if (isset($credentials['telephone'])) {
                $telephone = [];
                $telephone['name'] = $credentials['telephone'];
                $telephone['telephone'] = $credentials['telephone'];
            }

            //create email
            $emailObect = [];
            $emailObect['name'] = $credentials['email'];
            $emailObect['email'] = $credentials['email'];

            //create person
            $person = [];
            $person['givenName'] = $credentials['givenName'];
            $person['familyName'] = $credentials['familyName'];
            $person['emails'] = [$emailObect];
            if (isset($credentials['telephone'])) {
                $person['telephones'] = [$telephone];
            }

            $person = $this->commonGroundService->createResource($person, ['component' => 'cc', 'type' => 'people']);
            $personUrl = $this->commonGroundService->cleanUrl(['component' => 'cc', 'type' => 'people', 'id' => $person['id']]);

            if (isset($user['userGroups'])) {
                foreach ($user['userGroups'] as &$group) {
                    $group = '/groups/'.$group['id'];
                }
            }
            $user['person'] = $personUrl;
            $user = $this->commonGroundService->updateResource($user);
        }

        $log = [];
        $log['address'] = $_SERVER['REMOTE_ADDR'];
        $log['method'] = 'Id-Vault';
        $log['status'] = '200';
        $log['application'] = $application;

        $this->commonGroundService->saveResource($log, ['component' => 'uc', 'type' => 'login_logs']);

        if (!in_array('ROLE_USER', $user['roles'])) {
            $user['roles'][] = 'ROLE_USER';
        }
        foreach ($user['roles'] as $key=>$role) {
            if (strpos($role, 'ROLE_') !== 0) {
                $user['roles'][$key] = "ROLE_$role";
            }
        }

        if ($credentials['newUser']) {
            $event = new NewUserEvent($user);
            $this->eventDispatcher->dispatch($event, IdVaultEvents::NEWUSER);
        }


        $event = new LoggedInEvent($user);
        $this->eventDispatcher->dispatch($event, IdVaultEvents::LOGGEDIN);

        if (isset($user['organization'])) {
            return new IdVaultUser($user['username'], $user['username'], $person['name'], null, $user['roles'], $user['person'], $user['organization'], 'id-vault', false, $credentials['authorization'], null, $credentials['groups'], $credentials['organizations']);
        } else {
            return new IdVaultUser($user['username'], $user['username'], $person['name'], null, $user['roles'], $user['person'], null, 'id-vault', false, $credentials['authorization'], null, $credentials['groups'], $credentials['organizations']);
        }
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        $application = $this->commonGroundService->cleanUrl(['component'=>'wrc', 'type'=>'applications', 'id'=>$this->params->get('app_id')]);
        $providers = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'providers'], ['type' => 'id-vault', 'application' => $this->params->get('app_id')])['hydra:member'];
        $tokens = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'tokens'], ['token' => $credentials['username'], 'provider.name' => $providers[0]['name']])['hydra:member'];

        if (!$tokens || count($tokens) < 1) {
            return;
        }

        // no adtional credential check is needed in this case so return true to cause authentication success
        return true;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $backUrl = $this->session->get('backUrl', false);
        if ($backUrl) {
            return new RedirectResponse($backUrl);
        }
        else {
            return new RedirectResponse($this->router->generate('app_default_index'));
        }
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        return new RedirectResponse($this->router->generate('app_user_idvault'));
    }

    /**
     * Called when authentication is needed, but it's not sent.
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        if ($this->params->get('app_subpath') && $this->params->get('app_subpath') != 'false') {
            return new RedirectResponse('/'.$this->params->get('app_subpath').$this->router->generate('app_user_idvault', []));
        } else {
            return new RedirectResponse($this->router->generate('app_user_idvault', [], UrlGeneratorInterface::RELATIVE_PATH));
        }
    }

    public function supportsRememberMe()
    {
        return true;
    }

    protected function getLoginUrl()
    {
        if ($this->params->get('app_subpath') && $this->params->get('app_subpath') != 'false') {
            return '/'.$this->params->get('app_subpath').$this->router->generate('app_user_idvault', [], UrlGeneratorInterface::RELATIVE_PATH);
        } else {
            return $this->router->generate('app_user_idvault', [], UrlGeneratorInterface::RELATIVE_PATH);
        }
    }
}
