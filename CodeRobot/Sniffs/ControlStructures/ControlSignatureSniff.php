<?php


  /**
   * Verifies that control statements conform to their coding standards
   *
   * @package PHP_CodeSniffer
   */
  class CodeRobot_Sniffs_ControlStructures_ControlSignatureSniff extends PHP_CodeSniffer_Standards_AbstractPatternSniff {


    /**
     * Sniff constructor
     *
     * @return void
     */
    public function __construct() {
      parent::__construct(TRUE);
    }


    /**
     * Returns the patterns that this test wishes to verify
     *
     * @return array(string)
     */
    protected function getPatterns() {
      return array(
        'do {EOL...} while (...);EOL',
        'while (...) {EOL',
        'for (...) {EOL',
        'if (...) {EOL',
        'if (...) ...;EOL',
        'foreach (...) {EOL',
        '} else if (...) {EOL',
        '} elseif (...) {EOL',
        '} else {EOL',
        'do {EOL',
        'switch (...) {EOL'
      );

    }

  }