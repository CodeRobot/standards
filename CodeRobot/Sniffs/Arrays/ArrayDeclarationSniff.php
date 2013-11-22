<?php


  /**
   * A test to ensure that arrays conform to the array coding standard
   *
   * @package PHP_CodeSniffer
   */
  class CodeRobot_Sniffs_Arrays_ArrayDeclarationSniff implements PHP_CodeSniffer_Sniff {


    /**
     * Returns an array of tokens this test wants to listen for
     *
     * @return array
     */
    public function register() {
      return array(T_ARRAY);
    }


    /**
     * Processes this sniff, when one of its tokens is encountered
     *
     * @param PHP_CodeSniffer_File $phpcsFile The current file being checked
     * @param integer              $stackPtr  The position of the current token in the stack passed in $tokens
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr) {
      $tokens = $phpcsFile->getTokens();

      // Array keyword should be lower case.
      if (strtolower($tokens[$stackPtr]['content']) !== $tokens[$stackPtr]['content']) {
        $error = 'Array keyword should be lower case; expected "array" but found "' . $tokens[$stackPtr]['content'] . '"';
        $phpcsFile->addError($error, $stackPtr);
      }

      // Back up through the tokens on this line to find out where the line starts.
      $t = $stackPtr;
      while ($tokens[$t]['line'] == $tokens[$stackPtr]['line']) {
        // This finds the last non-whitespace character on the line
        if ($tokens[$t]['code'] != T_WHITESPACE) $line_start = $tokens[$t]['column'];
        $t--;
      }

      $arrayStart   = $tokens[$stackPtr]['parenthesis_opener'];
      $arrayEnd     = $tokens[$arrayStart]['parenthesis_closer'];
      $keywordStart = $tokens[$stackPtr]['column'];

      if ($arrayStart != ($stackPtr + 1)) {
        $error = 'There must be no space between the keyword and the opening parenthesis';
        $phpcsFile->addError($error, $stackPtr);
      }

      // Check for empty arrays.
      $content = $phpcsFile->findNext(array(T_WHITESPACE), ($arrayStart + 1), ($arrayEnd + 1), TRUE);
      if ($content === $arrayEnd) {
        // Empty array, but if the brackets aren't together, there's a problem.
        if (($arrayEnd - $arrayStart) !== 1) {
          $error = 'Empty array declaration must have no space between the parentheses';
          $phpcsFile->addError($error, $stackPtr);

          // We can return here because there is nothing else to check. All code
          // below can assume that the array is not empty.
          return;
        }
      }

      if ($tokens[$arrayStart]['line'] === $tokens[$arrayEnd]['line']) {
        // Single line array.
        // Check if there are multiple values. If so, then it has to be multiple lines
        // unless it is contained inside a function call or condition.
        $nextComma  = $arrayStart;
        $valueCount = 0;
        $commas     = array();
        while (($nextComma = $phpcsFile->findNext(array(T_COMMA), ($nextComma + 1), $arrayEnd)) !== FALSE) {
          $valueCount++;
          $commas[] = $nextComma;
        }

        // Now check each of the double arrows (if any).
        $nextArrow = $arrayStart;
        while (($nextArrow = $phpcsFile->findNext(T_DOUBLE_ARROW, ($nextArrow + 1), $arrayEnd)) !== FALSE) {
          if ($tokens[($nextArrow - 1)]['code'] !== T_WHITESPACE) {
            $content = $tokens[($nextArrow - 1)]['content'];
            $error   = "Expected 1 space between \"$content\" and double arrow; 0 found";
            $phpcsFile->addError($error, $nextArrow);
          } else {
            $spaceLength = strlen($tokens[($nextArrow - 1)]['content']);
            if ($spaceLength !== 1) {
              $content = $tokens[($nextArrow - 2)]['content'];
              $error   = "Expected 1 space between \"$content\" and double arrow; $spaceLength found";
              $phpcsFile->addError($error, $nextArrow);
            }
          }

          if ($tokens[($nextArrow + 1)]['code'] !== T_WHITESPACE) {
            $content = $tokens[($nextArrow + 1)]['content'];
            $error   = "Expected 1 space between double arrow and \"$content\"; 0 found";
            $phpcsFile->addError($error, $nextArrow);
          } else {
            $spaceLength = strlen($tokens[($nextArrow + 1)]['content']);
            if ($spaceLength !== 1) {
              $content = $tokens[($nextArrow + 2)]['content'];
              $error   = 'Expected 1 space between double arrow and "' . $content . '"; ' . $spaceLength . ' found';
              $phpcsFile->addError($error, $nextArrow);
            }
          }
        }

        if ($valueCount > 0) {
          $conditionCheck = $phpcsFile->findPrevious(array(T_OPEN_PARENTHESIS, T_SEMICOLON), ($stackPtr - 1), NULL, FALSE);

          if (($conditionCheck === FALSE) || ($tokens[$conditionCheck]['line'] !== $tokens[$stackPtr]['line'])) {
            $error = 'Array with multiple values cannot be declared on a single line';
            $phpcsFile->addError($error, $stackPtr);
            return;
          }

          // We have a multiple value array that is inside a condition or
          // function. Check its spacing is correct.
          foreach ($commas as $comma) {
            if ($tokens[($comma + 1)]['code'] !== T_WHITESPACE) {
              $content = $tokens[($comma + 1)]['content'];
              $error   = "Expected 1 space between comma and \"$content\"; 0 found";
              $phpcsFile->addError($error, $comma);
            } else {
              $spaceLength = strlen($tokens[($comma + 1)]['content']);
              if ($spaceLength !== 1) {
                $content = $tokens[($comma + 2)]['content'];
                $error   = "Expected 1 space between comma and \"$content\"; $spaceLength found";
                $phpcsFile->addError($error, $comma);
              }
            }

            if ($tokens[($comma - 1)]['code'] === T_WHITESPACE) {
              $content     = $tokens[($comma - 2)]['content'];
              $spaceLength = strlen($tokens[($comma - 1)]['content']);
              $error       = "Expected 0 spaces between \"$content\" and comma; $spaceLength found";
              $phpcsFile->addError($error, $comma);
            }
          }
        }

        return;
      }

      // Check the closing bracket is on a new line.
      $lastContent = $phpcsFile->findPrevious(array(T_WHITESPACE), ($arrayEnd - 1), $arrayStart, TRUE);
      if ($tokens[$lastContent]['line'] !== ($tokens[$arrayEnd]['line'] - 1)) {
        $error = 'Closing parenthesis of array declaration must be on a new line immediately after the array';
        $phpcsFile->addError($error, $arrayEnd);
      } else if ($tokens[$arrayEnd]['column'] !== $line_start) {
        // Check the closing bracket is lined up under the first non-whitespace character in the opening line
        $expected  = $keywordStart;
        $expected .= ($keywordStart === 0) ? ' space' : ' spaces';
        $found     = $tokens[$arrayEnd]['column'];
        $found    .= ($found === 0) ? ' space' : ' spaces';
        $phpcsFile->addError('Closing parenthesis not aligned correctly; expected ' . $expected . ' but found ' . $found, $arrayEnd);
      }

      $nextToken  = $stackPtr;
      $lastComma  = $stackPtr;
      $keyUsed    = FALSE;
      $singleUsed = FALSE;
      $lastToken  = '';
      $indices    = array();
      $maxLength  = 0;

      // Find all the double arrows that reside in this scope.
      while (($nextToken = $phpcsFile->findNext(array(T_DOUBLE_ARROW, T_COMMA, T_ARRAY), ($nextToken + 1), $arrayEnd)) !== FALSE) {
        $currentEntry = array();

        if ($tokens[$nextToken]['code'] === T_ARRAY) {
          // Let subsequent calls of this test handle nested arrays.
          $indices[] = array(
            'value' => $nextToken
          );
          $nextToken = $tokens[$tokens[$nextToken]['parenthesis_opener']]['parenthesis_closer'];
          continue;
        }

        if ($tokens[$nextToken]['code'] === T_COMMA) {
          $stackPtrCount = 0;
          if (isset($tokens[$stackPtr]['nested_parenthesis']) === TRUE) {
            $stackPtrCount = count($tokens[$stackPtr]['nested_parenthesis']);
          }

          if (count($tokens[$nextToken]['nested_parenthesis']) > ($stackPtrCount + 1)) {
            // This comma is inside more parenthesis than the ARRAY keyword,
            // then there it is actually a comma used to separate arguments
            // in a function call.
            continue;
          }

          if ($keyUsed === TRUE && $lastToken === T_COMMA) {
            $error = 'No key specified for array entry; first entry specifies key';
            $phpcsFile->addError($error, $nextToken);
            return;
          }

          if ($keyUsed === FALSE) {
            if ($tokens[($nextToken - 1)]['code'] === T_WHITESPACE) {
              $content     = $tokens[($nextToken - 2)]['content'];
              $spaceLength = strlen($tokens[($nextToken - 1)]['content']);
              $error       = "Expected 0 spaces between \"$content\" and comma; $spaceLength found";
              $phpcsFile->addError($error, $nextToken);
            }

            // Find the value, which will be the first token on the line,
            // excluding the leading whitespace.
            $valueContent = $phpcsFile->findPrevious(PHP_CodeSniffer_Tokens::$emptyTokens, ($nextToken - 1), NULL, TRUE);
            while ($tokens[$valueContent]['line'] === $tokens[$nextToken]['line']) {
              if ($valueContent === $arrayStart) {
                // Value must have been on the same line as the array
                // parenthesis, so we have reached the start of the value.
                break;
              }

              $valueContent--;
            }

            $valueContent = $phpcsFile->findNext(T_WHITESPACE, ($valueContent + 1), $nextToken, TRUE);
            $indices[]    = array('value' => $valueContent);
            $singleUsed   = TRUE;
          }

          $lastToken = T_COMMA;
          continue;
        }

        if ($tokens[$nextToken]['code'] === T_DOUBLE_ARROW) {
          if ($singleUsed === TRUE) {
            $error = 'Key specified for array entry; first entry has no key';
            $phpcsFile->addError($error, $nextToken);
            return;
          }

          $currentEntry['arrow'] = $nextToken;
          $keyUsed               = TRUE;

          // Find the start of index that uses this double arrow.
          $indexEnd   = $phpcsFile->findPrevious(T_WHITESPACE, ($nextToken - 1), $arrayStart, TRUE);
          $indexStart = $phpcsFile->findPrevious(T_WHITESPACE, $indexEnd, $arrayStart);

          if ($indexStart === FALSE) {
            $index = $indexEnd;
          } else {
            $index = ($indexStart + 1);
          }

          $currentEntry['index']         = $index;
          $currentEntry['index_content'] = $phpcsFile->getTokensAsString($index, ($indexEnd - $index + 1));

          $indexLength = strlen($currentEntry['index_content']);
          if ($maxLength < $indexLength) {
            $maxLength = $indexLength;
          }

          // Find the value of this index.
          $nextContent           = $phpcsFile->findNext(array(T_WHITESPACE), ($nextToken + 1), $arrayEnd, TRUE);
          $currentEntry['value'] = $nextContent;
          $indices[]             = $currentEntry;
          $lastToken             = T_DOUBLE_ARROW;
        }
      }

      // Check for mutli-line arrays that should be single-line.
      $singleValue = FALSE;

      if (empty($indices) === TRUE) {
        $singleValue = TRUE;
      } else if (count($indices) === 1) {
        if ($lastToken === T_COMMA) {
          // There may be another array value without a comma.
          $exclude     = PHP_CodeSniffer_Tokens::$emptyTokens;
          $exclude[]   = T_COMMA;
          $nextContent = $phpcsFile->findNext($exclude, ($indices[0]['value'] + 1), $arrayEnd, TRUE);
          if ($nextContent === FALSE) {
            $singleValue = TRUE;
          }
        }

        if ($singleValue === FALSE && isset($indices[0]['arrow']) === FALSE) {
          // A single nested array as a value is fine.
          if ($tokens[$indices[0]['value']]['code'] !== T_ARRAY) {
            $singleValue === TRUE;
          }
        }
      }

      if ($keyUsed === FALSE && empty($indices) === FALSE) {
        $count     = count($indices);
        $lastIndex = $indices[($count - 1)]['value'];

        $trailingContent = $phpcsFile->findPrevious(T_WHITESPACE, ($arrayEnd - 1), $lastIndex, TRUE);
        if ($tokens[$trailingContent]['code'] == T_COMMA) {
          $error = 'Comma not allowed after last value in array declaration';
          $phpcsFile->addError($error, $trailingContent);
        }

        foreach ($indices as $value) {
          if (empty($value['value']) === TRUE) {
            // Array was malformed and we couldn't figure out
            // the array value correctly, so we have to ignore it.
            // Other parts of this sniff will correct the error.
            continue;
          }

          if ($tokens[($value['value'] - 1)]['code'] === T_WHITESPACE) {
            // A whitespace token before this value means that the value
            // was indented and not flush with the opening parenthesis.
            if ($tokens[$value['value']]['column'] !== ($line_start + 2)) {
              $error = 'Array value not aligned correctly; expected ' . ($line_start + 2) .
                       ' spaces but found ' . $tokens[$value['value']]['column'];
              $phpcsFile->addError($error, $value['value']);
            }
          }
        }
      }

      $numValues = count($indices);

      $indicesStart = ($keywordStart + 1);
      $arrowStart   = ($indicesStart + $maxLength + 1);
      $valueStart   = ($arrowStart + 3);
      foreach ($indices as $index) {
        if (isset($index['index']) === FALSE) {
          // Array value only.
          if (($tokens[$index['value']]['line'] === $tokens[$stackPtr]['line']) && ($numValues > 1)) {
            $phpcsFile->addError('The first value in a multi-value array must be on a new line', $stackPtr);
          }

          continue;
        }

        if (($tokens[$index['index']]['line'] === $tokens[$stackPtr]['line'])) {
          $phpcsFile->addError('The first index in a multi-value array must be on a new line', $stackPtr);
          continue;
        }

        if ($tokens[$index['index']]['column'] !== $line_start + 2) {
          $phpcsFile->addError(
            'Array key not aligned correctly; expected ' . ($line_start + 2) .
            ' spaces but found ' . $tokens[$index['index']]['column'], $index['index']
          );
          continue;
        }

        // Check each line ends in a comma.
        if ($tokens[$index['value']]['code'] !== T_ARRAY) {
          $nextComma = $phpcsFile->findNext(array(T_COMMA), ($index['value'] + 1));

          // Check that there is no space before the comma.
          if ($nextComma !== FALSE && $tokens[($nextComma - 1)]['code'] === T_WHITESPACE) {
            $content     = $tokens[($nextComma - 2)]['content'];
            $spaceLength = strlen($tokens[($nextComma - 1)]['content']);
            $error       = "Expected 0 spaces between \"$content\" and comma; $spaceLength found";
            $phpcsFile->addError($error, $nextComma);
          }
        }
      }
    }


  }