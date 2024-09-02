<?php

declare(strict_types=1);

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Input;

use PHPUnit\Framework\TestCase;
use Rollerworks\Component\Search\Exception\StringLexerException;
use Rollerworks\Component\Search\Input\StringLexer;

/**
 * @internal
 */
final class StringLexerTest extends TestCase
{
    /** @var StringLexer */
    private $lexer;

    /** @before */
    public function setUpLexer(): void
    {
        $this->lexer = new StringLexer();
    }

    /** @test */
    public function it_skips_all_whitespace_at_the_beginning(): void
    {
        $this->lexer->parse("   \nhe:\n there;");

        self::assertTrue($this->lexer->isGlimpse('/he:/A'));
    }

    /** @test */
    public function skips_whitespace_with_exception_of_new_lines(): void
    {
        $this->lexer->parse("he:\n there;");

        $this->lexer->moveCursor('he:');
        $this->lexer->skipWhitespace();

        self::assertTrue($this->lexer->isGlimpse("/\n/A"));
    }

    /** @test */
    public function it_reports_the_correct_col(): void
    {
        $this->lexer->parse("he:");
        $this->lexer->fieldIdentification();

        $this->expectExceptionObject(StringLexerException::syntaxErrorUnexpectedEnd(3, 1, 'StringValue', 'end of string'));

        $this->lexer->stringValue();
    }

    /** @test */
    public function it_reports_the_correct_col_with_multiline(): void
    {
        $this->lexer->parse("he:\nid:");
        $this->lexer->fieldIdentification();
        $this->lexer->skipEmptyLines();

        $this->lexer->fieldIdentification();

        $this->expectExceptionObject(StringLexerException::syntaxErrorUnexpectedEnd(4, 2, 'StringValue', 'end of string'));

        $this->lexer->stringValue();
    }

    /** @test */
    public function it_reports_the_correct_col_when_start_at_newline(): void
    {
        $this->lexer->parse("he:\n");
        $this->lexer->fieldIdentification();
        $this->lexer->skipEmptyLines();

        $this->expectExceptionObject(StringLexerException::syntaxErrorUnexpectedEnd(1, 2, 'StringValue', 'end of string'));

        $this->lexer->stringValue();
    }
}
