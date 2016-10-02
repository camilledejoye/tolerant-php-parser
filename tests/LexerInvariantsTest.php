<?php
// TODO autoload classes
require_once(__DIR__ . "/../lexer.php");
require_once(__DIR__ . "/../Token.php");

use PHPUnit\Framework\TestCase;
use PhpParser\TokenKind;

class LexerInvariantsTest extends TestCase {
    // TODO test w/ multiple files
    const FILENAMES = array (
        __dir__ . "/fixtures/testfile.php",
        __dir__ . "/fixtures/commentsFile.php"
    );

    public function testTokenLengthSum() {
        foreach (self::FILENAMES as $filename) {
            $tokensArray = PhpParser\getTokensArray($filename);

            $tokenLengthSum = 0;
            foreach ($tokensArray as $token) {
                $tokenLengthSum += $token->length;
            }

            $this->assertEquals(
                filesize($filename), $tokenLengthSum,
                "Invariant: Sum of the lengths of all the tokens should be equivalent to the length of the document.");
        }
    }

    public function testTokenStartGeqFullStart() {
        foreach (self::FILENAMES as $filename) {
            $tokensArray = PhpParser\getTokensArray($filename);

            foreach ($tokensArray as $token) {
                $this->assertGreaterThanOrEqual(
                    $token->fullStart, $token->start,
                    "Invariant: A token's Start is always >= FullStart.");
            }
        }
    }

    public function testTokenContentMatchesFileSpan() {
        foreach (self::FILENAMES as $filename) {
            $tokensArray = PhpParser\getTokensArray($filename);
            $fileContents = file_get_contents($filename);
            foreach ($tokensArray as $token) {
                $this->assertEquals(
                    substr($fileContents, $token->fullStart, $token->length),
                    $token->getFullTextForToken($fileContents),
                    "Invariant: A token's content exactly matches the range of the file its span specifies"
                );
            }
        }
    }

    public function testTokenFullTextMatchesTriviaPlusText() {
        foreach (self::FILENAMES as $filename) {
            $tokensArray = PhpParser\getTokensArray($filename);
            $fileContents = file_get_contents($filename);
            foreach ($tokensArray as $token) {
                $this->assertEquals(
                    $token->getFullTextForToken($fileContents),
                    $token->getTriviaForToken($fileContents) . $token->getTextForToken($fileContents),
                    "Invariant: FullText of each token matches Trivia plus Text"
                );
            }
        }
    }

    public function testTokenFullTextConcatenationMatchesDocumentText() {
        foreach (self::FILENAMES as $filename) {
            $tokensArray = PhpParser\getTokensArray($filename);
            $fileContents = file_get_contents($filename);

            $tokenFullTextConcatenation = "";
            foreach ($tokensArray as $token) {
                $tokenFullTextConcatenation .= $token->getFullTextForToken($fileContents);
            }

            $this->assertEquals(
                $fileContents,
                $tokenFullTextConcatenation,
                "Invariant: Concatenating FullText of each token returns the document"
            );
        }
    }

    public function testGetTokenFullTextLengthMatchesLength() {
        foreach (self::FILENAMES as $filename) {
            $tokensArray = PhpParser\getTokensArray($filename);
            $fileContents = file_get_contents($filename);

            foreach ($tokensArray as $token) {
                $this->assertEquals(
                    $token->length,
                    strlen($token->getFullTextForToken($fileContents)),
                    "Invariant: a token's FullText length is equivalent to Length"
                );
            }
        }
    }

    public function testTokenTextLengthMatchesLengthMinusStartPlusFullStart() {
        foreach (self::FILENAMES as $filename) {
            $tokensArray = PhpParser\getTokensArray($filename);
            $fileContents = file_get_contents($filename);

            foreach ($tokensArray as $token) {
                $this->assertEquals(
                    $token->length - ($token->start - $token->fullStart),
                    strlen($token->getTextForToken($fileContents)),
                    "Invariant: a token's FullText length is equivalent to Length - (Start - FullStart)"
                );
            }
        }
    }

    public function testTokenTriviaLengthMatchesStartMinusFullStart() {
        foreach (self::FILENAMES as $filename) {
            $tokensArray = PhpParser\getTokensArray($filename);
            $fileContents = file_get_contents($filename);

            foreach ($tokensArray as $token) {
                $this->assertEquals(
                    $token->start - $token->fullStart,
                    strlen($token->getTriviaForToken($fileContents)),
                    "Invariant: a token's Trivia length is equivalent to (Start - FullStart)"
                );
            }
        }
    }

    public function testEOFTokenTextHasZeroLength() {
        foreach (self::FILENAMES as $filename) {
            $tokensArray = PhpParser\getTokensArray($filename);

            $tokenText = $tokensArray[count($tokensArray) - 1]->getTextForToken($filename);
            $this->assertEquals(
                0, strlen($tokenText),
                "Invariant: End-of-file token text should have zero length"
            );
        }
    }

    public function testTokensArrayEndsWithEOFToken() {
        foreach (self::FILENAMES as $filename) {
            $tokensArray = PhpParser\getTokensArray($filename);

            $this->assertEquals(
                $tokensArray[count($tokensArray) - 1]->kind, TokenKind::EndOfFileToken,
                "Invariant: Tokens array should always end with end of file token"
            );
        }
    }

    public function testTokensArrayOnlyContainsExactlyOneEOFToken () {
        foreach (self::FILENAMES as $filename) {
            $tokensArray = PhpParser\getTokensArray($filename);

            $eofTokenCount = 0;

            foreach ($tokensArray as $index => $token) {
                if ($token->kind == TokenKind::EndOfFileToken) {
                    $eofTokenCount++;
                }
            }
            $this->assertEquals(
                1, $eofTokenCount,
                "Invariant: Tokens array should contain exactly one EOF token"
            );
        }
    }

    public function testTokenFullStartBeginsImmediatelyAfterPreviousToken () {
        foreach (self::FILENAMES as $filename) {
            $tokensArray = PhpParser\getTokensArray($filename);

            $prevToken;
            foreach ($tokensArray as $index => $token) {
                if ($index === 0) {
                    $prevToken = $token;
                    continue;
                }

                $this->assertEquals(
                    $prevToken->fullStart + $prevToken->length, $token->fullStart,
                    "Invariant: Token FullStart should begin immediately after previous token end"
                );
                $prevToken = $token;
            }
        }
    }

    public function testWithDifferentEncodings() {
        // TODO test with different encodings
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
}