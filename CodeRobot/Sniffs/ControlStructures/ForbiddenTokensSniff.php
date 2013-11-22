<?php


  /**
   * Forbids the use of certain tokens such as the alternative syntax, print, goto, and eval
   *
   * @package PHP_CodeSniffer
   */
  class CodeRobot_Sniffs_ControlStructures_ForbiddenTokensSniff implements PHP_CodeSniffer_Sniff {


    /**
     * If true, an error will be thrown; otherwise a warning
     *
     * @var bool
     */
    protected $error = TRUE;


    /**
     * Returns an array of tokens this test wants to listen for
     *
     * @return array
     */
    public function register() {
      return array(
        T_ENDDECLARE,
        T_ENDFOR,
        T_ENDFOREACH,
        T_ENDIF,
        T_ENDSWITCH,
        T_ENDWHILE,
        T_PRINT,
        T_GOTO,
        T_EVAL
      );
    }


    /**
     * Processes this test, when one of its tokens is encountered
     *
     * @param PHP_CodeSniffer_File $phpcsFile The file being scanned
     * @param integer              $stackPtr  The position of the current token in the stack passed in $tokens
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {
      $tokens = $phpcsFile->getTokens();

      $token = strtolower($tokens[$stackPtr]['content']);

      $error = 'The use of ' . $token . ' is forbidden';
      $phpcsFile->addError($error, $stackPtr);
    }


  }