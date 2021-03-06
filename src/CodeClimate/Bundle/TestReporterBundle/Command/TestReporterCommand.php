<?php
namespace CodeClimate\Bundle\TestReporterBundle\Command;

use CodeClimate\Bundle\TestReporterBundle\CoverageCollector;
use CodeClimate\Bundle\TestReporterBundle\ApiClient;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Test reporter command
 */
class TestReporterCommand extends Command
{
    /**
     * Path to project root directory.
     *
     * @var string
     */
    protected $rootDir;

    /**
     * {@inheritdoc}
     *
     * @see \Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
        ->setName('test-reporter')
        ->setDescription('Code Climate PHP Test Reporter')
        ->addOption(
            'stdout',
            null,
            InputOption::VALUE_NONE,
            'Do not upload, print JSON payload to stdout'
        );
    }

    /**
     * {@inheritdoc}
     *
     * @see \Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $ret = 0;
        $collector = new CoverageCollector();
        $json = $collector->collectAsJson();

        if ($input->getOption('stdout')) {
            $output->writeln((string)$json);
        } else {
            $client = new ApiClient();
            $response = $client->send($json);

            if ($response) {
                $code = $response->getStatusCode();

                switch ($code) {
                    case 200:
                        $output->writeln("Test coverage data sent");
                        break;
                    case 401:
                        $output->writeln("An invalid CODECLIMATE_REPO_TOKEN repo token was specified.");
                        $ret = 1;
                        break;
                    default:
                        $output->writeln("Status code: ".$code);
                        $ret = 1;
                        break;
                }
            } else {
                $output->writeln("Unknown error posting Test coverage data.");
                $ret = 1;
            }
        }

        return $ret;
    }

    // accessor

    /**
     * Set root directory.
     *
     * @param string $rootDir Path to project root directory.
     *
     * @return void
     */
    public function setRootDir($rootDir)
    {
        $this->rootDir = $rootDir;
    }
}
