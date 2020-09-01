<?php

// src/Service/HuwelijkService.php

namespace Conduction\CommonGroundBundle\Service;

class CamundaService
{
    private $commonGroundService;

    public function __construct(CommonGroundService $commonGroundService)
    {
        $this->commonGroundService = $commonGroundService;
    }

    /*
     * Get a single resource from a common ground componant
     *
     * @param array $request The request being send to camunda
     * @param array The camunda proces started
     */
    public function proccesFromRequest(?array $request)
    {
        // Getting the request type
        $requestType = $this->commonGroundService->getResource($request['requestType']);

        // Declare on behalve on authentication
        $services = [
            'vtc'=> ['jwt'=>'Bearer '.$this->commonGroundService->getJwtToken('vtc')],
            'vrc'=> ['jwt'=>'Bearer '.$this->commonGroundService->getJwtToken('vrc')],
        ];

        // Build eigenschapen
        $eigenschappen = [];
        foreach ($request['properties'] as $key => $value) {
            $eigenschappen[] = ['naam'=>$key, 'waarde'=>$value];
        }

        $variables = [
            'services'       => ['type'=>'json', 'value'=> json_encode($services)],
            'eigenschappen'  => ['type'=>'json', 'value'=> json_encode($eigenschappen)],
            'zaaktype'       => ['type'=>'String', 'value'=> $requestType['caseType']],
            'organisatieRSIN'=> ['type'=>'String', 'value'=> $request['organization']],
        ];

        // Build the post
        $post = ['withVariablesInReturn'=>true, 'variables'=>$variables];

        return $this->commonGroundService->createResource($post, ['component'=>'be', 'type'=>'/process-definition/key/'.$requestType['camundaProces'].'/submit-form']);
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
     * Gets a form for a given task CAMUNDA
     *
     * @param string $taskId The task uuid
     * @return string The xhtml form
     */
    public function getTaskForm(?string $taskId)
    {
        return $this->commonGroundService->getResource(['component'=>'be', 'type'=>'task', 'id'=> $taskId.'/rendered-form', 'accept'=>'application/xhtml+xml']);
    }
}
