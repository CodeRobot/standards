<?php

  namespace CodeRobot\Standards\Enforcer;

  use Symfony\Component\Console\Command\Command;
  use Symfony\Component\Console\Input\InputArgument;
  use Symfony\Component\Console\Input\InputInterface;
  use Symfony\Component\Console\Input\InputOption;
  use Symfony\Component\Console\Output\OutputInterface;
  use Symfony\Component\Console\Helper\TableHelper as Table;

  /**
   * Blame Command
   *
   * @package Enforcer
   **/
  class BlameCommand extends Command {


    protected $input;
    protected $output;


    /**
     * Set up configuration for the command
     *
     * @return void
     **/
    protected function configure() {
      $this
        ->setName('blame')
        ->setDescription('Run a blame report on the given path')
        ->addArgument(
          'path',
          InputArgument::OPTIONAL,
          'Path to scan'
        );
    }


    /**
     * Execute the command
     *
     * @param InputInterface  $input  The input from the command line
     * @param OutputInterface $output Where we send output
     *
     * @return void
     **/
    protected function execute(InputInterface $input, OutputInterface $output) {
      $this->input  = $input;
      $this->output = $output;

      $this->verifyStandard();
      $path = $this->verifyPath($input->getArgument('path'));
      $data = $this->parseCodeSnifferReport($path);

      foreach ($data as $file) {
        $path     = (string)$file->attributes()->name;
        $errors   = (int)$file->attributes()->errors;
        $warnings = (int)$file->attributes()->warnings;

        $this->output->writeln(PHP_EOL . '<info>File: ' . str_replace(trim(`pwd`), '', $path) . '</info>');

        $report = [];

        foreach ($file->error as $error) {
          $row = [];
          $line   = (int)$error->attributes()->line;
          $source = (string)$error->attributes()->source;
          $cmd    = 'git blame -p -e -f -L' . $line . ',+1 ' . $path . ' | cat';
          $blame  = $this->parseBlame(`$cmd`);

          $row[] = (string)$line;
          $row[] = 'Error';
          $row[] = (string)$error;
          $row[] = $source;
          $row[] = $blame->name;
          $report[] = $row;
        }

        foreach ($file->warning as $error) {
          $row = [];
          $line   = (int)$error->attributes()->line;
          $source = (string)$error->attributes()->source;
          $cmd    = 'git blame -p -L' . $line . ',+1 ' . $path . ' | cat';
          $blame  = $this->parseBlame(`$cmd`);

          $row[] = (string)$line;
          $row[] = 'Warning';
          $row[] = (string)$error;
          $row[] = $source;
          $row[] = $blame->name;
          $report[] = $row;
        }

        $message = 'Found ';
        if ($errors > 0) {
          $message .= $errors . ' error';
          if ($errors > 1) {
            $message .= 's';
          }

          $message .= ' ';

          if ($warnings > 0) {
            $message .= 'and ';
          }
        }
        if ($warnings > 0) {
          $message .= $warnings  . ' warning';
          if ($warnings > 1) {
            $message .= 's';
          }
          $message .= ' ';
        }

        $this->output->writeln('<info>' . $message . '</info>');

        $table = new Table;
        $table
          ->setHeaders([
            'Line',
            'Type',
            'Error',
            'Sniff',
            'Author'
          ])
          ->setRows($report);

        $table->render($output);
      }
    }


    /**
     * Run the phpcs command and retrieve the output
     *
     * @param string $path The path to run blame report in
     *
     * @return SimpleXMLElement
     **/
    private function parseCodeSnifferReport($path = '.') {
      $this->output->writeln('<info>Running blame report in ' . $path . '</info>');

      $cmd = 'phpcs --report=xml -s --standard=phpcs.xml ' . $path;
      $xml = `$cmd`;
      @$data = simplexml_load_string($xml);

      if ($data === FALSE) {
        $this->output->writeln('<error>Could not parse phpcs xml response.</error>');
        exit(1);
      }

      return $data;
    }


    /**
     * Verify that it's a valid path we can report on
     *
     * @param string $path Path to the source code
     *
     * @return string
     **/
    private function verifyPath($path) {
      if (empty($path)) {
        $path = '.';
      }

      if (realpath($path) === FALSE) {
        $this->output->writeln('<error>Could not find path ' . $path . '.</error>');
        exit(1);
      }

      return $path;
    }


    /**
     * Verify that the standards XML file is present
     *
     * @return void
     **/
    private function verifyStandard() {
      if (!file_exists('phpcs.xml')) {
        $this->output->writeln('<error>Could not find standard. Please create a phpcs.xml file.</error>');
        exit(1);
      }
    }


    /**
     * Parse the blame string from the command line
     *
     * @param string $blame The raw blame
     *
     * @return object
     **/
    private function parseBlame($blame) {
      $author = (object)[
        'email' => NULL,
        'name'  => NULL
      ];

      $lines = explode(PHP_EOL, trim($blame));
      foreach ($lines as $line) {
        if (substr($line, 0, 7) == 'author ') {
          $author->name = str_replace('author ', '', $line);
        }
        if (substr($line, 0, 12) == 'author-mail ') {
          $author->email = str_replace('author-mail ', '', $line);
        }
      }
      return $author;
    }


  }