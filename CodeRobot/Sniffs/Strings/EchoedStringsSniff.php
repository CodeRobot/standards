<?php

  /**
   * CodeRobot_Sniffs_Strings_EchoedStringsSniff.
   *
   * Makes sure that any strings that are "echoed" are not enclosed in brackets
   * like a function call.
   */
  class CodeRobot_Sniffs_Strings_EchoedStringsSniff implements PHP_CodeSniffer_Sniff {

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register() {
      return array(T_ECHO);
    }


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token in the
     *                                        stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {
      $tokens = $phpcsFile->getTokens();

      $firstContent = $phpcsFile->findNext(array(T_WHITESPACE), ($stackPtr + 1), NULL, TRUE);
      // If the first non-whitespace token is not an opening parenthesis, then we are not concerned.
      if($tokens[$firstContent]['code'] !== T_OPEN_PARENTHESIS) {
        return;
      }

      $endOfStatement = $phpcsFile->findNext(array(T_SEMICOLON), $stackPtr, NULL, FALSE);

      // If the token before the semi-colon is not a closing parenthesis, then we are not concerned.
      if($tokens[($endOfStatement - 1)]['code'] !== T_CLOSE_PARENTHESIS) {
        return;
      }

      if(($phpcsFile->findNext(PHP_CodeSniffer_Tokens::$operators, $stackPtr, $endOfStatement, FALSE)) === FALSE) {
        // There are no arithmetic operators in this.
        $error = 'Echoed strings should not be bracketed';
        $phpcsFile->addError($error, $stackPtr);
      }

    }
  }