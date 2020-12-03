<?php

// Conduction/CommonGroundBundle/Service/IdVaultService.php
namespace Conduction\IdVaultBundle\Service;

use Conduction\CommonGroundBundle\Service\CommonGroundService;
use GuzzleHttp\Client;
use Symfony\Component\Config\Definition\Exception\Exception;

class IdVaultService
{

    /**
     * @var CommonGroundService
     */
    private $commonGroundService;

    public function __construct(
        CommonGroundService $commonGroundService
    ) {
        $this->commonGroundService = $commonGroundService;
    }

    // getScope

    // getClaim

    /**
     * This function returns an authorization id
     *
     * @param string $userUrl cleanUrl of the user
     *
     * @return string authorization id
     */
    public function getAuthorization(string $userUrl, string $resource, string $name, ?string $description)
    {

    }

    /**
     * This function add a dossier to a current user.
     *
     * @param array $scopes scopes the dossier is blocking.
     * @param string $authorization authorization id.
     *
     * @return boolean true if the dossier created was successful, false otherwise.
     */
    public function createDossier(array $scopes, string $authorization, string $name, ?string $description)
    {
        try {
            $headers = [
                'authentication' => 'Bearer test_H8PeFq62HpNFPQmer4GuEUWupMwSqQ',
                'Accept'        => 'application/json',
            ];

            $client = new Client([
                // Base URI is used with relative requests
                'base_uri' => 'https://api.mollie.com',
                // You can set any number of default request options.
                'timeout'  => 2.0,
            ]);

            $response = $client->request('GET', '/v2/payments/'.$id, [
                'headers' => $headers,
            ]);

        } catch (\Throwable $e) {
            return false;
        }
        return true;
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
        try {
            $headers = [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json'
            ];

            $body = [
                'applicationId' => $applicationId,
                'body'          => $body,
                'subject'       => $subject,
                'receiver'      => $receiver,
                'sender'        => $sender,
            ];

            $client = new Client([
                // Base URI is used with relative requests
                'headers'  => $headers,
                'base_uri' => 'https://id-vault.com',
                // You can set any number of default request options.
                'timeout'  => 2.0,
            ]);

            $response = $client->request('POST', '/api/mails', [
                'json'         => $body,
            ]);

            $response = json_decode($response->getBody()->getContents(), true);

        } catch (\Throwable $e) {
            return false;
        }
        return $response;
    }

    // createContract

}
