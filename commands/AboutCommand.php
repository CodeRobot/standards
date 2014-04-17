<?php

  namespace CodeRobot\Standards\Enforcer;

  use Symfony\Component\Console\Command\Command;
  use Symfony\Component\Console\Input\InputArgument;
  use Symfony\Component\Console\Input\InputInterface;
  use Symfony\Component\Console\Input\InputOption;
  use Symfony\Component\Console\Output\OutputInterface;

  /**
   * About Command
   *
   * @package Enforcer
   **/
  class AboutCommand extends Command {


    /**
     * Set up configuration for the command
     *
     * @return void
     **/
    protected function configure() {
      $this
        ->setName('about')
        ->setDescription('Information about this command')
        ->setHelp('<info>php enforcer about</info>');
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
      $output->writeln(
        '<info>Enforcer</info>' . PHP_EOL .
        '<comment>This is a collection of utilities to help enforce code standards.</comment>'
      );
    }


  }