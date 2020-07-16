<?php

// Conduction/CommonGroundBundle/Service/VrcService.php

namespace Conduction\CommonGroundBundle\Service;

/*
 * The VRC Service handels logic reqoured to properly connect with the vrc component
 *
 */
class VrcService
{
    private $commonGroundService;
    private $camundaService;

    public function __construct(CommonGroundService $commonGroundService, CamundaService $camundaService)
    {
        $this->commonGroundService = $commonGroundService;
        $this->camundaService = $camundaService;
    }

    /*
     * Validates a resource with optional commonground and component specific logic
     *
     * @param array $resource The resource before enrichment
     * @param array The resource afther enrichment
     */
    public function onSave(?array $resource)
    {
        // Lets get the request type
        if (array_key_exists('requestType', $resource)) {
            $requestType = $this->commonGroundService->getResource($resource['requestType']);
        }

        // Lets get the process type
        if (array_key_exists('processType', $resource)) {
            $processType = $this->commonGroundService->getResource($resource['processType']);
        }

        // We need to loop trough the properties, and see if items need to be created
        if (array_key_exists('properties', $resource)) {
            foreach ($resource['properties'] as $property) {
            }
        }

        // Lets see if we need to create assents for the submitters
        if (array_key_exists('submitters', $resource)) {
            foreach ($resource['submitters'] as $submitter) {
            }
        }

        return $resource;
    }

    /*
     * Aditional logic triggerd afther a Request has been newly created
     *
     * @param array $resource The resource before enrichment
     * @param array The resource afther enrichment
     */
    public function onCreated(?array $resource)
    {
        if (!$requestType = $this->commonGroundService->getResource($resource['requestType'])) {
            return;
        }

        // If the request has Zaak properties we need to trigger those
        if (array_key_exists('caseType', $requestType) && !array_key_exists('cases', $resource)) {
            /* @todo create a case */
        }

        // Let run al the tasks
        if (array_key_exists('tasks', $requestType)) {
            // Loop trough the tasks atached to this resource and add them to the stack
            foreach ($requestType['tasks'] as $trigger) {
                if (!$trigger['event'] || $trigger['event'] == 'create') {
                    // Lets preparte the task for the que
                    unset($trigger['id']);
                    unset($trigger['@id']);
                    unset($trigger['@type']);
                    unset($trigger['dateCreated']);
                    unset($trigger['dateModified']);
                    unset($trigger['requestBody']);

                    // Lets hook the task to the propper resource
                    $trigger['resource'] = $resource['@id'];
                    $trigger['type'] = strtoupper($trigger['type']);

                    // Lets set the time to trigger
                    $dateToTrigger = new \DateTime();
                    $dateToTrigger->add(new \DateInterval($trigger['timeInterval']));
                    $trigger['dateToTrigger'] = $dateToTrigger->format('Y-m-d H:i:s');

                    // Lets add the task to the que
                    $trigger = $this->commonGroundService->createResource($trigger, ['component'=>'qc', 'type'=>'tasks']);
                }
            }
        }

        return $resource;
    }

    /*
     * Aditional logic triggerd afther a Request has been newly created
     *
     * @param array $resource The resource before enrichment
     * @return array The resource afther enrichment
     */
    public function onUpdated(?array $resource)
    {
        // Lets first see if we can grap an requested type
        if (!$requestType = $this->commonGroundService->getResource($resource['requestType'])) {
            return;
        }

        // Let run al the tasks
        if (array_key_exists('tasks', $requestType)) {
            // Loop trough the tasks atached to this resource and add them to the stack
            foreach ($requestType['tasks'] as $trigger) {
                if (!$trigger['event'] || $trigger['event'] == 'update') {
                    // Lets preparte the task for the que
                    unset($trigger['id']);
                    unset($trigger['@id']);
                    unset($trigger['@type']);
                    unset($trigger['dateCreated']);
                    unset($trigger['dateModified']);
                    unset($trigger['requestBody']);

                    // Lets hook the task to the propper resource
                    $trigger['resource'] = $resource['@id'];
                    $trigger['type'] = strtoupper($trigger['type']);

                    // Lets set the time to trigger
                    $dateToTrigger = new \DateTime();
                    $dateToTrigger->add(new \DateInterval($trigger['timeInterval']));
                    $trigger['dateToTrigger'] = $dateToTrigger->format('Y-m-d H:i:s');

                    // Lets add the task to the que
                    $trigger = $this->commonGroundService->createResource($trigger, ['component'=>'qc', 'type'=>'tasks']);
                }
            }
        }

        return $resource;
    }

