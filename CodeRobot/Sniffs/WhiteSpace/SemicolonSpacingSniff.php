<?php
  /**
   * Squiz_Sniffs_WhiteSpace_SemicolonSpacingSniff.
   *
   * Ensure there is no whitespace before a semicolon.
   */
  class CodeRobot_Sniffs_WhiteSpace_SemicolonSpacingSniff implements PHP_CodeSniffer_Sniff {


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register() {
      return array(T_SEMICOLON);
    }


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token
     *                                        in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {
      $tokens = $phpcsFile->getTokens();

      $prevType = $tokens[($stackPtr - 1)]['code'];
      if(in_array($prevType, PHP_CodeSniffer_Tokens::$emptyTokens) === TRUE) {
        $nonSpace = $phpcsFile->findPrevious(PHP_CodeSniffer_Tokens::$emptyTokens, ($stackPtr - 2), NULL, TRUE);
        $expected = $tokens[$nonSpace]['content'].';';
        $found    = $phpcsFile->getTokensAsString($nonSpace, ($stackPtr - $nonSpace)).';';
        $error    = "Space found before semicolon; expected \"$expected\" but found \"$found\"";
        $phpcsFile->addError($error, $stackPtr);
      }
    }
  }