<?php


  /**
   * Checks that arguments in function declarations are spaced correctly
   *
   * @package PHP_CodeSniffer
   */
  class CodeRobot_Sniffs_Functions_FunctionDeclarationArgumentSpacingSniff implements PHP_CodeSniffer_Sniff {


    /**
     * Returns an array of tokens this test wants to listen for
     *
     * @return array
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

      $functionName = $phpcsFile->findNext(array(T_STRING), $stackPtr);
      $openBracket  = $tokens[$stackPtr]['parenthesis_opener'];
      $closeBracket = $tokens[$stackPtr]['parenthesis_closer'];

      $nextParam = $openBracket;
      $params    = array();
      while(($nextParam = $phpcsFile->findNext(T_VARIABLE, ($nextParam + 1), $closeBracket)) !== FALSE) {

        $nextToken = $phpcsFile->findNext(T_WHITESPACE, ($nextParam + 1), ($closeBracket + 1), TRUE);
        if($nextToken === FALSE) {
          break;
        }

        $nextCode = $tokens[$nextToken]['code'];

        if($nextCode === T_EQUAL) {
          // Check parameter default spacing.
          $gap   = strlen($tokens[($nextParam + 1)]['content']);
          if($gap > 1) {
            $arg   = $tokens[$nextParam]['content'];
            $error = "Expected 1 space between argument \"$arg\" and equals sign; $gap found";
            $phpcsFile->addError($error, $nextToken);
          }

          $gap   = strlen($tokens[($nextToken + 1)]['content']);
          if($gap > 1) {
            $arg   = $tokens[$nextParam]['content'];
            $error = "Expected 1 space between default value and equals sign for argument \"$arg\"; $gap found";
            $phpcsFile->addError($error, $nextToken);
          }
        }

        // Find and check the comma (if there is one).
        $nextComma = $phpcsFile->findNext(T_COMMA, ($nextParam + 1), $closeBracket);
        if($nextComma !== FALSE) {
          // Comma found.
          if($tokens[($nextComma - 1)]['code'] === T_WHITESPACE) {
            $space = strlen($tokens[($nextComma - 1)]['content']);
            $arg   = $tokens[$nextParam]['content'];
            $error = "Expected 0 spaces between argument \"$arg\" and comma; $space found";
            $phpcsFile->addError($error, $nextToken);
          }
        }

        // Take references into account when expecting the
        // location of whitespace.
        if($phpcsFile->isReference(($nextParam - 1)) === TRUE) {
          $whitespace = $tokens[($nextParam - 2)];
        } else {
          $whitespace = $tokens[($nextParam - 1)];
        }

        if(empty($params) === FALSE) {
          // This is not the first argument in the function declaration.
          $arg = $tokens[$nextParam]['content'];

          if($whitespace['code'] === T_WHITESPACE) {
            $gap = strlen($whitespace['content']);

            // Before we throw an error, make sure there is no type hint.
            $comma     = $phpcsFile->findPrevious(T_COMMA, ($nextParam - 1));
            $nextToken = $phpcsFile->findNext(T_WHITESPACE, ($comma + 1), NULL, TRUE);
            if($phpcsFile->isReference($nextToken) === TRUE) {
              $nextToken++;
            }

            if($nextToken !== $nextParam) {
              // There was a type hint, so check the spacing between
              // the hint and the variable as well.
              $hint = $tokens[$nextToken]['content'];

              if($gap !== 1) {
                $error = "Expected 1 space between type hint and argument \"$arg\"; $gap found";
                $phpcsFile->addError($error, $nextToken);
              }

              if($tokens[($comma + 1)]['code'] !== T_WHITESPACE) {
                $error = "Expected 1 space between comma and type hint \"$hint\"; 0 found";
                $phpcsFile->addError($error, $nextToken);
              } else {
                $gap = strlen($tokens[($comma + 1)]['content']);
                if($gap !== 1) {
                  $error = "Expected 1 space between comma and type hint \"$hint\"; $gap found";
                  $phpcsFile->addError($error, $nextToken);
                }
              }
            } else if($gap !== 1) {
              $error = "Expected 1 space between comma and argument \"$arg\"; $gap found";
              $phpcsFile->addError($error, $nextToken);
            }
          } else {
            $error = "Expected 1 space between comma and argument \"$arg\"; 0 found";
            $phpcsFile->addError($error, $nextToken);
          }
        } else {
          // First argument in function declaration.
          if($whitespace['code'] === T_WHITESPACE) {
            $gap = strlen($whitespace['content']);
            $arg = $tokens[$nextParam]['content'];

            // Before we throw an error, make sure there is no type hint.
            $bracket   = $phpcsFile->findPrevious(T_OPEN_PARENTHESIS, ($nextParam - 1));
            $nextToken = $phpcsFile->findNext(T_WHITESPACE, ($bracket + 1), NULL, TRUE);
            if($phpcsFile->isReference($nextToken) === TRUE) {
              $nextToken++;
            }

            if($nextToken !== $nextParam) {
              // There was a type hint, so check the spacing between
              // the hint and the variable as well.
              $hint = $tokens[$nextToken]['content'];

              if($gap !== 1) {
                $error = "Expected 1 space between type hint and argument \"$arg\"; $gap found";
                $phpcsFile->addError($error, $nextToken);
              }

              if($tokens[($bracket + 1)]['code'] === T_WHITESPACE) {
                $gap   = strlen($tokens[($bracket + 1)]['content']);
                $error = "Expected 0 spaces between opening bracket and type hint \"$hint\"; $gap found";
                $phpcsFile->addError($error, $nextToken);
              }
            } else {
              $error = "Expected 0 spaces between opening bracket and argument \"$arg\"; $gap found";
              $phpcsFile->addError($error, $nextToken);
            }
          }
        }

        $params[] = $nextParam;
      }

      if(empty($params) === TRUE) {
        // There are no parameters for this function.
        if(($closeBracket - $openBracket) !== 1) {
          $space = strlen($tokens[($closeBracket - 1)]['content']);
          $error = "Expected 0 spaces between brackets of function declaration; $space found";
          $phpcsFile->addError($error, $stackPtr);
        }
      } else if($tokens[($closeBracket - 1)]['code'] === T_WHITESPACE) {
        $lastParam = array_pop($params);
        $arg       = $tokens[$lastParam]['content'];
        $gap       = strlen($tokens[($closeBracket - 1)]['content']);
        $error     = "Expected 0 spaces between argument \"$arg\" and closing bracket; $gap found";
        $phpcsFile->addError($error, $closeBracket);
      }
    }


  }