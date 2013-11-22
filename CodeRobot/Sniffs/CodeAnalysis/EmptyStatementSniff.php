<?php


  /**
   * This sniff class detected empty statement
   *
   * This sniff implements the common algorithm for empty statement body detection.
   * A body is considered as empty if it is completely empty or it only contains
   * whitespace characters and|or comments
   *
   * @package PHP_CodeSniffer
   */
  class CodeRobot_Sniffs_CodeAnalysis_EmptyStatementSniff implements PHP_CodeSniffer_Sniff {


    /**
     * Registers the tokens that this sniff wants to listen for
     *
     * @return array(integer)
     */
    public function register() {
      return array(
        T_CATCH,
        T_DO,
        T_ELSE,
        T_ELSEIF,
        T_FOR,
        T_FOREACH,
        T_IF,
        T_SWITCH,
        T_TRY,
        T_WHILE
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
      $token  = $tokens[$stackPtr];

      // Skip for-statements without body.
      if(isset($token['scope_opener']) === FALSE) {
        return;
      }

      $next = ++$token['scope_opener'];
      $end  = --$token['scope_closer'];

      $emptyBody = TRUE;
      for(; $next <= $end; ++$next) {
        if(in_array($tokens[$next]['code'], PHP_CodeSniffer_Tokens::$emptyTokens) === FALSE) {
          $emptyBody = FALSE;
          break;
        }
      }

      if($emptyBody === TRUE) {
        // Get token identifier.
        $name  = $phpcsFile->getTokensAsString($stackPtr, 1);
        $error = sprintf('Empty %s statement detected', strtoupper($name));
        $phpcsFile->addError($error, $stackPtr);
      }
    }


  }