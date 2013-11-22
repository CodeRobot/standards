<?php


  /**
   * CodeRobot_Sniffs_CSS_DisallowMultipleStyleDefinitionsSniff.
   *
   * Ensure that each style definition is on a line by itself.
   *
   * @package   PHP_CodeSniffer
   */
  class CodeRobot_Sniffs_CSS_DisallowMultipleStyleDefinitionsSniff implements PHP_CodeSniffer_Sniff {


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
      $next   = $phpcsFile->findNext(T_STYLE, ($stackPtr + 1));
      if($next === FALSE) {
        return;
      }

      if($tokens[$next]['line'] === $tokens[$stackPtr]['line']) {
        // We have to ignore filters since they give false positives
        if(strtoupper($tokens[$stackPtr]['content']) == 'FILTER') {
          return;
        }
        $error = 'Each style definition must be on a line by itself';
        $phpcsFile->addError($error, $next);
      }
    }


  }