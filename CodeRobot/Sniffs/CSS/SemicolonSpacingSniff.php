<?php

  /**
   * CodeRobot_Sniffs_CSS_SemicolonSpacingSniff.
   *
   * Ensure each style definition has a semi-colon and it is spaced correctly.
   *
   * @package   PHP_CodeSniffer
   */
  class CodeRobot_Sniffs_CSS_SemicolonSpacingSniff implements PHP_CodeSniffer_Sniff {


    /**
     * A list of tokenizers this sniff supports.
     *
     * @var array
     */
    public $supportedTokenizers = array('CSS');


    /**
     * Returns the token types that this sniff is interested in.
     *
     * @return array(integer)
     */
    public function register() {
      return array(T_STYLE);
    }


    /**
     * Processes the tokens that this sniff is interested in.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file where the token was found.
     * @param int                  $stackPtr  The position in the stack where
     *                                        the token was found.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {
      $tokens = $phpcsFile->getTokens();

      $semicolon = $phpcsFile->findNext(T_SEMICOLON, ($stackPtr + 1));
      if($semicolon === FALSE || $tokens[$semicolon]['line'] !== $tokens[$stackPtr]['line']) {
        $error = 'Style definitions must end with a semicolon';
        $phpcsFile->addError($error, $stackPtr);
        return;
      }

      if($tokens[($semicolon - 1)]['code'] === T_WHITESPACE) {
        $length  = strlen($tokens[($semicolon - 1)]['content']);
        $error = "Expected 0 spaces before semicolon in style definition; $length found";
        $phpcsFile->addError($error, $stackPtr);
      }
    }


  }