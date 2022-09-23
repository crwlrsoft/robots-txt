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

    public function test_throws_exception_when_constructor_argument_array_contains_non_string_value(): void
    {
        $this->expectException(InvalidArgumentException::class);
        /* @phpstan-ignore-next-line  We're just testing that an Exception is thrown in that case */
        new UserAgentGroup(['foo', 'bar', 123]);
    }

    public function test_contains_returns_true_when_exact_user_agent_is_contained(): void
    {
        $userAgentGroup = new UserAgentGroup(['GoogleBot', 'FooBot', 'CrwlrBot']);
        $this->assertTrue($userAgentGroup->contains('FooBot'));
    }

    public function test_contains_returns_false_when_user_agent_is_not_contained(): void
    {
        $userAgentGroup = new UserAgentGroup(['GoogleBot', 'FooBot', 'CrwlrBot']);
        $this->assertFalse($userAgentGroup->contains('BarBot'));
    }

    public function test_contains_returns_true_when_user_agent_is_contained_case_insensitive(): void
    {
        $userAgentGroup = new UserAgentGroup(['GoogleBot', 'FooBot', 'CrwlrBot']);
        $this->assertTrue($userAgentGroup->contains('foobot'));

        $userAgentGroup = new UserAgentGroup(['GoogleBot', 'foobot', 'CrwlrBot']);
        $this->assertTrue($userAgentGroup->contains('FOOBOT'));
    }

    public function test_contains_returns_true_when_wildcard_is_in_group(): void
    {
        $userAgentGroup = new UserAgentGroup(['*', 'barbot']);

        $this->assertTrue($userAgentGroup->contains('foobot'));
    }

    public function test_contains_return_false_when_wildcard_is_in_group_but_arg_include_wildcard_is_set_to_false(): void
    {
        $userAgentGroup = new UserAgentGroup(['*', 'barbot']);

        $this->assertFalse($userAgentGroup->contains('foobot', false));
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

    public function test_is_allowed_with_no_matching_rules(): void
    {
        $this->addDisallowedRulePattern('/admin');
        $this->addDisallowedRulePattern('/secret');
        $this->addAllowedRulePattern('/secret/notsosecret');
        $this->assertTrue($this->userAgentGroup->isAllowed('/contact'));
    }

    public function test_is_allowed_with_only_matching_allowed_rule(): void
    {
        $this->addDisallowedRulePattern('/admin');
        $this->addAllowedRulePattern('/home');
        $this->assertTrue($this->userAgentGroup->isAllowed('home'));
    }

    public function test_is_not_allowed_with_only_matching_disallowed_rule(): void
    {
        $this->addDisallowedRulePattern('/admin');
        $this->addAllowedRulePattern('/home');
        $this->assertFalse($this->userAgentGroup->isAllowed('/admin'));
    }

    public function test_is_allowed_with_matching_allowed_and_disallowed_rules_and_allowed_is_more_specific(): void
    {
        $this->addDisallowedRulePattern('/secret');
        $this->addAllowedRulePattern('/secret/notsosecret');
        $this->assertTrue($this->userAgentGroup->isAllowed('/secret/notsosecret/something'));
    }

    public function test_is_not_allowed_with_matching_allowed_and_disallowed_rules_and_disallowed_is_more_specific(): void
    {
        $this->addDisallowedRulePattern('/secret/of-the-yaya-sisters');
        $this->addAllowedRulePattern('/secret');
        $this->assertFalse($this->userAgentGroup->isAllowed('/secret/of-the-yaya-sisters'));
    }

    public function test_is_allowed_with_matching_allowed_and_disallowed_rules_and_both_are_equivalent(): void
    {
        $this->addDisallowedRulePattern('/foo');
        $this->addAllowedRulePattern('/bar');
        $this->assertTrue($this->userAgentGroup->isAllowed('/foo/bar'));
    }


    private function addDisallowedRulePattern(string $pattern): void
    {
        $this->userAgentGroup->addDisallowedPattern(new RulePattern($pattern));
    }

    private function addAllowedRulePattern(string $pattern): void
    {
        $this->userAgentGroup->addAllowedPattern(new RulePattern($pattern));
    }
}
