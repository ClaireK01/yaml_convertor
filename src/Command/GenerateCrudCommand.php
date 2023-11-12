<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\VarDumper\VarDumper;

#[AsCommand(
    name: 'app:generate-crud',
    description: 'Add a short description for your command',
)]
class GenerateCrudCommand extends Command
{

    //TODO : Rassembler chaque génération dans une seule et même fonction

    const PATH_SKELETON = "./src/Skeletons/";


    protected function configure(): void
    {

    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $name = $io->ask("Nom de l'entité ? (ex : Article, Bibliotheque...)");
        $trans = $io->confirm('Générer les traductions ?', 'y');

        //entité
        $this->generate('Entity', $name, './src/Entity/', 'Entity.txt',  $trans, $io);
        //repo
        $this->generate('Repository', $name, './src/Repository/', 'Repository.txt',  $trans, $io);
        //form
        $this->generate('Type', $name, './src/Form/', 'Form.txt',  $trans, $io);
        if ($trans) {
            //translation
            $this->generate('Translation', $name, './src/Entity/', 'Translation.txt',  $trans, $io);
            //translation repository
            $this->generate('TranslationRepository', $name, './src/Repository/', 'TranslationRepository.txt',  $trans, $io);
        }


        $io->success('Entité ' . $name . ' créer avec succès !');
        return Command::SUCCESS;

    }

    public function generate($type, $name, $targetPath, $file, $trans, $io)
    {
        $io->write('Launching generation ' . $type . '...');
        $content = file_get_contents(self::PATH_SKELETON . $file);

        if ($content) {
            $content = str_replace('{NAME}', ucfirst($name), $content);
            if ($trans) {
                $content = str_replace('{IFCONTENT}', '', $content);
                $content = str_replace('{/IFCONTENT}', '', $content);
                $content = str_replace('{ELSECONTENT}([\s\S]*?){/ELSECONTENT}~', '', $content);
            } else {
                $content = str_replace('{ELSECONTENT}', '', $content);
                $content = str_replace('{/ELSECONTENT}', '', $content);
                $re = '#{IFCONTENT}.*?{\/IFCONTENT}#';

                VarDumper::dump('result:');
                die(VarDumper::dump(preg_match($re, $content)));

                $content = preg_replace($re, '', $content);
            }
            if($type == "Entity"){
                $rsrc = fopen($targetPath . $name . '.php', 'w+');
            }else{
                $rsrc = fopen($targetPath . $name . $type . '.php', 'w+');
            }
            if ($rsrc) {
                fwrite($rsrc, $content);
                fclose($rsrc);
            } else {
                $io->error('Impossible de créer le fichier');
                return Command::FAILURE;
            }

        } else {
            $io->error('Skellette ' . $type . ' introuvable');
            return Command::FAILURE;
        }

        return true;
    }
}
