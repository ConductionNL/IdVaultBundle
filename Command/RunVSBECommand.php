<?php

// src/Command/CreateUserCommand.php

namespace Conduction\CommonGroundBundle\Command;

use Conduction\CommonGroundBundle\Service\CommonGroundService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Twig\Environment;

class RunVSBECommand extends Command
{
    private $commonGroundService;

    public function __construct(CommonGroundService $commonGroundService)
    {
        $this->commonGroundService = $commonGroundService;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('app:vsbe:start')
            // the short description shown while running "php bin/console list"
            ->setDescription('Creates a new helm chart.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command allows you to create a new hel chart from the helm template')
            ->setAliases(['app:vsbe:start'])
            ->setDescription('Kick off the VSBE')
            ->addOption('object', null, InputOption::VALUE_REQUIRED, 'The object to start')
            ->addOption('action', null, InputOption::VALUE_OPTIONAL, 'The CRUD action that belongs to the object', 'CREATE');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        /** @var string $version */
        $resource['action'] = $input->getOption('action');
        $resource['object'] = $input->getOption('object');

        $this->commonGroundService->createResource($resource, ['component'=>'vsbe', 'type'=>'results'], false, true, false);
    }
}
