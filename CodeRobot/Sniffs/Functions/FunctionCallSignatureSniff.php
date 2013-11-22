<?php


  /**
   * This sniff checks for whitespace in functions calls
   *
   * @package PHP_CodeSniffer
   */
  class CodeRobot_Sniffs_Functions_FunctionCallSignatureSniff implements PHP_CodeSniffer_Sniff {


    /**
     * Returns an array of tokens this test wants to listen for
     *
     * @return array
     */
    public function register() {
      return array(T_STRING);
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

      // Find the next non-empty token.
      $next = $phpcsFile->findNext(PHP_CodeSniffer_Tokens::$emptyTokens, ($stackPtr + 1), NULL, TRUE);

      if($tokens[$next]['code'] !== T_OPEN_PARENTHESIS) {
        // Not a function call.
        return;
      }

      if(isset($tokens[$next]['parenthesis_closer']) === FALSE) {
        // Not a function call.
        return;
      }

      // Find the previous non-empty token.
      $previous = $phpcsFile->findPrevious(PHP_CodeSniffer_Tokens::$emptyTokens, ($stackPtr - 1), NULL, TRUE);
      if($tokens[$previous]['code'] === T_FUNCTION) {
        // It's a function definition, not a function call.
        return;
      }

      // Stubbles
      if($tokens[$previous]['code'] === T_NEW && $tokens[$previous - 2]['code'] !== T_THROW) {
        // We are creating an object, not calling a function.
        // but ignore exceptions
        return;
      }

      if(($stackPtr + 1) !== $next) {
        // Checking this: $value = my_function[*](...).
        $error = 'Space before opening parenthesis of function call prohibited';
        $phpcsFile->addError($error, $stackPtr);
      }

      if($tokens[($next + 1)]['code'] === T_WHITESPACE) {
        if(ord($tokens[($next + 1)]['content']) != 13 && ord($tokens[($next + 1)]['content']) != 10) { // New lines are ok for lots of arguments
          if($tokens[($next + 2)]['code'] !== T_COMMENT) { // A space and then a comment is cool too
            // Checking this: $value = my_function([*]...).
            $error = 'Space after opening parenthesis of function call prohibited. (' . ord($tokens[($next + 1)]['content']) . ')';
            $phpcsFile->addError($error, $stackPtr);
          }
        }
      }

      $closer = $tokens[$next]['parenthesis_closer'];

      if($tokens[($closer - 1)]['code'] === T_WHITESPACE) {
        // Checking this: $value = my_function(...[*]).
        $between = $phpcsFile->findNext(T_WHITESPACE, ($next + 1), NULL, TRUE);

        // Only throw an error if there is some content between the parenthesis.
        // IE. Checking for this: $value = my_function().
        // If there is no content, then we would have thrown an error in the
        // previous IF statement because it would look like this:
        // $value = my_function( ).

        // ignore functions within function calls
        if($tokens[$closer + 1]['code'] !== T_SEMICOLON) {
          return;
        }

        $nextSemicolon  = $phpcsFile->findNext(T_SEMICOLON, ($stackPtr + 1));
        if($tokens[$nextSemicolon]['line'] === $tokens[$next]['line']) {
          // one line function call
          if($between !== $closer) {
            $error = 'Space before closing parenthesis of function call prohibited';
            $phpcsFile->addError($error, $closer);
          }
        } else {
          $closingParenthesisIndent = $tokens[$nextSemicolon - 1]['column'];
          $prevVar                  = $phpcsFile->findPrevious(T_VARIABLE, ($next - 1));
          $prevThrow                = $phpcsFile->findPrevious(T_THROW, ($stackPtr - 1));
          $prevDoubleColon          = $phpcsFile->findPrevious(T_DOUBLE_COLON, ($next - 1));

          if($prevVar && $tokens[$prevVar]['line'] === $tokens[$next]['line']) {
            $error = '';
          } else if($prevThrow && $tokens[$prevThrow]['line'] === $tokens[$next]['line']) {
            $exception = $phpcsFile->findNext(T_STRING, ($prevThrow + 1));
            if($tokens[$exception]['column'] !== $closingParenthesisIndent) {
              $error = 'Multiline Function call: Closing paranthesis indented incorrectly (b)';
              $phpcsFile->addError($error, $closer);
            }
          } else if($prevDoubleColon && $tokens[$prevDoubleColon]['line'] === $tokens[$next]['line']) {
            $staticClass = $prevDoubleColon - 1;
            if($tokens[$staticClass]['column'] !== $closingParenthesisIndent) {
              $error = 'Multiline Function call: Closing paranthesis indented incorrectly (c)';
              $phpcsFile->addError($error, $closer);
            }
          }
        }
      }

      $next = $phpcsFile->findNext(T_WHITESPACE, ($closer + 1), NULL, TRUE);

      if($tokens[$next]['code'] === T_SEMICOLON) {
        if(in_array($tokens[($closer + 1)]['code'], PHP_CodeSniffer_Tokens::$emptyTokens) === TRUE) {
          $error = 'Space after closing parenthesis of function call prohibited';
          $phpcsFile->addError($error, $closer);
        }
      }
    }
  }