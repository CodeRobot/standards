<?php


  /**
   * Checks that the file does not end with a closing tag
   *
   * @package PHP_CodeSniffer
   */
  class CodeRobot_Sniffs_Files_ClosingTagSniff implements PHP_CodeSniffer_Sniff {


    /**
     * Returns an array of tokens this test wants to listen for
     *
     * @return array
     */
    public function register() {
      return array(T_CLOSE_TAG);
    }


    /**
     * Processes this sniff, when one of its tokens is encountered
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned
     * @param integer              $stackPtr  The position of the current token in the stack passed in $tokens
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {
      $tokens = $phpcsFile->getTokens();

      $next = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), NULL, TRUE);
      if($next === FALSE) {
        $error = 'A closing tag is not permitted at the end of a PHP file';
        $phpcsFile->addError($error, $stackPtr);
      }
    }


  }