    /*
     * Start a Cammunda procces from e resource
     *
     * @param array $resource The resource before enrichment
     * @param string $proccess The uuid of the proccess to start
     * @param string $caseType The uuid of the casetype to start
     * @return array The resource afther enrichment
     */
    public function startProcess(?array $resource, ?string $proccess, ?string $caseType)
    {
        // Lets first see if we can grap an requested type
        if (!$requestType = $this->commonGroundService->getResource($resource['requestType'])) {
            return false;
        }

        $properties = [];

        // Lets make sure that we have a procceses array
        if (!array_key_exists('processes', $resource)) {
            $resource['processes'] = [];
        }
        // Declare on behalve on authentication
        $services = [
            'ztc'=> ['jwt'=>'Bearer '.$this->commonGroundService->getJwtToken('ztc')],
            'zrc'=> ['jwt'=>'Bearer '.$this->commonGroundService->getJwtToken('zrc')],
        ];

        $formvariables = $this->commonGroundService->getResource(['component'=>'be', 'type'=>'process-definition/key/'.$proccess.'/form-variables']);

        // Transfer the  default properties
        foreach ($formvariables as $key => $value) {
            // $properties[] = ['naam'=> $key,'waarde'=>$value['value']];
        }

        // hacky tacky
        unset($resource['properties']['gegevens']);
        unset($resource['properties']['naam']);
        unset($resource['properties']['partners']);
        unset($resource['properties']['organisatieRSIN']);
        unset($resource['properties']['zaaktype']);

        foreach ($resource['properties'] as $key => $value) {
            $properties[] = ['naam'=> $key, 'waarde'=> $value];
        }

        $variables = [
            'services'       => ['type'=>'json', 'value'=> json_encode($services)],
            'eigenschappen'  => ['type'=>'json', 'value'=> json_encode($properties)],
            'zaaktype'       => ['type'=>'String', 'value'=> 'https://openzaak.utrechtproeftuin.nl/catalogi/api/v1/zaaktypen/'.$caseType],
            'organisatieRSIN'=> ['type'=>'String', 'value'=> $this->commonGroundService->getResource($resource['organization'])['rsin']],
        ];

        // Build the post
        $post = ['withVariablesInReturn'=>true, 'variables'=>$variables];

        $procces = $this->commonGroundService->createResource($post, ['component'=>'be', 'type'=>'process-definition/key/'.$requestType['camundaProces'].'/submit-form']);
        $resource['processes'][] = $procces['links'][0]['href'];

        /* @todo dit is  natuurlijk but lellijk en moet eigenlijk worden upgepakt in een onCreate hook */
        unset($resource['submitters']);
        unset($resource['children']);
        unset($resource['parent']);

        $resource = $this->commonGroundService->saveResource($resource, ['component'=>'vrc', 'type'=>'requests']);

        return $resource;
    }

    /*
     * Get Camunda tasks for a given request
     *
     * @param array $resource The resource before enrichment
     * @return array The resource afther enrichment
     */
    public function getTasks(?array $resource)
    {
        $tasks = [];

        // Lets see if we have procceses tied to this request
        if (!array_key_exists('processes', $resource) || !is_array($resource['processes'])) {
            return $tasks;
        }

        // Lets get the tasks for each procces atached to this request
        foreach ($resource['processes'] as $process) {
            //$processTasks = $this->commonGroundService->getResourceList(['component'=>'be','type'=>'task'],['processInstanceId'=> $this->commonGroundService->getUuidFromUrl($process)]);
            $processTasks = $this->commonGroundService->getResourceList(['component'=>'be', 'type'=>'task'], ['processInstanceId'=> '0a3d56dd-9345-11ea-ae32-0e13a3f6559d']);
            // Lets get the form elements
            foreach ($processTasks as $key=>$value) {
                $processTasks[$key]['form'] = $this->getTaskForm($value['id']);
            }
            $tasks = array_merge($tasks, $processTasks);
        }

        return $tasks;
    }

    /*
     * Gets a form for a given task
     *
     * @param string $taskId The task uuid
     * @return string The xhtml form
     */
    public function getTaskForm(?string $taskId)
    {
        return $this->commonGroundService->getResource(['component'=>'be', 'type'=>'task', 'id'=> $taskId.'/rendered-form', 'accept'=>'application/xhtml+xml']);
    }

    /*
     * Gets a requestType from a request and validates stage completion
     *
     * @param array $request The request before stage completion checks
     * @return array The resourceType afther stage completion checks
     */
    public function checkProperties(?array $request)
    {
        foreach ($request['properties'] as $property) {
        }

        return $request;
    }

    /*
     * Checks the property of a request against the requestType to determine if it is valid
     *
     * @param array $request The request to wichs the property belongs
     * @param string $property The key of the property to checks
     * @return boolean Whether or not a property is valid to its requestTypes
     */
    public function checkProperty(?array $request, $propertys)
    {
        // Lets first see if we can grap an requested type and if it has stages
        if (!$requestType = $this->commonGroundService->getResource($resource['requestType']) || !array_key_exists('stages', $requestType)) {
            return;
        }

        return $requestType;
    }
}
