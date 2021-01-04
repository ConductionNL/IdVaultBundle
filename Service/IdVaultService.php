<?php

// Conduction/CommonGroundBundle/Service/IdVaultService.php
namespace Conduction\IdVaultBundle\Service;

use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Conduction\IdVaultApi\src\IdVaultApiClient;
use GuzzleHttp\Client;
use Symfony\Component\Config\Definition\Exception\Exception;
use Throwable;

class IdVaultService
{

    private $idVault;
    private $commonGroundService;

    public function __construct(CommonGroundService $commonGroundService) {
        $this->idVault = new IdVaultApiClient();
        $this->commonGroundService = $commonGroundService;
    }

    /**
     * This function add a dossier to an id-vault user.
     *
     * @param array $scopes scopes the dossier is blocking (scopes must be authorized by the user).
     * @param string $accessToken accessToken received from id-vault.
     * @param string $name name of the dossier.
     * @param string $goal the goal of the Dossier.
     * @param string $expiryDate Expiry date of the Dossier (example: "27-10-2020 12:00:00").
     * @param string $sso valid URL with which the user can view this Dossier.
     * @param string $description (optional) description of the dossier.
     * @param bool $legal (default = false) whether or not this Dossier is on legal basis.
     *
     * @return array|string response from id-vault if dossier created was successful, error message otherwise.
     */
    public function createDossier(array $scopes, string $accessToken, string $name, string $goal, string $expiryDate, string $sso, string $description = '', bool $legal = false)
    {
        $result = $this->idVault->createDossier($scopes, $accessToken, $name, $goal, $expiryDate, $sso, $description, $legal);

        return $result;
    }

    /**
     * This function sets the organization on the provided user object
     *
     * @param string $organization id of the wrc organization object
     * @param string $username username of the user
     *
     * @return array|false the updated user object or false when failed to update
     */
    public function updateUserOrganization(string $organization, string $username)
    {
        $organization = $this->commonGroundService->cleanUrl(['component' => 'wrc', 'type' => 'organizations', 'id' => $organization]);

        $users = $this->commonGroundService->getResourceList(['component' => 'uc', 'type' => 'users'], ['username' => $username])['hydra:member'];

        if (count($users) > 0) {
            $users[0]['organization'] = $organization;

            foreach ($users[0]['userGroups'] as &$group) {
                $group = '/groups/'.$group['id'];
            }

            $result = $this->commonGroundService->updateResource($users[0]);

        } else {
            return false;
        }

        return $result;
    }

    /**
     * This function sends mail from id-vault to provided receiver
     *
     * @param string $applicationId id of your id-vault application.
     * @param string $body html body of the mail.
     * @param string $subject subject of the mail.
     * @param string $receiver receiver of the mail.
     * @param string $sender sender of the mail.
     *
     * @return array|false returns response from id-vault or false if wrong information provided for the call
     */
    public function sendMail(string $applicationId, string $body, string $subject, string $receiver, string $sender)
    {
        $result = $this->idVault->sendMail($applicationId, $body, $subject, $receiver, $sender);

        return $result;
    }

    /**
     * this function requests additional scopes from user that they must authorize.
     *
     * @param array $scopes scopes you wish to request from the user.
     * @param string $accessToken accessToken received from id-vault.
     *
     * @return array|Throwable returns response from id-vault
     */
    public function getScopes(array $scopes, string $accessToken)
    {
        $result = $this->idVault->getScopes($scopes, $accessToken);

        return $result;
    }

    /**
     * This function retrieve's user information from id-vault.
     *
     * @param string $code the code received by id-vault oauth endpoint.
     * @param string $applicationId id of your id-vault application.
     * @param string $secret secret of your id-vault application.
     * @param string $state (optional) A random string used by your application to identify a unique session
     *
     * @return array|false returns response from id-vault or false
     */
    public function authenticateUser(string $code, string $applicationId, string $secret, string $state = '')
    {
        $result = $this->idVault->authenticateUser($code, $applicationId, $secret, $state);

        return $result;
    }

}
