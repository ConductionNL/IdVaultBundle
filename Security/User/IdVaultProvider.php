<?php

namespace Conduction\IdVaultBundle\Security\User;

use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Conduction\IdVaultBundle\Service\IdVaultService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class IdVaultProvider implements UserProviderInterface
{
    private $params;
    private $commonGroundService;
    private $session;
    private $idVaultService;

    public function __construct(ParameterBagInterface $params, CommonGroundService $commonGroundService, SessionInterface $session, IdVaultService $idVaultService)
    {
        $this->params = $params;
        $this->commonGroundService = $commonGroundService;
        $this->session = $session;
        $this->idVaultService = $idVaultService;
    }

    public function loadUserByUsername($username)
    {
        return $this->fetchUser($username);
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof IdVaultUser) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        $username = $user->getUsername();
        $password = $user->getPassword();
        $organization = $user->getOrganization();
        $type = $user->getType();
        $person = $user->getPerson();
        $authorization = $user->getAuthorization();
        $groups = $user->getGroups();
        $organizations = $user->getOrganizations();

        return $this->fetchUser($username, $password, $organization, $type, $person, $authorization, $groups, $organizations);
    }

    public function supportsClass($class)
    {
        return IdVaultUser::class === $class;
    }

    private function fetchUser($username, $password, $organization, $type, $person, $authorization, $groups, $organizations)
    {
        //only trigger if type of user is organization
        $application = $this->commonGroundService->cleanUrl(['component'=>'wrc', 'type'=>'applications', 'id'=>$this->params->get('app_id')]);

        $provider = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'providers'], ['type' => 'id-vault', 'application' => $this->params->get('app_id')])['hydra:member'];
        $tokens = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'tokens'], ['token' => $password, 'provider.name' => $provider[0]['name']])['hydra:member'];

        //get groups from id-vault again
        $groups = $this->idVaultService->getUserGroups($provider[0]['configuration']['app_id'], $username)['groups'];

        $userUlr = $this->commonGroundService->cleanUrl(['component'=>'uc', 'type'=>'users', 'id'=>$tokens[0]['user']['id']]);
        $user = $this->commonGroundService->getResource($userUlr);

        if (!isset($user['roles'])) {
            $user['roles'] = [];
        }

        if (!in_array('ROLE_USER', $user['roles'])) {
            $user['roles'][] = 'ROLE_USER';
        }

        foreach ($user['roles'] as $key=>$role) {
            if (strpos($role, 'ROLE_') !== 0) {
                $user['roles'][$key] = "ROLE_$role";
            }
        }


        $person = $this->commonGroundService->getResource($user['person']);
        if (isset($user['organization'])) {
            return new IdVaultUser($user['username'], $password, $person['name'], null, $user['roles'], $user['person'], $user['organization'], 'id-vault', false, $authorization, null, $groups, $organizations);
        } else {
            return new IdVaultUser($user['username'], $password, $person['name'], null, $user['roles'], $user['person'], null, 'id-vault', false, $authorization, null, $groups, $organizations);
        }

    }

}
