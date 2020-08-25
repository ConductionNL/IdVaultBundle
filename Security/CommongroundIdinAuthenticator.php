<?php

// src/Security/TokenAuthenticator.php

/*
 * This authenticator authenticates against DigiSpoof
 *
 */

namespace Conduction\CommonGroundBundle\Security;

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

class CommongroundIdinAuthenticator extends AbstractGuardAuthenticator
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
        return 'app_user_idin' === $request->attributes->get('_route')
            && $request->isMethod('GET') && $request->query->get('code');
    }

    /**
     * Called on every request. Return whatever credentials you want to
     * be passed to getUser() as $credentials.
     */
    public function getCredentials(Request $request)
    {
        $code = $request->query->get('code');

        $body = [
            'client_id'    => 'demo-preprod-basic',
            'grant_type'   => 'authorization_code',
            'code'         => $code,
            'redirect_uri' => 'https://checkin.dev.zuid-drecht.nl/idin',
        ];

        $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => 'https://eu01.preprod.signicat.com',
            // You can set any number of default request options.
            'timeout'  => 2.0,
        ]);

        $response = $client->request('POST', '/oidc/token', [
            'auth'        => ['demo-preprod-basic', 'KmcxXfuttfBGnn86DlW8Tg3_dYu6khWafkn5uVo7fGg'],
            'form_params' => $body,
        ]);

        $token = json_decode($response->getBody()->getContents(), true);

        $headers = [
            'Authorization' => 'Bearer '.$token['access_token'],
            'Accept'        => 'application/json',
        ];

        $response = $client->request('GET', '/oidc/userinfo', [
            'headers' => $headers,
        ]);

        $user = json_decode($response->getBody()->getContents(), true);

        $credentials = [
            'username'    => $user['consumer.bin'],
            'firstName'   => $user['consumer.partnerlastname'],
            'lastName'    => $user['consumer.legallastname'],
            'postalCode'  => $user['consumer.postalcode'],
            'streetName'  => $user['consumer.street'],
            'houseNumber' => $user['consumer.houseno'],
            'country'     => $user['consumer.country'],
            'city'        => $user['consumer.city'],
        ];

        if (isset($user['consumer.email'])) {
            $credentials['email'] = $user['consumer.email'];
        }

        if (isset($user['consumer.telephone'])) {
            $credentials['telephone'] = $user['consumer.telephone'];
        }

        $request->getSession()->set(
            Security::LAST_USERNAME,
            $credentials['username']
        );

        return $credentials;
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $provider = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'providers'], ['name' => 'idin'])['hydra:member'];
        $token = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'tokens'], ['token' => $credentials['username'], 'provider.name' => $provider[0]['name']])['hydra:member'];
        $application = $this->commonGroundService->getResource(['component' => 'wrc', 'type' => 'applications', 'id' => getenv('APP_ID')]);

        if (!$token || count($token) < 1) {
            //create email
            $email = [];
            $email['name'] = $credentials['email'];
            $email['email'] = $credentials['email'];
            $email = $this->commonGroundService->createResource($email, ['component' => 'cc', 'type' => 'emails']);

            //create phoneNumber
            $telephone = [];
            $telephone['name'] = $credentials['telephone'];
            $telephone['telephone'] = $credentials['telephone'];
            $telephone = $this->commonGroundService->createResource($telephone, ['component' => 'cc', 'type' => 'telephones']);

            //create address
            $address = [];
            $address['name'] = $credentials['firstName'];
            $address['street'] = $credentials['streetName'];
            $address['houseNumber'] = $credentials['houseNumber'];
            $address['postalCode'] = $credentials['postalCode'];
            $address['country'] = $credentials['country'];
            $address['region'] = $credentials['city'];
            $address = $this->commonGroundService->createResource($address, ['component' => 'cc', 'type' => 'addresses']);

            //create person
            $person = [];
            $person['name'] = $credentials['firstName'];
            $person['givenName'] = $credentials['firstName'];
            $person['familyName'] = $credentials['lastName'];
            $person['emails'] = [$email['@id']];
            $person['telephones'] = [$telephone['@id']];
            $person['addresses'] = [$address['@id']];
            $person = $this->commonGroundService->createResource($person, ['component' => 'cc', 'type' => 'people']);

            //create user
            $user = [];
            $user['username'] = $credentials['username'];
            $user['password'] = $credentials['username'];
            $user['person'] = $person['@id'];
            $user['organization'] = $application['organization']['@id'];
            $user = $this->commonGroundService->createResource($user, ['component' => 'uc', 'type' => 'users']);

            //create token
            $token = [];
            $token['token'] = $credentials['username'];
            $token['user'] = $user['@id'];
            $token['provider'] = $provider[0]['@id'];
            $token = $this->commonGroundService->createResource($token, ['component' => 'uc', 'type' => 'tokens']);

            $token = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'tokens'], ['token' => $credentials['username'], 'provider.name' => $provider[0]['name']])['hydra:member'];
        }

        $token = $token[0];

        $user = $this->commonGroundService->getResource($token['user']['@id']);

        if (!in_array('ROLE_USER', $user['roles'])) {
            $user['roles'][] = 'ROLE_USER';
        }

        return new CommongroundUser($user['username'], $user['username'], null, $user['roles'], $user['person'], $user['organization'], 'idin');
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        $provider = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'providers'], ['name' => 'idin'])['hydra:member'];
        $token = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'tokens'], ['token' => $credentials['username'], 'provider.name' => $provider[0]['name']])['hydra:member'];
        $application = $this->commonGroundService->getResource(['component' => 'wrc', 'type' => 'applications', 'id' => getenv('APP_ID')]);

        if (!$token || count($token) < 1) {
            return;
        }

        // no adtional credential check is needed in this case so return true to cause authentication success
        return true;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        return new RedirectResponse($this->router->generate('app_default_index'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        return new RedirectResponse($this->router->generate('app_user_eherkenning', ['response' => $request->request->get('back_url'), 'back_url' => $request->request->get('back_url')]));
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
