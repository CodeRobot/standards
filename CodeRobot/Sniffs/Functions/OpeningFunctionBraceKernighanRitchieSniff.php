<?php


  /**
   * Checks that the opening brace of a function is on the same line as the function declaration
   *
   * @package PHP_CodeSniffer
   */
  class CodeRobot_Sniffs_Functions_OpeningFunctionBraceKernighanRitchieSniff implements PHP_CodeSniffer_Sniff {


    /**
     * Registers the tokens that this sniff wants to listen for
     *
     * @return void
     */
    public function register() {
      return array(T_FUNCTION);
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

      if(isset($tokens[$stackPtr]['scope_opener']) === FALSE) {
        return;
      }

      $openingBrace = $tokens[$stackPtr]['scope_opener'];

      // The end of the function occurs at the end of the argument list. Its
      // like this because some people like to break long function declarations
      // over multiple lines.
      $functionLine = $tokens[$tokens[$stackPtr]['parenthesis_closer']]['line'];
      $braceLine    = $tokens[$openingBrace]['line'];

      $lineDifference = ($braceLine - $functionLine);

      if($lineDifference > 0) {
        $error = 'Opening brace should be on the same line as the declaration';
        $phpcsFile->addError($error, $openingBrace);
        return;
      }

      // Checks that the closing parenthesis and the opening brace are
      // separated by a whitespace character.
      $closerColumn = $tokens[$tokens[$stackPtr]['parenthesis_closer']]['column'];
      $braceColumn  = $tokens[$openingBrace]['column'];

      $columnDifference = ($braceColumn - $closerColumn);

      if($columnDifference !== 2) {
        $error = 'Expected 1 space between the closing parenthesis and the opening brace; found ' . ($columnDifference - 1) . '.';
        $phpcsFile->addError($error, $openingBrace);
        return;
      }

      // Check that a tab was not used instead of a space.
      $spaceTokenPtr = ($tokens[$stackPtr]['parenthesis_closer'] + 1);
      $spaceContent  = $tokens[$spaceTokenPtr]['content'];
      if($spaceContent !== ' ') {
        $error = 'Expected a single space character between closing parenthesis and opening brace; found "' . $spaceContent . '".';
        $phpcsFile->addError($error, $openingBrace);
        return;
      }
    }


  }