<?php


  /**
   * Check function definition spacing
   *
   * @package PHP_CodeSniffer
   **/
  class CodeRobot_Sniffs_Functions_FunctionDefinitionSpacingSniff implements PHP_CodeSniffer_Sniff {


    var $tokens = NULL;


    /**
     * Returns the token types that this sniff is interested in
     *
     * @return array(integer)
     */
    public function register() {
      return array(
        T_FUNCTION
      );
    }


    /**
     * Processes the tokens that this sniff is interested in
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file where the token was found
     * @param integer              $stackPtr  The position in the stack where the token was found
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {
      $this->tokens     = $phpcsFile->getTokens();
      $next_line        = $this->tokens[$stackPtr]['line'] + 1;
      $next_line_tokens = $this->get_tokens_on_line($next_line);

      foreach ($next_line_tokens as $token) {
        if ($token['code'] !== T_WHITESPACE) return;
      }

      if ($token['content'] == "\n") return;

      $error = 'The first line of a function must not be blank';
      $phpcsFile->addError($error, $stackPtr);
    }


    /**
     * Get all of the tokens on the given line number
     *
     * @param integer $line_number The line number we want tokens for
     *
     * @return array
     **/
    public function get_tokens_on_line($line_number) {
      $line_tokens = array();
      foreach ($this->tokens as $token) {
        if ($token['line'] == $line_number) {
          $line_tokens[] = $token;
        }
      }
      return $line_tokens;
    }


  }