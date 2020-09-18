<?php

// src/Security/TokenAuthenticator.php

/*
 * This authenticator authenticates against DigiSpoof
 *
 */

namespace Conduction\CommonGroundBundle\Security;

use Conduction\CommonGroundBundle\Entity\LoginLog;
use Conduction\CommonGroundBundle\Security\User\CommongroundUser;
use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
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

class CommongroundFacebookAuthenticator extends AbstractGuardAuthenticator
{
    private $em;
    private $params;
    private $commonGroundService;
    private $csrfTokenManager;
    private $router;
    private $urlGenerator;

    public function __construct(EntityManagerInterface $em, ParameterBagInterface $params, CommonGroundService $commonGroundService, CsrfTokenManagerInterface $csrfTokenManager, RouterInterface $router, UrlGeneratorInterface $urlGenerator, SessionInterface $session)
    {
        $this->em = $em;
        $this->params = $params;
        $this->commonGroundService = $commonGroundService;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->router = $router;
        $this->urlGenerator = $urlGenerator;
        $this->session = $session;
    }

    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning false will cause this authenticator
     * to be skipped.
     */
    public function supports(Request $request)
    {
        return 'app_user_facebook' === $request->attributes->get('_route')
            && $request->isMethod('GET') && $request->query->get('code');
    }

    /**
     * Called on every request. Return whatever credentials you want to
     * be passed to getUser() as $credentials.
     */
    public function getCredentials(Request $request)
    {
        $provider = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'providers'], ['name' => 'facebook'])['hydra:member'];
        $provider = $provider[0];
        $code = $request->query->get('code');

        $redirect = str_replace('http:', 'https:', $request->getUri());
        $redirect = substr($redirect, 0, strpos($redirect, '?'));

        $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => 'https://graph.facebook.com',
            // You can set any number of default request options.
            'timeout'  => 2.0,
        ]);

        $response = $client->request('GET', '/v8.0/oauth/access_token?client_id='.$provider['configuration']['app_id'].'&redirect_uri='.$redirect.'&client_secret='.$provider['configuration']['secret'].'&code='.$code);
        $accessToken = json_decode($response->getBody()->getContents(), true);

        $response = $client->request('GET', '/me?&fields=id,name,email&access_token='.$accessToken['access_token']);
        $user = json_decode($response->getBody()->getContents(), true);

        $credentials = [
            'username'  => $user['email'],
            'email'     => $user['email'],
            'name'      => $user['name'],
            'id'        => $user['id'],
        ];

        $request->getSession()->set(
            Security::LAST_USERNAME,
            $credentials['username']
        );

        return $credentials;
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $provider = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'providers'], ['name' => 'facebook'])['hydra:member'];
        $token = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'tokens'], ['token' => $credentials['id'], 'provider.name' => $provider[0]['name']])['hydra:member'];
        $application = $this->commonGroundService->getResource(['component' => 'wrc', 'type' => 'applications', 'id' => getenv('APP_ID')]);

        if (!$token || count($token) < 1) {

            $users = $this->commonGroundService->getResourceList(['component'=>'uc', 'type'=>'users'], ['username'=> $credentials['username']], true, false, true, false, false);
            $users = $users['hydra:member'];

            // User dosnt exist
            if (count($users) < 1) {

                //create email
                $email = [];
                $email['name'] = $credentials['email'];
                $email['email'] = $credentials['email'];
                $email = $this->commonGroundService->createResource($email, ['component' => 'cc', 'type' => 'emails']);

                //create person
                $names = explode(' ', $credentials['name']);
                $person = [];
                $person['givenName'] = $names[0];
                $person['familyName'] = end($names);
                $person['emails'] = [$email['@id']];
                $person = $this->commonGroundService->createResource($person, ['component' => 'cc', 'type' => 'people']);

                //create user
                $user = [];
                $user['username'] = $credentials['username'];
                $user['password'] = $credentials['id'];
                $user['person'] = $person['@id'];
                $user['organization'] = $application['organization']['@id'];
                $user = $this->commonGroundService->createResource($user, ['component' => 'uc', 'type' => 'users']);
            }
            else{
                $user = $users[0];
            }


            //create token
            $token = [];
            $token['token'] = $credentials['id'];
            $token['user'] = $user['@id'];
            $token['provider'] = $provider[0]['@id'];
            $token = $this->commonGroundService->createResource($token, ['component' => 'uc', 'type' => 'tokens']);

            $token = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'tokens'], ['token' => $credentials['id'], 'provider.name' => $provider[0]['name']])['hydra:member'];
        }

        $token = $token[0];

        $user = $this->commonGroundService->getResource($token['user']['@id']);

        $log = new LoginLog();
        $log->setAddress($_SERVER['REMOTE_ADDR']);
        $log->setMethod('Facebook');
        $log->setStatus('200');
        $this->em->persist($log);
        $this->em->flush($log);

        if (!in_array('ROLE_USER', $user['roles'])) {
            $user['roles'][] = 'ROLE_USER';
        }
        array_push($user['roles'], 'scope.chin.checkins.read');

        return new CommongroundUser($user['username'], $credentials['id'], $credentials['name'], null, $user['roles'], $user['person'], null, 'facebook');
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        $provider = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'providers'], ['name' => 'facebook'])['hydra:member'];
        $token = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'tokens'], ['token' => $credentials['id'], 'provider.name' => $provider[0]['name']])['hydra:member'];
        $application = $this->commonGroundService->getResource(['component' => 'wrc', 'type' => 'applications', 'id' => getenv('APP_ID')]);

        if (!$token || count($token) < 1) {
            return;
        }

        // no adtional credential check is needed in this case so return true to cause authentication success
        return true;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        return new RedirectResponse($this->router->generate('app_chin_checkin'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        return new RedirectResponse($this->router->generate('app_user_facebook'));
    }

    /**
     * Called when authentication is needed, but it's not sent.
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        if ($this->params->get('app_subpath') && $this->params->get('app_subpath') != 'false') {
            return new RedirectResponse('/'.$this->params->get('app_subpath').$this->router->generate('app_user_digispoof', []));
        } else {
            return new RedirectResponse($this->router->generate('app_user_digispoof', [], UrlGeneratorInterface::RELATIVE_PATH));
        }
    }

    public function supportsRememberMe()
    {
        return true;
    }

    protected function getLoginUrl()
    {
        if ($this->params->get('app_subpath') && $this->params->get('app_subpath') != 'false') {
            return '/'.$this->params->get('app_subpath').$this->router->generate('app_user_digispoof', [], UrlGeneratorInterface::RELATIVE_PATH);
        } else {
            return $this->router->generate('app_user_digispoof', [], UrlGeneratorInterface::RELATIVE_PATH);
        }
    }
}
