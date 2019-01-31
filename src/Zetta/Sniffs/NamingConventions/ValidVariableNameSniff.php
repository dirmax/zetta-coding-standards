<?php
namespace Zetta\Sniffs\NamingConventions;

use PHP_CodeSniffer\Standards\Squiz\Sniffs\NamingConventions\ValidVariableNameSniff as BaseValidVariableNameSniff;

use PHP_CodeSniffer\Util\Common;
use PHP_CodeSniffer\Files\File;

class ValidVariableNameSniff extends BaseValidVariableNameSniff
{
    protected function processVariable(File $phpcsFile, $stackPtr)
    {
        $tokens  = $phpcsFile->getTokens();
        $varName = ltrim($tokens[$stackPtr]['content'], '$');

        // If it's a php reserved var, then its ok.
        if (isset($this->phpReservedVars[$varName]) === true) {
            return;
        }

        $objOperator = $phpcsFile->findNext([T_WHITESPACE], ($stackPtr + 1), null, true);
        if ($tokens[$objOperator]['code'] === T_OBJECT_OPERATOR) {
            // Check to see if we are using a variable from an object.
            $var = $phpcsFile->findNext([T_WHITESPACE], ($objOperator + 1), null, true);
            if ($tokens[$var]['code'] === T_STRING) {
                $bracket = $phpcsFile->findNext([T_WHITESPACE], ($var + 1), null, true);
                if ($tokens[$bracket]['code'] !== T_OPEN_PARENTHESIS) {
                    $objVarName = $tokens[$var]['content'];

                    // There is no way for us to know if the var is public or
                    // private, so we have to ignore a leading underscore if there is
                    // one and just check the main part of the variable name.
                    $originalVarName = $objVarName;
                    if (substr($objVarName, 0, 1) === '_') {
                        $objVarName = substr($objVarName, 1);
                    }

                    if (Common::isCamelCaps($objVarName, false, true, false) === false) {
                        $error = 'Property "%s" is not in valid camel caps format';
                        $data  = [$originalVarName];
                        $phpcsFile->addError($error, $var, 'PropertyNotCamelCaps', $data);
                    }
                }//end if
            }//end if
        }//end if

        // There is no way for us to know if the var is public or private,
        // so we have to ignore a leading underscore if there is one and just
        // check the main part of the variable name.
        $originalVarName = $varName;
        if (substr($varName, 0, 1) === '_') {
            $objOperator = $phpcsFile->findPrevious([T_WHITESPACE], ($stackPtr - 1), null, true);
            if ($tokens[$objOperator]['code'] === T_DOUBLE_COLON) {
                // The variable lives within a class, and is referenced like
                // this: MyClass::$_variable, so we don't know its scope.
                $inClass = true;
            } else {
                $inClass = $phpcsFile->hasCondition($stackPtr, [T_CLASS, T_INTERFACE, T_TRAIT]);
            }

            if ($inClass === true) {
                $varName = substr($varName, 1);
            }
        }

        if (Common::isCamelCaps($varName, false, true, false) === false) {
            $error = 'Variable "%s" is not in valid camel caps format';
            $data  = [$originalVarName];
            $phpcsFile->addError($error, $stackPtr, 'NotCamelCaps', $data);
        }

    }
}
