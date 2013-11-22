<?php
  /**
   * CodeRobot_Sniffs_PHP_UpperCaseConstantSniff.
   *
   * Checks that all uses of TRUE, FALSE and NULL are uppercase.
   */
  class CodeRobot_Sniffs_PHP_UpperCaseConstantSniff implements PHP_CodeSniffer_Sniff {

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register() {
      return array(
        T_TRUE,
        T_FALSE,
        T_NULL,
      );
    }


    /**
     * Processes this sniff, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token in the
     *                                        stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {
      $tokens = $phpcsFile->getTokens();

      $keyword = $tokens[$stackPtr]['content'];
      if(strtoupper($keyword) !== $keyword) {
        $error = 'TRUE, FALSE and NULL must be uppercase; expected "'.strtoupper($keyword).'" but found "'.$keyword.'"';
        $phpcsFile->addError($error, $stackPtr);
      }

    }
  }