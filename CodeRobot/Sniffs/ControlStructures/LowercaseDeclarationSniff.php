<?php


  /**
   * Ensures all control structure keywords are lowercase
   *
   * @package PHP_CodeSniffer
   */
  class CodeRobot_Sniffs_ControlStructures_LowercaseDeclarationSniff implements PHP_CodeSniffer_Sniff {


    /**
     * Returns an array of tokens this test wants to listen for
     *
     * @return array
     */
    public function register() {
      return array(
        T_IF,
        T_ELSE,
        T_ELSEIF,
        T_FOREACH,
        T_FOR,
        T_DO,
        T_SWITCH,
        T_WHILE,
        T_TRY,
        T_CATCH
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

      $content = $tokens[$stackPtr]['content'];
      if($content !== strtolower($content)) {
        $type     = strtoupper($content);
        $expected = strtolower($content);
        $error    = "$type keyword must be lowercase; expected \"$expected\" but found \"$content\"";
        $phpcsFile->addError($error, $stackPtr);
      }
    }


  }