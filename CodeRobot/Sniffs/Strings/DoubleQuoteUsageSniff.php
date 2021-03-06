<?php
  /**
   * CodeRobot_Sniffs_Strings_DoubleQuoteUsageSniff.
   *
   * Makes sure that any use of Double Quotes ("") are warranted.
   *
   */
  class CodeRobot_Sniffs_Strings_DoubleQuoteUsageSniff implements PHP_CodeSniffer_Sniff {


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register() {
      return array(
        T_CONSTANT_ENCAPSED_STRING,
      );
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

      $workingString = $tokens[$stackPtr]['content'];

      // Check if it's a double quoted string.
      if(strpos($workingString, '"') === FALSE) {
        return;
      }

      // Make sure it's not a part of a string started above.
      // If it is, then we have already checked it.
      if($workingString[0] !== '"') {
        return;
      }

      // Work through the following tokens, in case this string is stretched
      // over multiple Lines.
      for($i = ($stackPtr + 1); $i < $phpcsFile->numTokens; $i++) {
        if($tokens[$i]['type'] !== 'T_CONSTANT_ENCAPSED_STRING') {
          break;
        }

        $workingString .= $tokens[$i]['content'];
      }

      $allowedChars = array(
        '\n',
        '\r',
        '\f',
        '\t',
        '\v',
        '\x',
        '\'',
      );

      foreach($allowedChars as $testChar) {
        if(strpos($workingString, $testChar) !== FALSE) {
          return;
        }
      }

      $error = "String $workingString does not require double quotes; use single quotes instead";
      $phpcsFile->addError($error, $stackPtr);

    }


  }