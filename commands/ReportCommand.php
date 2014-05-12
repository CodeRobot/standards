<?php

  namespace CodeRobot\Standards\Enforcer;

  use Symfony\Component\Console\Command\Command;
  use Symfony\Component\Console\Input\InputArgument;
  use Symfony\Component\Console\Input\InputInterface;
  use Symfony\Component\Console\Input\InputOption;
  use Symfony\Component\Console\Output\OutputInterface;

  /**
   * Report Command
   *
   * @package Enforcer
   **/
  class ReportCommand extends Command {


    protected $input;
    protected $output;
    protected $authorMap;
    protected $reports;
    protected $templatePath;


    /**
     * Set up configuration for the command
     *
     * @return void
     **/
    protected function configure() {
      $this
        ->setName('report')
        ->setDescription('Send an email report to a user')
        ->addArgument(
          'path',
          InputArgument::OPTIONAL,
          'Path to scan'
        )
        ->setHelp('<info>php enforcer report</info>');
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

      $this->loadAuthorMap();
      $this->verifyStandard();

      $this->path         = $this->verifyPath($input->getArgument('path'));
      $this->templatePath = __DIR__ . '/../templates/';

      $data = $this->parseCodeSnifferReport($this->path);

      $currentCommit = substr($this->getCurrentCommit(), 0, 8);
      $triggeredBy   = $this->getLastAuthor();

      foreach ($data as $file) {
        $path     = (string)$file->attributes()->name;
        $errors   = (int)$file->attributes()->errors;
        $warnings = (int)$file->attributes()->warnings;

        foreach ($file->error as $error) {
          $this->handleError($path, $error);
        }

        foreach ($file->warning as $error) {
          $this->handleError($path, $error);
        }
      }

      $transport = \Swift_SmtpTransport::newInstance('smtp.sendgrid.net', 587)
        ->setUsername('sparkhire')
        ->setPassword('53V3ytVqGf');

      $mailer = \Swift_Mailer::newInstance($transport);

      foreach ($this->reports as $email => $report) {
        if ($email != $triggeredBy) continue;

        $this->output->writeln('<info>Sending report to ' . $email . '</info>');

        $message = View::render($this->templatePath . 'blame_email', [
          'files'         => $report,
          'currentCommit' => $currentCommit,
          'triggeredBy'   => $triggeredBy,
          'date'          => \Carbon\Carbon::now()->setTimezone('America/Chicago')->format('l jS \\of F Y h:i:s A')
        ]);

        // Create a message
        $message = \Swift_Message::newInstance('Code Standards Report for #' . $currentCommit)
          ->setFrom([
            'dprevite@etech360.com' => 'Jenkins Enforcer'
          ])
          ->setTo([
            $triggeredBy
          ])
          ->addPart($message, 'text/html');

        // Send the message
        $result = $mailer->send($message);
      }
    }


    /**
     * Do your thing
     *
     * @param string            $path  Path to the file
     * @param \SimpleXMLElement $error The error to thing
     *
     * @return void
     **/
    private function handleError($path, \SimpleXMLElement $error) {
      $row = [];
      $message = (string)$error;
      $line    = (int)$error->attributes()->line;
      $source  = (string)$error->attributes()->source;
      $cmd     = 'git blame -p -e -f -L' . $line . ',+1 ' . $path . ' | cat';
      $blame   = $this->parseBlame(`$cmd`);

      $error = $blame;
      $error->path     = str_replace(getcwd() . '/', '', $path);
      $error->line     = $line;
      $error->standard = $source;
      $error->message  = $message;

      if (!isset($this->reports[$error->email])) {
        $this->reports[$error->email] = [];
      }

      if (!isset($this->reports[$error->email][$error->path])) {
        $this->reports[$error->email][$error->path] = [];
      }

      $this->reports[$error->email][$error->path][] = $error;
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
      $author->commit = explode(' ', $lines[0])[0];

      foreach ($lines as $line) {
        if (substr($line, 0, 7) == 'author ') {
          $author->name = str_replace('author ', '', $line);
        }
        if (substr($line, 0, 12) == 'author-mail ') {
          $author->email = str_replace('author-mail ', '', $line);
          $author->email = str_replace('<', '', $author->email);
          $author->email = str_replace('>', '', $author->email);
        }
      }

      // Check for these things in the author map
      foreach ($this->authorMap->emails as $original => $email) {
        if ($original == $author->email) {
          $author->email = $email;
          break;
        }
      }

      foreach ($this->authorMap->names as $original => $name) {
        if ($original == $author->name) {
          $author->name = $name;
          break;
        }
      }

      return $author;
    }


    /**
     * Load the author map, if there is one
     *
     * @return void
     **/
    private function loadAuthorMap() {
      $authorMapPath = getcwd() . '/author_map.json';
      if (file_exists($authorMapPath)) {
        $this->authorMap = json_decode(file_get_contents($authorMapPath));
      } else {
        $this->authorMap = (object)[
          'emails' => [],
          'names'  => []
        ];
      }
    }


    /**
     * Get the current commit
     *
     * @return string
     **/
    private function getCurrentCommit() {
      $cmd = 'git --git-dir ' . getcwd() . '/.git rev-parse HEAD';
      return `$cmd`;
    }


    /**
     * Get the last author
     *
     * @return string
     **/
    private function getLastAuthor() {
      $cmd    = 'git --git-dir ' . getcwd() . '/.git log --pretty=format:"%ae" HEAD^..HEAD | head -1';
      $author = trim(`$cmd`);

      // Check for these things in the author map
      foreach ($this->authorMap->emails as $original => $email) {
        if ($original == $author) {
          $author = $email;
          break;
        }
      }

      return $author;
    }


  }
