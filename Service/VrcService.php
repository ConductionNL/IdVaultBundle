<?php

// Conduction/CommonGroundBundle/Service/VrcService.php

namespace Conduction\CommonGroundBundle\Service;

use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

/*
 * The VRC Service handels logic reqoured to properly connect with the vrc component
 *
 */
class VrcService
{
    private $commonGroundService;
    private $flash;
    private $camundaService;

    public function __construct(CommonGroundService $commonGroundService, FlashBagInterface $flash, CamundaService $camundaService)
    {
        $this->commonGroundService = $commonGroundService;
        $this->flash = $flash;
        $this->camundaService = $camundaService;
    }

    /*
     * Validates a resource with optional commonground and component specific logic
     *
     * @param array $resource The resource before enrichment
     * @param array The resource afther enrichment
     */
    public function onResource(?array $resource)
    {
        return $resource;
    }

    /*
     * Validates a resource with optional commonground and component specific logic
     *
     * @param array $resource The resource before enrichment
     * @param array The resource afther enrichment
     */
    public function onList(?array $resource)
    {
        return $resource;
    }

    /**
     * Creates an assent for a request.
     *
     * @param array      $value
     * @param array      $requestType
     * @param array      $typeProperty
     * @param array      $request
     * @param array|null $processType
     *
     * @return array|bool
     */
    public function createAssent(array $value, array $requestType, array $typeProperty, array $request, array $processType = null)
    {
        $assent = [];
        $contact = [];
        if (key_exists('person', $value)) {
            $contact['givenName'] = $value['person']['givenName'];
            $contact['familyName'] = $value['person']['familyName'];
        }
        if (key_exists('email', $value)) {
            $email['name'] = 'e-mail';
            $email['email'] = $value['email'];
            $contact['emails'][] = $email;
        }
        if (key_exists('telephone', $value)) {
            $phone['name'] = 'phone number';
            $phone['telephone'] = $value['telephone'];
            $contact['telephones'][] = $phone;
        }
        $assent['contact'] = $this->commonGroundService->createResource($contact, ['component'=>'cc', 'type'=>'people'])['@id'];

        if ($processType) {
            $name = $processType['name'];
        } else {
            $name = $requestType['name'];
        }

        $assent['name'] = "Instemmingsverzoek voor $name";
        $assent['description'] = "U hebt een instemmingsverzoek ontvangen als {$typeProperty['name']} voor een $name van X";
        if (key_exists('@id', $request)) {
            $assent['request'] = $request['@id'];
        }
        $assent['requester'] = $request['organization'];

        return $this->commonGroundService->createResource($assent, ['component'=>'irc', 'type'=>'assents']);
    }

    /*
     * Validates a resource with optional commonground and component specific logic
     *
     * @param array $resource The resource before enrichment
     * @param array The resource afther enrichment
     */
    public function onSave(?array $resource)
    {
        if (!key_exists('@id', $resource) || !$this->commonGroundService->isCommonGround($resource['@id'])['type'] == 'requests') {
            return $resource;
        }
        // Lets get the request type
        if (array_key_exists('requestType', $resource)) {
            $requestType = $this->commonGroundService->getResource($resource['requestType']);
        }

        // Lets get the process type
        if (array_key_exists('processType', $resource)) {
            $processType = $this->commonGroundService->getResource($resource['processType']);
        } else {
            $processType = null;
        }
        // Lets see if we need to create assents for the submitters
        if (array_key_exists('submitters', $resource)) {
            foreach ($resource['submitters'] as $submitter) {
            }
        }

        // We need to loop trough the properties, and see if items need to be created
        if (array_key_exists('properties', $resource)) {
            $properties = $resource['properties'];
            $typeProperties = $requestType['properties'];
            foreach ($typeProperties as $typeProperty) {
                if (
                    $typeProperty['iri'] == 'irc/assent' &&
                    key_exists($typeProperty['name'], $properties) &&
                    ($property = $properties[$typeProperty['name']])
                ) {
                    if ($typeProperty['maxItems'] > 1) {
                        foreach ($property as $key => $value) {
                            if (is_array($value)) {
                                $properties[$typeProperty['name']][$key] = $this->createAssent($value, $requestType, $typeProperty, $resource, $processType)['@id'];
                            }
                        }
                    } elseif (is_array($property)) {
                        $properties[$typeProperty['name']] = $this->createAssent($property, $requestType, $typeProperty, $resource, $processType)['@id'];
                    }
                }
            }
            $resource['properties'] = $properties;
        }

        return $resource;
    }

