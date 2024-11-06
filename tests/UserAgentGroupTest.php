<?php

declare(strict_types=1);

use Crwlr\RobotsTxt\RulePattern;
use Crwlr\RobotsTxt\UserAgentGroup;
use PHPUnit\Framework\TestCase;

final class UserAgentGroupTest extends TestCase
{
    private UserAgentGroup $userAgentGroup;

    protected function setUp(): void
    {
        $this->userAgentGroup = new UserAgentGroup(['*']);
    }

    /**
     * @return Generator&iterable<array<string[]|string>> [$userAgents[], $contains]
     */
    public function containedUserAgentProvider(): Generator
    {
        yield 'Exact match'                => [['GoogleBot', 'FooBot', 'CrwlrBot',], 'FooBot'];
        yield 'Case insensitive uppercase' => [['GoogleBot', 'FooBot', 'CrwlrBot',], 'foobot'];
        yield 'Case insensitive lowercase' => [['GoogleBot', 'FooBot', 'CrwlrBot',], 'FOOBOT'];
        yield 'Wildcard'                   => [['*',         'barbot',            ], 'foobot'];
    }

    /**
     * @return Generator&iterable<string[],string,string> [$disallowedPatterns[], $allowedPattern, $uri]
     */
    public function allowedRulePatternProvider(): Generator
    {
        yield 'No matching rules'   => [['/admin', '/secret', ], '/secret/notsosecret', '/contact'];
        yield 'Only matching rules' => [['/admin',            ], '/home',               'home'];
        yield 'More specific URI'   => [['/secret',           ], '/secret/notsosecret', '/secret/notsosecret/something'];
        yield 'Equivalent rules'    => [['/foo',              ], '/bar',                '/foo/bar'];
    }

    /**
     * @return Generator&iterable<string[]> [$disallowedPatterns, $allowedPattern, $uri]
     */
    public function notAllowedRulePatternProvider(): Generator
    {
        yield 'Only matching dissallowed rule' => ['/admin',                      '/home',   '/admin'];
        yield 'More specific rules'            => ['/secret/of-the-yaya-sisters', '/secret', '/secret/of-the-yaya-sisters'];
    }

    /**
     * @dataProvider containedUserAgentProvider
     *
     * @param string[] $userAgents
     */
    public function test_contained_user_agent(array $userAgents, string $contains): void
    {
        $this->assertTrue((new UserAgentGroup($userAgents))->contains($contains));
    }

    /**
     * @dataProvider allowedRulePatternProvider
     *
     * @param string[] $disallowedPatterns
     */
    public function test_is_allowed(array $disallowedPatterns, string $allowedPattern, string $uri): void
    {
        array_walk(
            $disallowedPatterns,
            fn(string $pattern) => $this->userAgentGroup->addDisallowedPattern(new RulePattern($pattern)),
        );
        $this->userAgentGroup->addAllowedPattern(new RulePattern($allowedPattern));
        $this->assertTrue($this->userAgentGroup->isAllowed($uri));
    }

    /**
     * @dataProvider notAllowedRulePatternProvider
     */
    public function test_is_not_allowed(string $disallowedPatterns, string $allowedPattern, string $uri): void
    {
        $this->userAgentGroup->addDisallowedPattern(new RulePattern($disallowedPatterns));
        $this->userAgentGroup->addAllowedPattern(new RulePattern($allowedPattern));
        $this->assertFalse($this->userAgentGroup->isAllowed($uri));
    }

    public function test_throws_exception_when_constructor_argument_array_contains_non_string_value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        /* @phpstan-ignore-next-line  We're just testing that an Exception is thrown in that case */
        new UserAgentGroup(['foo', 'bar', 123]);
    }

    public function test_adding_a_disallow_rule_pattern(): void
    {
        $rulePattern = new RulePattern('/foo/bar');
        $this->userAgentGroup->addDisallowedPattern($rulePattern);
        $this->assertEquals($rulePattern, $this->userAgentGroup->disallowedPatterns()[0]);
    }

    public function test_adding_an_allow_rule_pattern(): void
    {
        $rulePattern = new RulePattern('/foo/bar');
        $this->userAgentGroup->addAllowedPattern($rulePattern);
        $this->assertEquals($rulePattern, $this->userAgentGroup->allowedPatterns()[0]);
    }

    public function test_is_allowed_with_no_rule_at_all(): void
    {
        $this->assertTrue($this->userAgentGroup->isAllowed('/foo/bar'));
    }
}
