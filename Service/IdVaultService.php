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

    // createContract

}
