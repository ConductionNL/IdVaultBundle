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
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class CommongroundDigispoofAuthenticator extends AbstractGuardAuthenticator
{
    private $em;
    private $params;
    private $commonGroundService;
    private $csrfTokenManager;
    private $router;
    private $urlGenerator;

    public function __construct(EntityManagerInterface $em, ParameterBagInterface $params, CommonGroundService $commonGroundService, CsrfTokenManagerInterface $csrfTokenManager, RouterInterface $router, UrlGeneratorInterface $urlGenerator)
    {
        $this->em = $em;
        $this->params = $params;
        $this->commonGroundService = $commonGroundService;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->router = $router;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning false will cause this authenticator
     * to be skipped.
     */
    public function supports(Request $request)
    {
        return 'app_user_digispoof' === $request->attributes->get('_route')
        && $request->isMethod('POST');
    }

    /**
     * Called on every request. Return whatever credentials you want to
     * be passed to getUser() as $credentials.
     */
    public function getCredentials(Request $request)
    {
        $credentials = [
            'bsn'   => $request->request->get('bsn'),

        ];

        $request->getSession()->set(
            Security::LAST_BSN,
            $credentials['bsn']
        );

        return $credentials;
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        
        // Aan de hand van BSN persoon ophalen uit haal centraal
        $users = $this->commonGroundService->getResourceList(['component'=>'brp', 'type'=>'ingeschrevenpersonen'], ['burgerservicenummer'=> $credentials['bsn']], true);

        if (!$users || count($users) < 1) {
            return;
        }

        $user = $users[0];

        if (!in_array('ROLE_USER', $user['roles'])) {
            $user['roles'][] = 'ROLE_USER';
        }

        return new CommongroundUser($user['username'], $user['id'], null, $user['roles'], $user['person'], $user['organization'], 'person');
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        $user = $this->commonGroundService->getResourceList(['component'=>'brp', 'type'=>'ingeschrevenpersonen'], ['burgerservicenummer'=> $credentials['bsn']], true);

        if (!$user) {
            return false;
        }

        // no adtional credential check is needed in this case so return true to cause authentication success
        return true;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        if ($this->params->get('app_subpath') && $this->params->get('app_subpath') != 'false') {
            return new RedirectResponse('/'.$this->params->get('app_subpath').$this->router->generate('app_wrc_templates', []));
        }

        return new RedirectResponse($this->router->generate('app_wrc_templates', [], UrlGeneratorInterface::RELATIVE_PATH));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        if ($this->params->get('app_subpath') && $this->params->get('app_subpath') != 'false') {
            return new RedirectResponse('/'.$this->params->get('app_subpath').$this->router->generate('app_user_digispoof', []));
        }

        return new RedirectResponse($this->router->generate('app_user_digispoof', [], UrlGeneratorInterface::RELATIVE_PATH));
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
