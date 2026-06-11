<?php
namespace Mediashare\Marathon\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'version:upgrade',
    description: '<comment>Update</comment> version of Marathon',
    aliases: ['update', 'upgrade'],
)]
Class VersionUpgradeCommand extends Command {
    public function __construct(
        private readonly HttpClientInterface $client,
    ) { 
        parent::__construct();
    }

    protected function configure(): void {
        $this
            ->addArgument('version', InputArgument::OPTIONAL, 'Marathon <comment>version</comment> to update', 'main')    
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int {
        $response = $this->client->request("GET", "https://api.github.com/repos/privatorosy/marathon/tags");
        $tags = $response->toArray();
        if (($version = $input->getArgument('version')) === 'main'):
            $tag = $tags[0] ?? null;
        else:
            $tag = array_filter(
                $tags,
                static fn (array $tag) => $tag["name"] === $version
            )[0] ?? null;
        endif;

        if (!$tag):
            $output->writeln("The <error>$version</error> version was not found.");
            $output->writeln("Versions list: <comment>main</comment>" . implode("", array_map(static fn (array $tag) => " | <comment>{$tag['name']}</comment>", $tags)));
            return Command::INVALID;
        endif;

        if (!\Phar::running()):
            $output->writeln("<info>Use <comment>git fetch && git checkout {$version} && git pull</comment> for update Marathon.</info>");
            return Command::INVALID;
        endif;

        $file = \Phar::running();
        $file = str_replace('phar://', '', $file);
        $tmp = tempnam(sys_get_temp_dir(), 'marathon');
        if (!is_writable(\pathinfo($tmp, PATHINFO_DIRNAME))):
            $text = "<error>You have not permission for write <comment>".$tmp."</comment> file.</error>";
            $output->writeln($text);
            $text = "<error>You can use sudo command for allow permission.</error>";
            $output->writeln($text);
            return Command::FAILURE;
        endif;

        // Download
        $head = $version === 'main' ? $version : $tag['name'];
        $marathonBin = "https://github.com/privatorosy/marathon/raw/refs/heads/$head/marathon";
        file_put_contents($tmp, file_get_contents($marathonBin));
        if (!\file_exists($tmp)):
            $text = "<error>Error download <comment>[".$marathonBin."]</comment>.</error>";
            $output->writeln($text);
            return Command::FAILURE;
        endif;
        
        // Check Version
        $filesystem = new Filesystem();
        // if (filesize($file) !== filesize($tmp)
        //     || md5_file($file) !== md5_file($tmp)):
        //     $filesystem->remove($tmp);
        //     $output->writeln("<info>Marathon run already with last version</info>");
        //     return 0;
        // endif;
        
        // Replace binary file
        $filesystem->remove($file);
        $filesystem->rename($tmp, $file);
        $filesystem->chmod($file, 0755);

        $output->writeln("<info>Marathon successly <comment>updated</comment> to <comment>{$version}</comment> version.</info>");

        return Command::SUCCESS;
    }
}
