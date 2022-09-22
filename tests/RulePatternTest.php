<?php

declare(strict_types=1);

use Crwlr\RobotsTxt\RulePattern;
use PHPUnit\Framework\TestCase;

final class RulePatternTest extends TestCase
{
    public function test_it_returns_the_raw_pattern(): void
    {
        $pattern = new RulePattern('/fo%6F/*/baz$');
        $this->assertEquals('/fo%6F/*/baz$', $pattern->pattern());
    }

    public function test_match_an_exact_match(): void
    {
        $this->assertTrue((new RulePattern('/home'))->matches('/home'));
    }

    public function test_return_false_when_uri_does_not_match(): void
    {
        $this->assertFalse((new RulePattern('/home'))->matches('/contact'));
    }

    public function test_match_a_partial_match(): void
    {
        $pattern = new RulePattern('/jobs/controlling/');
        $this->assertTrue($pattern->matches('/company/jobs/controlling/controller-f-m-x-2021-10-23'));
    }

    public function test_match_with_wildcard_in_pattern(): void
    {
        $pattern = new RulePattern('/foo/*/bar');
        $this->assertTrue($pattern->matches('/foo/yo/bar'));
    }

    public function test_match_with_multiple_wildcards_in_pattern(): void
    {
        $pattern = new RulePattern('/foo/*/bar*/baz');
        $this->assertTrue($pattern->matches('/foo/exa/mple/barbara/pew/pew/baz'));
    }

    public function test_match_ends_with(): void
    {
        $pattern = new RulePattern('/foo/bar$');
        $this->assertTrue($pattern->matches('/foo/bar'));
        $this->assertFalse($pattern->matches('/foo/bar/baz'));
    }

    public function test_match_with_uri_that_has_percent_encoded_ascii_characters(): void
    {
        $pattern = new RulePattern('/foo/bar');
        $this->assertTrue($pattern->matches('/foo/b%61r'));
    }
}
