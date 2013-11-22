<?php
  /**
   * CodeRobot_Sniffs_WhiteSpace_DisallowTabIndentSniff.
   *
   * Throws errors if tabs are used for indentation.
   **/
  class CodeRobot_Sniffs_WhiteSpace_DisallowTabIndentSniff implements PHP_CodeSniffer_Sniff {

    /**
     * A list of tokenizers this sniff supports.
     *
     * @var array
     */
    public $supportedTokenizers = array(
      'PHP',
      'JS',
      'CSS'
    );


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register() {
      return array(T_WHITESPACE);
    }


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile All the tokens found in the document.
     * @param int                  $stackPtr  The position of the current token in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {
      $tokens = $phpcsFile->getTokens();

      // Make sure this is whitespace used for indentation.
      $line = $tokens[$stackPtr]['line'];
      if($stackPtr > 0 && $tokens[($stackPtr - 1)]['line'] === $line) {
        return;
      }

      if(strpos($tokens[$stackPtr]['content'], "\t") !== FALSE) {
        $error = 'Spaces must be used to indent lines; tabs are not allowed';
        $phpcsFile->addError($error, $stackPtr);
      }
    }
  }
