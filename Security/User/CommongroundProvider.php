<?php

// src/Security/User/CommongroundUserProvider.php

namespace Conduction\CommonGroundBundle\Security\User;

use Conduction\CommonGroundBundle\Service\CommonGroundService;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class CommongroundProvider implements UserProviderInterface
{
    private $params;
    private $commonGroundService;
    private $session;

    public function __construct(ParameterBagInterface $params, CommonGroundService $commonGroundService, SessionInterface $session)
    {
        $this->params = $params;
        $this->commonGroundService = $commonGroundService;
        $this->session = $session;
    }

    public function loadUserByUsername($username)
    {
        return $this->fetchUser($username);
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof CommongroundUser) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        $username = $user->getUsername();
        $password = $user->getPassword();
        $organization = $user->getOrganization();
        $type = $user->getType();
        $person = $user->getPerson();

        return $this->fetchUser($username, $password, $organization, $type, $person);
    }

    public function supportsClass($class)
    {
        return CommongroundUser::class === $class;
    }

    private function fetchUser($username, $password, $organization, $type, $person)
    {
        //only trigger if type of user is organization
        if ($type == 'organization') {
            $client = new Client([
                // Base URI is used with relative requests
                'base_uri' => 'https://api.kvk.nl',
                // You can set any number of default request options.
                'timeout'  => 2.0,
            ]);
            $response = $client->request('GET', '/api/v2/testsearch/companies?q=test&mainBranch=true&branch=false&branchNumber='.$organization);
            $companies = json_decode($response->getBody()->getContents(), true);
            if (!$companies || count($companies) < 1) {
                return;
            }
            $kvk = $companies['data']['items'][0];
            $user = $this->commonGroundService->getResource($person);
            if (!isset($user['roles'])) {
                $user['roles'] = [];
            }
            array_push($user['roles'], 'scope.vrc.requests.read');
            array_push($user['roles'], 'scope.orc.orders.read');
            array_push($user['roles'], 'scope.cmc.messages.read');
            array_push($user['roles'], 'scope.bc.invoices.read');
            array_push($user['roles'], 'scope.arc.events.read');
            array_push($user['roles'], 'scope.irc.assents.read');
        } elseif ($type == 'person') {
            $user = $this->commonGroundService->getResource($person);
            if (!isset($user['roles'])) {
                $user['roles'] = [];
            }
            array_push($user['roles'], 'scope.vrc.requests.read');
            array_push($user['roles'], 'scope.orc.orders.read');
            array_push($user['roles'], 'scope.cmc.messages.read');
            array_push($user['roles'], 'scope.bc.invoices.read');
            array_push($user['roles'], 'scope.arc.events.read');
            array_push($user['roles'], 'scope.irc.assents.read');
        } elseif ($type == 'user') {
            $users = $this->commonGroundService->getResourceList(['component'=>'uc', 'type'=>'users'], ['username'=> $username], true);
            $users = $users['hydra:member'];
            $user = $users[0];
        } elseif ($type == 'idin') {
            $provider = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'providers'], ['name' => 'idin'])['hydra:member'];
            $tokens = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'tokens'], ['token' => $username, 'provider.name' => $provider[0]['name']])['hydra:member'];
            // Deze $urls zijn een hotfix voor niet werkende @id's op de cgb cgs
            $userUlr = $this->commonGroundService->cleanUrl(['component'=>'uc', 'type'=>'users', 'id'=>$tokens[0]['user']['id']]);
            $user = $this->commonGroundService->getResource($userUlr);
            if (!isset($user['roles'])) {
                $user['roles'] = [];
            }
            array_push($user['roles'], 'scope.chin.checkins.read');
        } elseif ($type == 'facebook') {
            $provider = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'providers'], ['name' => 'facebook'])['hydra:member'];
            $tokens = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'tokens'], ['token' => $password, 'provider.name' => $provider[0]['name']])['hydra:member'];
            // Deze $urls zijn een hotfix voor niet werkende @id's op de cgb cgs
            $userUlr = $this->commonGroundService->cleanUrl(['component'=>'uc', 'type'=>'users', 'id'=>$tokens[0]['user']['id']]);
            $user = $this->commonGroundService->getResource($userUlr);
            if (!isset($user['roles'])) {
                $user['roles'] = [];
            }
            array_push($user['roles'], 'scope.chin.checkins.read');
        } elseif ($type == 'gmail') {
            $provider = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'providers'], ['name' => 'gmail'])['hydra:member'];
            $tokens = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'tokens'], ['token' => $password, 'provider.name' => $provider[0]['name']])['hydra:member'];
            // Deze $urls zijn een hotfix voor niet werkende @id's op de cgb cgs
            $userUlr = $this->commonGroundService->cleanUrl(['component'=>'uc', 'type'=>'users', 'id'=>$tokens[0]['user']['id']]);
            $user = $this->commonGroundService->getResource($userUlr);
            if (!isset($user['roles'])) {
                $user['roles'] = [];
            }
            array_push($user['roles'], 'scope.chin.checkins.read');
        }

        if (!isset($user['roles'])) {
            $user['roles'] = [];
        }

        if (!in_array('ROLE_USER', $user['roles'])) {
            $user['roles'][] = 'ROLE_USER';
        }

        //We create a CommongroundUser based on user type.
        switch ($type) {
            case 'person':
                $resident = $this->checkResidence('person', $user, null);

                return new CommongroundUser($user['naam']['voornamen'].' '.$user['naam']['geslachtsnaam'], $user['id'], $user['naam']['voornamen'].' '.$user['naam']['geslachtsnaam'], null, $user['roles'], $user['@id'], null, 'person', $resident);
            case 'organization':
                $resident = $this->checkResidence('organization', $user, $kvk);

                return new CommongroundUser($kvk['tradeNames']['businessName'], $user['id'], $kvk['tradeNames']['businessName'], null, $user['roles'], $user['@id'], $kvk['branchNumber'], 'organization', $resident);
            case 'user':
                $person = $this->commonGroundService->getResource($user['person']);

                return new CommongroundUser($user['username'], $user['id'], $person['name'], null, $user['roles'], $user['person'], $user['organization'], 'user');
            case 'idin':
                $person = $this->commonGroundService->getResource($user['person']);

                return new CommongroundUser($user['username'], $user['username'], $person['name'], null, $user['roles'], $user['person'], null, 'idin');
            case 'facebook':
                $person = $this->commonGroundService->getResource($user['person']);

                return new CommongroundUser($user['username'], $password, $person['name'], null, $user['roles'], $user['person'], null, 'facebook');
            case 'gmail':
                $person = $this->commonGroundService->getResource($user['person']);

                return new CommongroundUser($user['username'], $password, $person['name'], null, $user['roles'], $user['person'], null, 'gmail');
            default:
                throw new UsernameNotFoundException(
                    sprintf('User "%s" does not exist.', $username)
                );
        }
    }

    private function checkResidence($type, $user, $organization)
    {
        $application = $this->commonGroundService->getResource(['component' => 'wrc', 'type' => 'applications', 'id' => $this->params->get('app_id')]);
        $resident = false;
        if (isset($application['defaultConfiguration']['configuration']['cityNames'])) {
            foreach ($application['defaultConfiguration']['configuration']['cityNames'] as $name) {
                if ($type == 'person') {
                    if ($user['verblijfplaats']['woonplaatsnaam'] == $name) {
                        $resident = true;
                    }
                } elseif ($type == 'organization') {
                    if ($organization['addresses'][0]['city'] == $name) {
                        $resident = true;
                    }
                } else {
                    $resident = false;
                }
            }

            return $resident;
        } else {
            return false;
        }
    }
}
