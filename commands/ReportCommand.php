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


    /**
     * Set up configuration for the command
     *
     * @return void
     **/
    protected function configure() {
      $this
        ->setName('report')
        ->setDescription('Send an email report to a user')
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
    }


  }