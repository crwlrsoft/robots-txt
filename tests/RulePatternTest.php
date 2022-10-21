<?php

declare(strict_types=1);

use Crwlr\RobotsTxt\RulePattern;
use PHPUnit\Framework\TestCase;

final class RulePatternTest extends TestCase
{
    /**
     * @return Generator&iterable<string[]> [$pattern, $uri]
     */
    public function validPatternProvider(): Generator
    {
        yield 'Exact match'                                   => ['/home',              '/home'];
        yield 'Partial match'                                 => ['/jobs/controlling/', '/company/jobs/controlling/controller-f-m-x-2021-10-23'];
        yield 'Single wildcard in pattern'                    => ['/foo/*/bar',         '/foo/yo/bar'];
        yield 'Multiple wildcard in pattern'                  => ['/foo/*/bar*/baz',    '/foo/exa/mple/barbara/pew/pew/baz'];
        yield 'URI that has percent encoded ASCII characters' => ['/foo/bar',           '/foo/b%61r'];
    }

    /**
     * @dataProvider validPatternProvider
     */
    public function test_valid_patterns(string $pattern, string $uri): void
    {
        $this->assertTrue((new RulePattern($pattern))->matches($uri));
    }

    public function test_it_returns_the_raw_pattern(): void
    {
        $pattern = new RulePattern('/fo%6F/*/baz$');
        $this->assertEquals('/fo%6F/*/baz$', $pattern->pattern());
    }

    public function test_return_false_when_uri_does_not_match(): void
    {
        $this->assertFalse((new RulePattern('/home'))->matches('/contact'));
    }

    public function test_match_ends_with(): void
    {
        $pattern = new RulePattern('/foo/bar$');
        $this->assertTrue($pattern->matches('/foo/bar'));
        $this->assertFalse($pattern->matches('/foo/bar/baz'));
    }
}
