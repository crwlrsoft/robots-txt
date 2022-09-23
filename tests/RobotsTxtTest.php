<?php

declare(strict_types=1);

use Crwlr\RobotsTxt\RobotsTxt;
use Crwlr\RobotsTxt\RulePattern;
use Crwlr\RobotsTxt\UserAgentGroup;
use PHPUnit\Framework\TestCase;

final class RobotsTxtTest extends TestCase
{
    public function testInstantiationWithEmptyArray(): void
    {
        $robotsTxt = new RobotsTxt([]);
        $this->assertEquals([], $robotsTxt->groups());
    }

    public function testInstantiationWithArrayWithInstancesOfUserAgentGroup(): void
    {
        $userAgentGroup1 = new UserAgentGroup(['*']);
        $userAgentGroup2 = new UserAgentGroup(['ExampleBot']);
        $robotsTxt = new RobotsTxt([$userAgentGroup1, $userAgentGroup2]);
        $this->assertEquals([$userAgentGroup1, $userAgentGroup2], $robotsTxt->groups());
    }

    public function testInstantiationWithArrayContainingSomethingElseThanUserAgentGroup(): void
    {
        $userAgentGroup1 = new UserAgentGroup(['*']);
        $userAgentGroup2 = new stdClass();
        $this->expectException(InvalidArgumentException::class);
        /* @phpstan-ignore-next-line  we're just testing that an Exception is thrown */
        new RobotsTxt([$userAgentGroup1, $userAgentGroup2]);
    }

    public function testMatchingDisallowedRuleWithinNonMatchingGroup(): void
    {
        $nonMatchingGroup = new UserAgentGroup(['FooBot']);
        $nonMatchingGroup->addDisallowedPattern(new RulePattern('/foo/bar'));
        $robotsTxt = new RobotsTxt([$nonMatchingGroup]);

        $this->assertTrue($robotsTxt->isAllowed('/foo/bar', 'BarBot'));
    }

    public function testMatchingDisallowedRuleWithinMatchingGroup(): void
    {
        $matchingGroup = new UserAgentGroup(['FooBot']);
        $matchingGroup->addDisallowedPattern(new RulePattern('/foo/bar'));
        $robotsTxt = new RobotsTxt([$matchingGroup]);

        $this->assertFalse($robotsTxt->isAllowed('/foo/bar', 'FooBot'));
    }

    public function testMatchingDisallowedRuleWithinMatchingGroupAndLessSpecificAllowedRuleWithinSameMatchingGroup(): void
    {
        $matchingGroup = new UserAgentGroup(['FooBot']);
        $matchingGroup->addDisallowedPattern(new RulePattern('/foo/bar'));
        $matchingGroup->addAllowedPattern(new RulePattern('/foo'));
        $robotsTxt = new RobotsTxt([$matchingGroup]);

        $this->assertFalse($robotsTxt->isAllowed('/foo/bar', 'FooBot'));
    }

    public function testMatchingDisallowedRuleWithinMatchingGroupAndLessSpecificAllowedRuleWithinOtherMatchingGroup(): void
    {
        $matchingGroup = new UserAgentGroup(['FooBot']);
        $matchingGroup->addDisallowedPattern(new RulePattern('/foo/bar'));
        $otherMatchingGroup = new UserAgentGroup(['*']);
        $otherMatchingGroup->addAllowedPattern(new RulePattern('/foo'));
        $robotsTxt = new RobotsTxt([$matchingGroup, $otherMatchingGroup]);

        $this->assertFalse($robotsTxt->isAllowed('/foo/bar', 'FooBot'));
    }

    public function testMatchingDisallowedRuleWithinMatchingGroupAndMoreSpecificAllowedRuleWithinSameMatchingGroup(): void
    {
        $matchingGroup = new UserAgentGroup(['FooBot']);
        $matchingGroup->addDisallowedPattern(new RulePattern('/foo'));
        $matchingGroup->addAllowedPattern(new RulePattern('/foo/bar'));
        $robotsTxt = new RobotsTxt([$matchingGroup]);

        $this->assertTrue($robotsTxt->isAllowed('/foo/bar', 'FooBot'));
    }

    public function testMatchingDisallowedRuleWithinMatchingGroupAndMoreSpecificAllowedRuleWithinOtherMatchingGroup(): void
    {
        $matchingGroup = new UserAgentGroup(['FooBot']);
        $matchingGroup->addDisallowedPattern(new RulePattern('/foo'));
        $otherMatchingGroup = new UserAgentGroup(['*']);
        $otherMatchingGroup->addAllowedPattern(new RulePattern('/foo/bar'));
        $robotsTxt = new RobotsTxt([$matchingGroup, $otherMatchingGroup]);

        $this->assertTrue($robotsTxt->isAllowed('/foo/bar', 'FooBot'));
    }

    public function testMatchingDisallowedRuleAndMoreSpecificMatchingAllowedRuleButAlsoEvenMoreSpecificOtherMatchingDisallowedRuleWithinSameGroup(): void
    {
        $matchingGroup = new UserAgentGroup(['FooBot']);
        $matchingGroup->addDisallowedPattern(new RulePattern('/foo'));
        $matchingGroup->addAllowedPattern(new RulePattern('/foo/ba'));
        $matchingGroup->addDisallowedPattern(new RulePattern('/foo/bar'));
        $robotsTxt = new RobotsTxt([$matchingGroup]);

        $this->assertFalse($robotsTxt->isAllowed('/foo/bar', 'FooBot'));
    }

    public function testMatchingDisallowedRuleAndMoreSpecificMatchingAllowedRuleButAlsoEvenMoreSpecificOtherMatchingDisallowedRuleWithinSeparateMatchingGroups(): void
    {
        $matchingGroup = new UserAgentGroup(['FooBot']);
        $matchingGroup->addDisallowedPattern(new RulePattern('/foo'));
        $otherMatchingGroup = new UserAgentGroup(['BarBot', 'FooBot']);
        $otherMatchingGroup->addAllowedPattern(new RulePattern('/foo/ba'));
        $andAnotherMatchingGroup = new UserAgentGroup(['ExampleBot', 'FooBot']);
        $andAnotherMatchingGroup->addDisallowedPattern(new RulePattern('/foo/bar'));
        $robotsTxt = new RobotsTxt([$matchingGroup, $otherMatchingGroup, $andAnotherMatchingGroup]);

        $this->assertFalse($robotsTxt->isAllowed('/foo/bar', 'FooBot'));
    }

    public function test_is_explicitly_not_allowed_for_returns_true_when_a_disallow_rule_is_for_explicit_user_agent(): void
    {
        $group = new UserAgentGroup(['FooBot']);

        $group->addDisallowedPattern(new RulePattern('/foo'));

        $robotsTxt = new RobotsTxt([$group]);

        $this->assertTrue($robotsTxt->isExplicitlyNotAllowedFor('/foo/bar', 'FooBot'));
    }

    public function test_is_explicitly_not_allowed_for_returns_false_when_the_group_of_a_disallow_rule_contains_wildcard_user_agent(): void
    {
        $group = new UserAgentGroup(['BarBot', '*']);

        $group->addDisallowedPattern(new RulePattern('/foo'));

        $robotsTxt = new RobotsTxt([$group]);

        $this->assertFalse($robotsTxt->isExplicitlyNotAllowedFor('/foo/bar', 'FooBot'));
    }
}