    /*
     * Validates a resource with optional commonground and component specific logic
     *
     * @param array $resource The resource before enrichment
     * @param array The resource afther enrichment
     */
    public function onSaved(?array $resource)
    {

        // Let see if we need to create an order
        $resource = $this->checkOrder($resource);

        return $resource;
    }

    /*
     * Validates a resource with optional commonground and component specific logic
     *
     * @param array $resource The resource before enrichment
     * @param array The resource afther enrichment
     */
    public function onDelete(?array $resource)
    {
        return $resource;
    }

    /*
     * Validates a resource with optional commonground and component specific logic
     *
     * @param array $resource The resource before enrichment
     * @param array The resource afther enrichment
     */
    public function onDeleted(?array $resource)
    {
        return $resource;
    }

    /*
     * Validates a resource with optional commonground and component specific logic
     *
     * @param array $resource The resource before enrichment
     * @param array The resource afther enrichment
     */
    public function onUpdate(?array $resource)
    {
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
        // Run the request through the very small business engine
        if ($this->commonGroundService->getComponentHealth('vsbe')) {
            $vsbeResource = [];
            $vsbeResource['object'] = $resource['@id'];
            $vsbeResource['action'] = 'UPDATE';

            $this->commonGroundService->createResource($vsbeResource, ['component'=>'vsbe', 'type'=>'results']);
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

        $this->checkOrder($resource);
        $resource = $this->clearDefaults($resource);

        return $resource;
    }

    /*
     * Validates a resource with optional commonground and component specific logic
     *
     * @param array $resource The resource before enrichment
     * @param array The resource afther enrichment
     */
    public function onCreate(?array $resource)
    {
        $resource = $this->clearDefaults($resource);

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

        // Run the request through the very small business engine
        if ($this->commonGroundService->getComponentHealth('vsbe')) {
            $vsbeResource = [];
            $vsbeResource['object'] = $resource['@id'];
            $vsbeResource['action'] = 'CREATE';

            $this->commonGroundService->createResource($vsbeResource, ['component'=>'vsbe', 'type'=>'results']);
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

        // Lets see if this request should have an order
        $this->checkOrder($resource);

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

    public function clearDefaults(?array $request)
    {
        // We want to ignore the cache here
        $request = $this->commonGroundService->getResource(['component' => 'vrc', 'type' => 'requests', 'id'=>$request['id']], [], true);
        // Lets first see if we can grap an requested type and if it has stages
        if (!$requestType = $this->commonGroundService->getResource($request['requestType'])) {
            return $request;
        }
        $dependencyArray = [];
        foreach ($requestType['properties'] as $property) {
            $dependencies = [];
            foreach ($property['query'] as $key => $value) {
                if (strstr($value, 'request')) {
                    $value = substr($value, strrpos($value, '.') + 1);
                    array_push($dependencies, $value);
                } else {
                    array_push($dependencies, $value);
                }
                $dependencyArray[$property['name']] = $dependencies;
            }
        }
        foreach ($request['properties'] as $key => $value) {
            if (array_key_exists($key, $dependencyArray)) {
                $dependencies = $dependencyArray[$key];
                foreach ($dependencies as $dependency) {
                    if (!array_key_exists($dependency, $request['properties'])) {
                        unset($request['properties'][$key]);
                    }
                }
            }
        }

        return $request;
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
     * This function tests if a order should be created
     *
     * @param array $request The request before stage completion checks
     * @return array The resourceType afther stage completion checks
     */
    public function checkOrder(?array $request)
    {
        // We want to ignore the cache here

        $request = $this->commonGroundService->getResource(['component' => 'vrc', 'type' => 'requests', 'id'=>$request['id']], [], true);

        // Lets first see if we can grap an requested type and if it has stages
        if (!$requestType = $this->commonGroundService->getResource($request['requestType'])) {
            return $request;
        }

        // Let transform the request properties in something we can search
        $requestTypeOffers = [];
        $requestTypeCemeteries = []; /* @todo  abstraheren!*/

        foreach ($requestType['properties'] as $property) {
            if (array_key_exists('iri', $property) && $property['iri'] == 'pdc/offer') {
                $requestTypeOffers[$property['name']] = $property;
            }
            if (array_key_exists('iri', $property) && $property['iri'] == 'grc/cemetery') {
                $requestTypeCemeteries[$property['name']] = $property;
            }
        }

        // Lets skip all other things if this request type isn't supposed to contain products
        if (count($requestTypeOffers) == 0 && count($requestTypeCemeteries) == 0) {
            return $request;
        }

        if (count($requestTypeCemeteries) > 0) {

            // Oke we need to try to figure out  a date for this request
            if (array_key_exists('datum', $requestType['properties'])) {
                $startDate = (new \DateTime($requestType['properties']['datum']))->format('Y-m-d H:i:s');
            } elseif (array_key_exists('datum', $requestType['properties'])) {
                $startDate = (new \DateTime($requestType['properties']['date']))->format('Y-m-d H:i:s');
            } else {
                $startDate = (new \DateTime())->format('Y-m-d H:i:s');
            }

            // Lets dermine an end date
            /* @todo this should be calculated */
            $endDate = (new \DateTime())->format('Y-m-d H:i:s');

            foreach ($requestTypeCemeteries as $key => $requestTypeCemetery) {

                // Lets create events for submitters of requests
                foreach ($request['submitters'] as $submitter) {

                    // We only create calenders for validated submitters
                    if (!array_key_exists('brp', $submitter)) {
                        continue;
                    }

                    $calendars = $this->commonGroundService->getResourceList(['component' => 'arc', 'type' => 'calendars'], ['resource' => $submitter['brp']])['hydra:member'];
                    if (count($calendars) > 0) {
                        $calendar = $calendars[0];
                    } else {
                        // Make a user calendar
                        $calendar = [];
                        $calendar['resource'] = $submitter['brp'];
                        $calendar['name'] = $this->commonGroundService->getResource($submitter['brp'])['burgerservicenummer']; /* @todo nope nope nope dit moet een naam zijn */
                        $calendar['organization'] = $request['organization'];
                        $calendar['timeZone'] = 'CET';
                        $calendar = $this->commonGroundService->saveResource($calendar, ['component' => 'arc', 'type' => 'calendars']);
                    }

                    // create submitter events
                    $events = $this->commonGroundService->getResourceList(['component' => 'arc', 'type' => 'events'], ['calendar.id' => $calendar['id'], 'resource' => $request['@id']])['hydra:member'];

                    if (count($events) > 0) {
                        $event = $events[0];
                        $event['startDate'] = $startDate;
                        $event['endDate'] = $endDate;
                    //$event = $this->commonGroundService->saveResource($event, ['component' => 'arc', 'type' => 'events']);
                    } else {
                        $event = [];
                        $event['name'] = $request['reference'];
                        $event['description'] = $requestType['name'];
                        $event['organization'] = $request['organization'];
                        $event['resource'] = $request['@id'];
                        $event['calendar'] = $calendar['@id'];
                        $event['startDate'] = $startDate;
                        $event['endDate'] = $endDate;
                        $event['priority'] = 1;
                        $event = $this->commonGroundService->saveResource($event, ['component' => 'arc', 'type' => 'events']);
                    }
                }

                // $property =
                if (array_key_exists($requestTypeCemetery['name'], $request['properties'])) {
                    $cemetery = $this->commonGroundService->getResource($request['properties'][$requestTypeCemetery['name']]);
                    $calendar = $this->commonGroundService->getResource($cemetery['calendar']);
                } else {
                    continue;
                }

                $events = $this->commonGroundService->getResourceList(['component' => 'arc', 'type' => 'events'], ['calendar.id' => $calendar['id'], 'resource' => $request['@id']])['hydra:member'];

                if (count($events) > 0) {
                    $event = $events[0];
                    $event['startDate'] = $startDate;
                    $event['endDate'] = $endDate;
                //$event = $this->commonGroundService->saveResource($event, ['component' => 'arc', 'type' => 'events']);
                } else {
                    $event = [];
                    $event['name'] = $request['reference'];
                    $event['description'] = $requestType['name'];
                    $event['organization'] = $cemetery['organization'];
                    $event['resource'] = $request['@id'];
                    $event['calendar'] = $calendar['@id'];
                    $event['startDate'] = $startDate;
                    $event['endDate'] = $endDate;
                    $event['priority'] = 1;
                    $event = $this->commonGroundService->saveResource($event, ['component' => 'arc', 'type' => 'events']);
                }
            }
        }

        // Lets see if we need to make an invoice
        /* @todo dit zou een losse service moeten zijn */
        if (
            array_key_exists('status', $request) &&
            in_array($request['status'], ['submitted', 'in progress', 'progressed']) &&
            array_key_exists('order', $request) &&
            $request['order']
        ) {
            $invoices = $this->commonGroundService->getResourceList(['component' => 'bc', 'type' => 'invoices'], ['order'=>$request['order']])['hydra:member'];
            if (count($invoices) > 0) {
                $invoice = $invoices[0];
            } else {
                $post = ['url'=>$request['order']];
                $invoice = $this->commonGroundService->saveResource($post, ['component' => 'bc', 'type' => 'order']);
            }
        }

        // Making orders
        if (count($requestTypeOffers) > 0) {
            // Let check the property
            $products = [];
            foreach ($request['properties'] as $name => $value) {
                // Lets see if the property is part of the request type
                if (!array_key_exists($name, $requestTypeOffers)) {
                    // property is not part of the provide request type
                    continue;
                }

                // Lets handle possible array values
                if (is_array($value)) {
                    $products = array_merge($products, $value);
                } else {
                    $products[] = $value;
                }
            }

            // Lets skip all other things if we do not have products
            if (count($products) == 0) {
                return $request;
            }

            $requestItems = [];
            foreach ($products as $product) {
                $product = $this->commonGroundService->getResource($product);
                $orderItem = [];
                $orderItem['offer'] = $product['@id'];
                if (array_key_exists('name', $product)) {
                    $orderItem['name'] = $product['name'];
                }
                if (array_key_exists('description', $product)) {
                    $orderItem['description'] = $product['description'];
                }
                $orderItem['price'] = (string) $product['price'];
                $orderItem['priceCurrency'] = $product['priceCurrency'];
                $orderItem['quantity'] = 1;
                $requestItems[$product['@id']] = $orderItem;
            }

            // Lets make sure that the request has an order
            if (!array_key_exists('order', $request) || !$request['order']) {
                $order = [];

                $order['name'] = $request['reference'];
                $order['description'] = $request['reference'];
                $order['organization'] = $request['organization'];
                $order['resources'] = [$request['@id']];

                // Determining the custommer
                if (array_key_exists('submitters', $request) && !empty($request['submitters'])) {
                    // Lets go trought the options here
                    if (array_key_exists('brp', $request['submitters'][0])) {
                        $order['customer'] = $request['submitters'][0]['brp'];
                    } else {
                        $order['customer'] = $request['submitters'][0]['person'];
                    }
                } else {
                    /* @todo use the user */
                    //$order['customer'] = $request['submmiters'];
                }

                $order = $this->commonGroundService->saveResource($order, ['component' => 'orc', 'type' => 'orders']);
                $request['order'] = $order['@id'];
            } else {
                $order = $this->commonGroundService->getResource($request['order'], [], true);
            }

            $orderItems = [];
            if (array_key_exists('items', $order)) {
                foreach ($order['items'] as $orderItem) {
                    // Needs to be deleted
                    if (!array_key_exists($orderItem['offer'], $requestItems)) {
                        $this->commonGroundService->deleteResource($orderItem);
                        continue;
                    }
                    // Als we need to keep it
                    $orderItems[$orderItem['offer']] = $orderItem;
                }
            }

            foreach ($requestItems as $requestItemId => $requestItem) {
                //let see if we already have it
                if (array_key_exists($requestItemId, $orderItems)) {
                    continue;
                }
                // We need to add it
                $requestItem['order'] = $request['order'];
                $orderItem = $this->commonGroundService->saveResource($requestItem, ['component' => 'orc', 'type' => 'order_items']);
            }

            // Lets reload the recalculated order
            $order = $this->commonGroundService->getResource($request['order'], [], true);
            $request['order'] = $order['@id'];

            unset($request['submitters']);
            unset($request['roles']);
            $request = $this->commonGroundService->saveResource($request, ['component' => 'vrc', 'type' => 'request'], true, false);
        }

        return $request;
    }

    /*
     * Gets a requestType from a request and validates stage completion
     *
     * @param array $request The request before stage completion checks
     * @return array The resourceType afther stage completion checks
     */
    public function checkProperties(?array $request)
    {
        // Lets first see if we can grap an requested type and if it has stages
        if (!$requestType = $this->commonGroundService->getResource($request['requestType']) || !array_key_exists('stages', $requestType)) {
            return $request;
        }

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
    public function checkProperty(?array $request, $property)
    {
        $result = ['value'=>null, 'valid'=>true, 'messages'=>[]];

        // Lets see if the property is requered and unset, in wich case we do not need to do more validation
        if ((!array_key_exists($property['name'], $request['properties'])) && $property['required']) {
            $result['messages'] = ['value is required'];
            $result['valid'] = false;

            return $result;
        }
        // If we don't have a validation further checking has no point
        elseif (!array_key_exists($property['name'], $request['properties'])) {
            $result['messages'] = ['value is empty'];

            return $result;
        }

        $result['value'] = $request['properties'][$property['name']];

        // Now we could hit multiple problems, so lets turn de message in an array

        // Type validation
        if ($property['type']) {
            switch ($property['type']) {
                case 'string':

                    if (!is_string($property['type'])) {
                        $result['messages'][] = 'value should be a string';
                        $result['valid'] = false;
                    }

                    if ($property['maxLength'] && strlen($property['value']) > (int) $property['maxLength']) {
                        $result['messages'][] = 'value should be longer then'.$property['maxLength'];
                        $result['valid'] = false;
                    }
                    if ($property['minLength'] && strlen($property['value']) < (int) $property['minLength']) {
                        $result['messages'][] = 'value should be shorter then'.$property['minLength'];
                        $result['valid'] = false;
                    }
                    if ($property['pattern']) {
                    }

                    // Format is only validated in combination with type string
                    if ($property['format']) {
                        switch ($property['format']) {
                            case 'url':
                                break;
                            case 'date':
                                if ($property['minDate']) {
                                }
                                if ($property['maxDate']) {
                                }

                                break;
                            default:
                                $result['message'][] = 'property format '.$property['format'].' is not supported';
                        }
                    }

                    break;
                case 'integer':

                    if ($property['multipleOf']) {
                    }
                    if ($property['maximum'] && $property['exclusiveMaximum']) {
                    }
                    if ($property['maximum'] && !$property['exclusiveMaximum']) {
                    }
                    if ($property['minimum'] && $property['exclusiveMinimum']) {
                    }
                    if ($property['minimum'] && !$property['exclusiveMinimum']) {
                    }
                    break;
                case 'boolean':

                    break;
                case 'number':

                    break;
                case 'array':

                    if (!is_array($property['type'])) {
                        $result['messages'][] = 'value should be a string';
                        $result['valid'] = false;
                    } else {
                        if ($property['maxItems'] && count($result['value']) > (int) $property['maxItems']) {
                            $result['messages'][] = 'There should be no more then '.$property['maxItems'].' items';
                            $result['valid'] = false;
                        }
                        if ($property['minItems'] && count($result['value']) < (int) $property['minItems']) {
                            $result['messages'][] = 'There should be no less then '.$property['minItems'].' items';
                            $result['valid'] = false;
                        }
                        if ($property['uniqueItems']) {
                        }
                    }

                    break;
                default:
                    $result['message'][] = 'property type '.$property['type'].' is not supported';
            }
        }

        // Lets look for requered values
        if ($property['enum']) {
            if (!is_array($property['enum'])) {
                $property['enum'] = explode(',', $property['enum']);
            }
            if (in_array($result['value'], $property['enum'])) {
                $result['messages'][] = 'There the value should be one of '.implode(',', $property['enum']);
                $result['valid'] = false;
            }
        }

        if ($property['availableFrom']) {
            $date = new DateTime($property['availableFrom']);
            $now = new DateTime();

            if ($date > $now) {
                $result['messages'][] = 'This property is not yet available';
                $result['valid'] = false;
            }
        }

        if ($property['availableUntil']) {
            $date = new DateTime($property['availableFrom']);
            $now = new DateTime();

            if ($date < $now) {
                $result['messages'][] = 'This property is no longer available';
                $result['valid'] = false;
            }
        }

        if ($property['readOnly'] && $request['value']) {
            $result['messages'][] = 'This property is read only';
            $result['valid'] = false;
        }

        if ($property['iri']) {
        }

        // format validation to be implemented
        /*
                "query": [],
                "additionalItems": null,
                "maxProperties": null,
                "minProperties": null,
                "properties": null,
                "additionalProperties": null,
                "object": null,
                "nullable": null,
                "discriminator": null,
                "xml": null,

            */

        // format validation noy to bu suporterd
        /*
         *  "deprecated": null
         *  "writeOnly": null,
         */

        return $result;
    }

    /*
     * This function fills a procces with all the requered data in order to render it
     *
     * @param array $resource The resource before enrichment
     * @param array The resource afther enrichment
     */
    public function extendProcess(?array $procces)
    {
        $procces['valid'] = false;
        foreach ($procces['stages'] as $stageKey => $stage) {
            foreach ($stage['sections'] as $sectionKey => $section) {
                $procces['stages'][$stageKey]['sections'][$sectionKey]['propertiesForms'] = [];
                foreach ($section['properties'] as $propertyKey => $property) {
                    $property = $this->commonGroundService->getResource($property);
                    $property['value'] = null;
                    $property['valid'] = false;
                    $property['message'] = null;
                    unset($property['requestType']);
                    $procces['stages'][$stageKey]['sections'][$sectionKey]['propertiesForms'][$property['id']] = $property;
                }
                $procces['stages'][$stageKey]['sections'][$sectionKey]['valid'] = false;
            }
            $procces['stages'][$stageKey]['valid'] = false;
        }

        return $procces;
    }

    /*
     * Aditional logic triggerd afther a Request has been newly created
     *
     * @param array $resource The resource before enrichment
     * @param array The resource afther enrichment
     */
    public function fillProcess(?array $procces, array $request = null)
    {
        $procces = $this->extendProcess($procces);

        foreach ($procces['stages'] as $stageKey => $stage) {
            $procces['stages'][$stageKey]['valid'] = true;
            foreach ($stage['sections'] as $sectionKey => $section) {
                $procces['stages'][$stageKey]['sections'][$sectionKey]['propertiesForms'] = [];
                $procces['stages'][$stageKey]['sections'][$sectionKey]['valid'] = true;

                // Lets validate the indivual property forms
                foreach ($section['propertiesForms'] as $propertyKey => $property) {
                    // Lets validate
                    $result = $this->checkProperty($request, $property);
                    // Set the results
                    $property['value'] = $result['value'];
                    $property['valid'] = $result['valid'];
                    $property['messages'] = $result['messages'];
                    $property['messages'] = $result['messages'];
                    // Store results to the current procces
                    if (!$property['valid']) {
                        $procces['stages'][$stageKey]['sections'][$sectionKey]['valid'] = false;
                    }
                }

                if (!$procces['stages'][$stageKey]['sections'][$sectionKey]['valid']) {
                    $procces['stages'][$stageKey]['valid'] = false;
                }
            }
            if (!$procces['stages'][$stageKey]['valid']) {
                $procces['valid'] = false;
            }
        }

        return $procces;
    }

    public function createCgResource($properties, $requestType)
    {
        foreach($properties as $value){
            var_dump($value);
            die;
            foreach($requestType['properties'] as $requestProperty){
                if($value == $requestProperty['name']){
                    $property = $requestProperty;
                }
            }
            if(is_array($value) && array_key_exists('iri', $property) && $property['type'] == 'string' && $property['format'] == 'url' && $component = explode('/', $property['iri']) && count($component) == 2){
                // hebben we een cg resource
                $properties[$value] = $this->commonGroundService->saveResource($value, ['component' => component[0], 'type' => component[1]]);
            }
        }

        return $properties;
    }
}
