<?php

// Conduction/CommonGroundBundle/Service/IdVaultService.php
namespace Conduction\IdVaultBundle\Service;

use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Conduction\IdVaultApi\src\IdVaultApiClient;
use GuzzleHttp\Client;
use Symfony\Component\Config\Definition\Exception\Exception;
use Throwable;
use Twig\Environment;

class IdVaultService
{

    private $idVault;
    private $commonGroundService;
    private $twig;

    public function __construct(CommonGroundService $commonGroundService, Environment $twig) {
        $this->idVault = new IdVaultApiClient();
        $this->commonGroundService = $commonGroundService;
        $this->twig = $twig;
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
     * @param string $template path to template in templates map.
     * @param string $subject subject of the mail.
     * @param string $receiver receiver of the mail.
     * @param string $sender sender of the mail.
     * @param array $data (optional) array used to render the template with twig.
     *
     * @return array|false returns response from id-vault or false if wrong information provided for the call
     */
    public function sendMail(string $applicationId, string $template, string $subject, string $receiver, string $sender, array $data = [])
    {
        $body = $this->twig->render($template, $data);
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
     * this function requests sendLists from id-vault BS, the resource filter only works when a clientSecret is also given.
     *
     * @param string $clientSecret An id of an id-vault wac/application. This is used to get the sendLists of a specific application.
     * @param string $resource An url of a resource that a sendList can also be connected to. For example an organization within the application this SendList is connected to. This is why this search filter only works if a clientSecret is also given.
     *
     * @return array|Throwable returns response from id-vault with an array of SendLists.
     */
    public function getSendLists(string $clientSecret = null, string $resource = null)
    {
        $result = $this->idVault->getSendLists($clientSecret, $resource);

        return $result;
    }

    /**
     * this function creates a new sendList in id-vault BS.
     *
     * @param string $clientSecret An id of an id-vault wac/application. This should be the UC/provider->configuration->secret of the application from where this sendList is saved.
     * @param array $sendList An array with information of the sendList. This should contain at least the key 'name' with a value when creating a new Sendlist. Or at least the key 'id' with the id of an id-vault sendList when updating a sendList. It can also contain the following keys: 'description', 'resource', (bool) 'mail', (bool) 'phone' and (array with wac/group id's) 'groups'.
     *
     * @return array|Throwable returns response from id-vault with the created or updated BS/sendList (and BS/subscriber @id's).
     */
    public function saveSendList(string $clientSecret, array $sendList)
    {
        $result = $this->idVault->saveSendList($clientSecret, $sendList);

        return $result;
    }

    /**
     * this function deletes a sendList in id-vault BS and also removes any connections to this sendList in all subscribers.
     *
     * @param string $sendListId The id of an id-vault sendList that is going to be deleted.
     *
     * @return array|Throwable returns response from id-vault with all the affected id-vault BS subscribers and true if this sendList was correctly deleted.
     */
    public function deleteSendList(string $sendListId)
    {
        $result = $this->idVault->deleteSendList($sendListId);

        return $result;
    }

    /**
     * this function creates subscribers in id-vault BS, connecting email addresses and/or id-vault wac/groups to the given sendList. Note that at least one of the arrays emails or groups needs to be set!
     *
     * @param string $sendListId The id of an id-vault sendList that all email addresses will subscribe to.
     * @param array $emails An array with email addresses that will be subscribed to the given sendList (id).
     * @param array $groups An array with id-vault wac/group id's that will be subscribed to the given sendList (id).
     *
     * @return array|Throwable returns response from id-vault with all the affected id-vault BS subscribers. Will return false if emails and groups are both empty.
     */
    public function addSubscribersToSendList(string $sendListId, array $emails = null, array $groups = null)
    {
        $result = $this->idVault->addSubscribersToSendList($sendListId, $emails, $groups);

        return $result;
    }

    /**
     * this function sends emails to all subscribers of an id-vault BS sendList
     *
     * @param string $sendListId The id of an id-vault sendList.
     * @param array $mail An array with information for the email. This should contain at least the keys title (email title), html (email content) and sender (an email address) and can also contain the following keys: message, text.
     *
     * @return array|Throwable returns response from id-vault with an array of @id's of all send emails.
     */
    public function sendToSendList(string $sendListId, array $mail)
    {
        $result = $this->idVault->sendToSendList($sendListId, $mail);

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

    /**
     * this function creates a userGroup linked to the id-vault application
     *
     * @param string $clientId id of the id-vault application.
     * @param string $name name of the group.
     * @param string $description description of the group.
     * @param string $organization (optional) uri of an organization object.
     *
     * @return array|Throwable returns response from id-vault
     */
    public function createGroup(string $clientId, string $name, string $description, string $organization = '')
    {
        $result = $this->idVault->createGroup($clientId, $name, $description, $organization);

        return $result;
    }

    /**
     * this function deletes a userGroup linked to the id-vault application
     *
     * @param string $clientId id of the id-vault application.
     * @param string $organization (optional) uri of an organization object.
     * @param string $groupId id of the id-vault group.
     *
     * @return array|Throwable returns response from id-vault
     */
    public function deleteGroup(string $clientId, string $organization = '', string $groupId = null) {
        $result = $this->idVault->deleteGroup($clientId, $organization, $groupId);

        return $result;
    }

    /**
     * this function get all the groups that are linked to a user in the application
     *
     * @param string $clientId id of the id-vault application.
     * @param string $username email of the user you want to get the groups from.
     *
     * @return array|Throwable returns response from id-vault
     */
    public function getUserGroups(string $clientId, string $username)
    {
        $result = $this->idVault->getUserGroups($clientId, $username);

        return $result;
    }

    /**
     * this function get all the groups and the users in those groups that are linked to an application
     *
     * @param string $clientId id of the id-vault application.
     * @param string $organization uri of the organization linked to the groups
     *
     * @return array|Throwable returns response from id-vault
     */
    public function getGroups(string $clientId, string $organization)
    {
        $result = $this->idVault->getGroups($clientId, $organization);

        return $result;
    }

    /**
     * this function invites a id-vault user to the provided group
     *
     * @param string $clientId id of the id-vault application.
     * @param string $groupId id of the id-vault group.
     * @param string $username username of the user you wish to invite.
     * @param bool $accepted whether the user already accepted the invited (default = false).
     *
     * @return array|Throwable returns response from id-vault
     */
    public function inviteUser(string $clientId, string $groupId, string $username, bool $accepted = false)
    {
        $result = $this->idVault->inviteUser($clientId, $groupId, $username, $accepted);

        return $result;
    }

    /**
     * this function removes a membership of an id-vault user to the provided group, if it exists.
     *
     * @param string $username username of the user.
     * @param string $clientId id of the id-vault application.
     * @param string $groupId id of the id-vault group.
     *
     * @return array|Throwable returns response from id-vault
     */
    public function removeUser(string $username, string $clientId, string $groupId)
    {
        $result = $this->idVault->removeUser($username, $clientId, $groupId);

        return $result;
    }

    /**
     * this function accepts the group invite for the user.
     *
     * @param string $clientId id of the id-vault application.
     * @param string $groupId id of the id-vault group.
     * @param string $username username of the user that wants to accept his invite
     *
     * @return array|Throwable returns response from id-vault
     */
    public function acceptGroupInvite(string $clientId, string $groupId, string $username)
    {
        $result = $this->idVault->acceptGroupInvite($clientId, $groupId, $username);

        return $result;
    }

    /**
     * this function tries to create an id-vault user and return an authorization code.
     *
     * @param string $clientId id of the id-vault application.
     * @param string $username username of the user that wants to accept his invite
     * @param array $scopes scopes requested from the user.
     *
     * @return array|Throwable returns response from id-vault
     */
    public function createUser(string $clientId, string $username, array $scopes)
    {
        $result = $this->idVault->createUser($clientId, $username, $scopes);

        return $result;
    }
}
