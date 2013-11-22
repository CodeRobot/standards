<?php
  /**
   * CodeRobot_Sniffs_Strings_UnnecessaryStringConcatSniff.
   *
   * Checks that two strings are not concatenated together; suggests
   * using one string instead.
   */
  class CodeRobot_Sniffs_Strings_SpaceBetweenConcatSniff implements PHP_CodeSniffer_Sniff {

    /**
     * A list of tokenizers this sniff supports.
     *
     * @var array
     */
    public $supportedTokenizers = array(
      'PHP',
    );


    /**
     * If true, an error will be thrown; otherwise a warning.
     *
     * @var bool
     */
    protected $error = true;


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register() {
      return array(
        T_STRING_CONCAT,
      );
    }


    /**
     * Processes this sniff, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned.
     * @param int                  $stackPtr  The position of the current token
     *                                        in the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {
      // Work out which type of file this is for.
      $tokens = $phpcsFile->getTokens();
      #if($tokens[$stackPtr]['code'] === T_STRING_CONCAT) {
      #  if($phpcsFile->tokenizerType === 'JS') {
      #    return;
      #  }
      #} else {
      #  if($phpcsFile->tokenizerType === 'PHP') {
      #    return;
      #  }
      #}

      if($tokens[$stackPtr - 1]['code'] === T_WHITESPACE && $tokens[$stackPtr + 1]['code'] === T_WHITESPACE) {
        return;
      }

      #$prev = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
      #$next = $phpcsFile->findNext(T_WHITESPACE, ($stackPtr + 1), null, true);
      #if($prev === false || $next === false) {
      #  return;
      #}

      #$stringTokens = PHP_CodeSniffer_Tokens::$stringTokens;
      #if(in_array($tokens[$prev]['code'], $stringTokens) === true
      #  && in_array($tokens[$next]['code'], $stringTokens) === true
      #) {
      $error = 'A space is required before and after a string concat symbol.';
      $phpcsFile->addError($error, $stackPtr);
      #  if($this->error === true) {
      #    $phpcsFile->addError($error, $stackPtr);
      #  } else {
      #    $phpcsFile->addWarning($error, $stackPtr);
      #  }
      #}
    }
  }