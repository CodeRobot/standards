<?php

  /**
   * Throws errors if boolean comparison operators are used rather than
   * logical ones.
   */
  class CodeRobot_Sniffs_Operators_ComparisonOperatorSniff implements PHP_CodeSniffer_Sniff {

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register() {
      return array(
        T_LOGICAL_AND,
        T_LOGICAL_OR,
        T_IS_GREATER_OR_EQUAL,
        T_IS_SMALLER_OR_EQUAL,
        T_IS_EQUAL,
        T_IS_NOT_EQUAL,
        T_IS_IDENTICAL,
        T_IS_NOT_IDENTICAL,
        T_IS_NOT_EQUAL,
        T_GREATER_THAN,
        T_LESS_THAN
      );
    }


    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile All the tokens found in the
     *        document
     * @param int $stackPtr Position of the current token in the stack passed
     *        in $tokens
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {
      $tokens = $phpcsFile->getTokens();

      switch($tokens[$stackPtr]['type']) {
        case 'T_LOGICAL_AND':
        case 'T_LOGICAL_OR':
          $error = 'Operators AND and OR are not allowed, use && and || instead';
          $phpcsFile->addError($error, $stackPtr);
        break;

        default:
          $beforePtr = $phpcsFile->findPrevious(T_WHITESPACE, $stackPtr - 1, NULL, TRUE);
          $afterPtr  = $phpcsFile->findNext(T_WHITESPACE, $stackPtr + 1, NULL, TRUE);
          if($tokens[$afterPtr]['type'] == 'T_VARIABLE') {
            switch($tokens[$beforePtr]['type']) {
              case 'T_STRING':
                $beforePtr = $phpcsFile->findPrevious(T_WHITESPACE, $beforePtr - 1, NULL, TRUE);
                if($tokens[$beforePtr]['type'] == 'T_OBJECT_OPERATOR') {
                  break;
                }
              case 'T_FALSE':
              case 'T_TRUE':
              case 'T_NULL':
                $error = 'Variables should precede constants in comparison operations';
                $phpcsFile->addError($error, $stackPtr);
              break;
            }
          }
        break;
      }
    }
  }
