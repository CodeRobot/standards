<?php


  /**
   * Checks the for unused function parameters
   *
   * This sniff checks that all function parameters are used in the function body.
   * One exception is made for empty function bodies or function bodies that only
   * contain comments. This could be usefull for the classes that implement an
   * interface that defines multiple methods but the implementation only needs some
   * of them.
   *
   * @package PHP_CodeSniffer
   */
  class CodeRobot_Sniffs_CodeAnalysis_UnusedFunctionParameterSniff implements PHP_CodeSniffer_Sniff {


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
      $token  = $tokens[$stackPtr];

      // Skip broken function declarations.
      if(isset($token['scope_opener']) === FALSE || isset($token['parenthesis_opener']) === FALSE) {
        return;
      }

      $params = array();
      foreach($phpcsFile->getMethodParameters($stackPtr) as $param) {
        $params[$param['name']] = $stackPtr;
      }

      $next = ++$token['scope_opener'];
      $end  = --$token['scope_closer'];

      $emptyBody = TRUE;

      for(; $next <= $end; ++$next) {
        $token = $tokens[$next];
        $code  = $token['code'];

        // Ignorable tokens.
        if(in_array($code, PHP_CodeSniffer_Tokens::$emptyTokens) === TRUE) {
          continue;
        } else if($code === T_THROW && $emptyBody === TRUE) {
          // Throw statement and an empty body indicate an interface method.
          return;
        } else if($code === T_RETURN && $emptyBody === TRUE) {
          // Return statement and an empty body indicate an interface method.
          $tmp = $phpcsFile->findNext(PHP_CodeSniffer_Tokens::$emptyTokens, ($next + 1), NULL, TRUE);
          if($tmp === FALSE) {
            return;
          }

          // There is a return.
          if($tokens[$tmp] === T_SEMICOLON) {
            return;
          }

          $tmp = $phpcsFile->findNext(PHP_CodeSniffer_Tokens::$emptyTokens, ($tmp + 1), NULL, TRUE);

          // There is a return <token>.
          if($tmp !== FALSE && $tokens[$tmp] === T_SEMICOLON) {
            return;
          }
        }

        $emptyBody = FALSE;

        if($code === T_VARIABLE && isset($params[$token['content']]) === TRUE) {
          unset($params[$token['content']]);
        } else if($code === T_DOUBLE_QUOTED_STRING) {
          // Tokenize double quote string.
          $strTokens = token_get_all(sprintf('<?php %s;?>', $token['content']));

          foreach($strTokens as $tok) {
            if(is_array($tok) === FALSE || $tok[0] !== T_VARIABLE ) {
              continue;
            }

            if(isset($params[$tok[1]]) === TRUE) {
              unset($params[$tok[1]]);
            }
          }
        }
      }

      if($emptyBody === FALSE && count($params) > 0) {
        foreach($params as $paramName => $position) {
          $error = 'The method parameter ' . $paramName . ' is never used';
          $phpcsFile->addWarning($error, $position);
        }
      }

    }


  }