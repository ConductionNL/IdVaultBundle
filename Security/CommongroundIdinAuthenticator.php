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
            'client_id' => 'demo-preprod-basic',
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => 'https://checkin.dev.zuid-drecht.nl/idin',
        ];

        $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => 'https://eu01.preprod.signicat.com',
            // You can set any number of default request options.
            'timeout'  => 2.0,
        ]);

        $response = $client->request('POST', '/oidc/token', [
            'auth' => ['demo-preprod-basic', 'KmcxXfuttfBGnn86DlW8Tg3_dYu6khWafkn5uVo7fGg'],
            'form_params' => $body
        ]);


        $token = json_decode($response->getBody()->getContents(), true);

        var_dump($token['access_token']);
        die;

        $credentials = [
            'username'   => $request->request->get('code'),
        ];

        $request->getSession()->set(
            Security::LAST_USERNAME,
            $credentials['username']
        );

        return $credentials;
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $users = $this->commonGroundService->getResourceList(['component'=>'uc', 'type'=>'users'], ['username'=> $credentials['username']], true)['hydra:member'];

        if (!$users || count($users) < 1) {
            $user = [];
            $user['username'] = $credentials['username'];
            $user = $this->commonGroundService->createResource($user, ['component' => 'uc', 'type' => 'users']);
            $users = $this->commonGroundService->getResourceList(['component'=>'uc', 'type'=>'users'], ['username'=> $credentials['username']], true)['hydra:member'];
        }

        $user = $users[0];

        if (!in_array('ROLE_USER', $user['roles'])) {
            $user['roles'][] = 'ROLE_USER';
        }

        return new CommongroundUser($user['username'], null, null, $user['roles'], null, null, 'user');

    }

    public function checkCredentials($credentials, UserInterface $user)
    {

        // Aan de hand van BSN persoon ophalen uit haal centraal
        $user = $this->commonGroundService->getResourceList(['component'=>'brp', 'type'=>'ingeschrevenpersonen'], ['burgerservicenummer'=> $credentials['bsn']], true)['hydra:member'];

        if (!$user || count($user) < 1) {
            return;
        }

        // no adtional credential check is needed in this case so return true to cause authentication success
        return true;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $backUrl = $request->request->get('back_url');
        $bsn = $request->request->get('bsn');
        $kvk = $request->request->get('kvk');
        $users = $this->commonGroundService->getResourceList(['component'=>'brp', 'type'=>'ingeschrevenpersonen'], ['burgerservicenummer'=> $bsn], true)['hydra:member'];
        $user = $users[0];

        $client = new Client([
            // Base URI is used with relative requests
            'base_uri' => 'https://api.kvk.nl',
            // You can set any number of default request options.
            'timeout'  => 2.0,
        ]);

        $response = $client->request('GET', '/api/v2/testsearch/companies?q=test&mainBranch=true&branch=false&branchNumber='.$kvk);
        $companies = json_decode($response->getBody()->getContents(), true);
        $company = $companies['data']['items'][0];

        $this->session->set('user', $user);
        $this->session->set('organization', $company);

        return new RedirectResponse($backUrl);
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
