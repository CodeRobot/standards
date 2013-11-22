<?php


  /**
   * Checks the function declaration is correct
   *
   * @package PHP_CodeSniffer
   */
  class CodeRobot_Sniffs_Functions_FunctionDeclarationSniff extends PHP_CodeSniffer_Standards_AbstractPatternSniff {


    /**
     * Returns an array of patterns to check are correct
     *
     * @return array
     */
    protected function getPatterns() {
      return array(
        'function abc(...);',
        'abstract function abc(...);',
        'function abc(...) {EOL',
      );
    }


  }