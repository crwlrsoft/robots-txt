<?php

declare(strict_types=1);

use Crwlr\RobotsTxt\Exceptions\InvalidRobotsTxtFileException;
use Crwlr\RobotsTxt\Parser;
use Crwlr\RobotsTxt\RulePattern;
use PHPUnit\Framework\TestCase;

final class ParserTest extends TestCase
{
    public function test_it_throws_an_exception_when_there_is_a_rule_line_before_any_user_agent_line(): void
    {
        $robotsTxtContent = <<<ROBOTSTXT
Disallow: ./foo.html

User-Agent: ExampleBot
Disallow: ./bar.html
ROBOTSTXT;
        $this->expectException(InvalidRobotsTxtFileException::class);
        (new Parser())->parse($robotsTxtContent);
    }

    public function test_dont_add_rule_when_pattern_is_empty_string(): void
    {
        $robotsTxtContent = <<<ROBOTSTXT
User-Agent: *
Disallow:
ROBOTSTXT;

        $robotsTxt = (new Parser())->parse($robotsTxtContent);

        $this->assertCount(1, $robotsTxt->groups());

        $group1 = $robotsTxt->groups()[0];
        $this->assertCount(1, $group1->userAgents());
        $this->assertEquals(['*'], $group1->userAgents());
        $this->assertCount(0, $group1->disallowedPatterns());
        $this->assertEmpty($group1->disallowedPatterns());
        $this->assertEmpty($group1->allowedPatterns());
    }

    public function test_parse_one_group_with_single_user_agent(): void
    {
        $robotsTxtContent = <<<ROBOTSTXT
User-Agent: *
Disallow: ./foo.html
ROBOTSTXT;

        $robotsTxt = (new Parser())->parse($robotsTxtContent);

        $this->assertCount(1, $robotsTxt->groups());

        $group1 = $robotsTxt->groups()[0];
        $this->assertCount(1, $group1->userAgents());
        $this->assertEquals(['*'], $group1->userAgents());
        $this->assertCount(1, $group1->disallowedPatterns());
        $this->assertArrayOfPatterns(['./foo.html'], $group1->disallowedPatterns());
        $this->assertEmpty($group1->allowedPatterns());
    }

    public function test_parse_one_group_with_multiple_user_agents(): void
    {
        $robotsTxtContent = <<<ROBOTSTXT
User-Agent: *
User-Agent: FooBot
Disallow: ./foo.html
ROBOTSTXT;

        $robotsTxt = (new Parser())->parse($robotsTxtContent);

        $this->assertCount(1, $robotsTxt->groups());

        $group1 = $robotsTxt->groups()[0];
        $this->assertCount(2, $group1->userAgents());
        $this->assertEquals(['*', 'FooBot'], $group1->userAgents());
        $this->assertCount(1, $group1->disallowedPatterns());
        $this->assertArrayOfPatterns(['./foo.html'], $group1->disallowedPatterns());
        $this->assertEmpty($group1->allowedPatterns());
    }

    public function test_parse_multiple_groups_with_single_user_agent(): void
    {
        $robotsTxtContent = <<<ROBOTSTXT
User-Agent: *
Disallow: ./something.html

User-Agent: FooBot
Disallow: ./foo.html

user-agent: BarBot
Disallow: ./bar.html
Allow: ./something.html
ROBOTSTXT;

        $robotsTxt = (new Parser())->parse($robotsTxtContent);

        $this->assertCount(3, $robotsTxt->groups());

        $group1 = $robotsTxt->groups()[0];
        $this->assertCount(1, $group1->userAgents());
        $this->assertEquals(['*'], $group1->userAgents());
        $this->assertCount(1, $group1->disallowedPatterns());
        $this->assertArrayOfPatterns(['./something.html'], $group1->disallowedPatterns());
        $this->assertEmpty($group1->allowedPatterns());

        $group2 = $robotsTxt->groups()[1];
        $this->assertCount(1, $group2->userAgents());
        $this->assertEquals(['FooBot'], $group2->userAgents());
        $this->assertCount(1, $group2->disallowedPatterns());
        $this->assertArrayOfPatterns(['./foo.html'], $group2->disallowedPatterns());
        $this->assertEmpty($group2->allowedPatterns());

        $group3 = $robotsTxt->groups()[2];
        $this->assertCount(1, $group3->userAgents());
        $this->assertEquals(['BarBot'], $group3->userAgents());
        $this->assertCount(1, $group3->disallowedPatterns());
        $this->assertArrayOfPatterns(['./bar.html'], $group3->disallowedPatterns());
        $this->assertCount(1, $group3->allowedPatterns());
        $this->assertArrayOfPatterns(['./something.html'], $group3->allowedPatterns());
    }

    public function test_parse_multiple_groups_with_multiple_user_agents(): void
    {
        $robotsTxtContent = <<<ROBOTSTXT
User-Agent: *
Disallow: /admin
Disallow: /exclusive

User-Agent: FooBot
User-Agent: BarBot
Disallow: ./foo.html

User-Agent: BazBot
User-Agent: CrwlrBot
User-Agent: ExampleBot
Allow: /exclusive
ROBOTSTXT;

        $robotsTxt = (new Parser())->parse($robotsTxtContent);

        $this->assertCount(3, $robotsTxt->groups());

        $group1 = $robotsTxt->groups()[0];
        $this->assertCount(1, $group1->userAgents());
        $this->assertEquals(['*'], $group1->userAgents());
        $this->assertCount(2, $group1->disallowedPatterns());
        $this->assertArrayOfPatterns(['/admin', '/exclusive'], $group1->disallowedPatterns());
        $this->assertEmpty($group1->allowedPatterns());

        $group2 = $robotsTxt->groups()[1];
        $this->assertCount(2, $group2->userAgents());
        $this->assertEquals(['FooBot', 'BarBot'], $group2->userAgents());
        $this->assertCount(1, $group2->disallowedPatterns());
        $this->assertArrayOfPatterns(['./foo.html'], $group2->disallowedPatterns());
        $this->assertEmpty($group2->allowedPatterns());

        $group3 = $robotsTxt->groups()[2];
        $this->assertCount(3, $group3->userAgents());
        $this->assertEquals(['BazBot', 'CrwlrBot', 'ExampleBot'], $group3->userAgents());
        $this->assertEmpty($group3->disallowedPatterns());
        $this->assertCount(1, $group3->allowedPatterns());
        $this->assertArrayOfPatterns(['/exclusive'], $group3->allowedPatterns());
    }

    public function test_parse_single_disallow_rule(): void
    {
        $robotsTxtContent = <<<ROBOTSTXT
User-Agent: ExampleBot
Disallow : /example
ROBOTSTXT;

        $robotsTxt = (new Parser())->parse($robotsTxtContent);

        $group1 = $robotsTxt->groups()[0];
        $this->assertCount(1, $group1->disallowedPatterns());
        $this->assertArrayOfPatterns(['/example'], $group1->disallowedPatterns());
    }

    public function test_parse_multiple_disallow_rules(): void
    {
        $robotsTxtContent = <<<ROBOTSTXT
User-Agent: ExampleBot
Disallow : /example
disallow: /secret
Disallow: /another-secret
ROBOTSTXT;

        $robotsTxt = (new Parser())->parse($robotsTxtContent);

        $group1 = $robotsTxt->groups()[0];
        $this->assertCount(3, $group1->disallowedPatterns());
        $this->assertArrayOfPatterns(['/example', '/secret', '/another-secret'], $group1->disallowedPatterns());
    }

    public function test_parse_multiple_disallow_rules_to_multiple_user_agent_groups(): void
    {
        $robotsTxtContent = <<<ROBOTSTXT
User-Agent: ExampleBot
Disallow : /example
disallow: /secret
Disallow: /another-secret

User-Agent: AnotherExampleBot
Disallow: /super-secret
Disallow: /just-disallowed
ROBOTSTXT;

        $robotsTxt = (new Parser())->parse($robotsTxtContent);

        $group1 = $robotsTxt->groups()[0];
        $this->assertCount(3, $group1->disallowedPatterns());
        $this->assertArrayOfPatterns(['/example', '/secret', '/another-secret'], $group1->disallowedPatterns());

        $group2 = $robotsTxt->groups()[1];
        $this->assertCount(2, $group2->disallowedPatterns());
        $this->assertArrayOfPatterns(['/super-secret', '/just-disallowed'], $group2->disallowedPatterns());
    }

    public function test_parse_single_allow_rule(): void
    {
        $robotsTxtContent = <<<ROBOTSTXT
User-Agent: FooBot
allow: /foo-bar
ROBOTSTXT;

        $robotsTxt = (new Parser())->parse($robotsTxtContent);

        $group1 = $robotsTxt->groups()[0];
        $this->assertCount(1, $group1->allowedPatterns());
        $this->assertArrayOfPatterns(['/foo-bar'], $group1->allowedPatterns());
    }

    public function test_parse_multiple_allow_rules(): void
    {
        $robotsTxtContent = <<<ROBOTSTXT
User-Agent: BarBot
Allow : /contact
 Allow: /articles
Allow: /news
ROBOTSTXT;

        $robotsTxt = (new Parser())->parse($robotsTxtContent);

        $group1 = $robotsTxt->groups()[0];
        $this->assertCount(3, $group1->allowedPatterns());
        $this->assertArrayOfPatterns(['/contact', '/articles', '/news'], $group1->allowedPatterns());
    }

    public function test_parse_multiple_allow_rules_to_multiple_user_agent_groups(): void
    {
        $robotsTxtContent = <<<ROBOTSTXT
User-Agent: BarBot
Allow : /contact
 Allow: /articles
Allow: /news

User-Agent: BazBot
Allow: /articles
Allow: /guestbook
ROBOTSTXT;

        $robotsTxt = (new Parser())->parse($robotsTxtContent);

        $group1 = $robotsTxt->groups()[0];
        $this->assertCount(3, $group1->allowedPatterns());
        $this->assertArrayOfPatterns(['/contact', '/articles', '/news'], $group1->allowedPatterns());

        $group2 = $robotsTxt->groups()[1];
        $this->assertCount(2, $group2->allowedPatterns());
        $this->assertArrayOfPatterns(['/articles', '/guestbook'], $group2->allowedPatterns());
    }

    public function test_parse_mixed_disallow_and_allow_rules_to_multiple_user_agent_groups(): void
    {
        $robotsTxtContent = <<<ROBOTSTXT
User-Agent: *
Disallow: /hidden
Disallow: /hidden-for-most-bots

User-Agent: CrwlrBot
Disallow: /lorem
Allow: /hidden
Allow: /hidden-for-most-bots

User-Agent: SomeBot
User-Agent: SomeOtherBot
Allow: /hidden-for-most-bots
Disallow: /something
Disallow: /something-else
ROBOTSTXT;

        $robotsTxt = (new Parser())->parse($robotsTxtContent);

        $this->assertCount(3, $robotsTxt->groups());

        $group1 = $robotsTxt->groups()[0];
        $this->assertCount(1, $group1->userAgents());
        $this->assertEquals(['*'], $group1->userAgents());
        $this->assertCount(2, $group1->disallowedPatterns());
        $this->assertArrayOfPatterns(['/hidden', '/hidden-for-most-bots'], $group1->disallowedPatterns());
        $this->assertEmpty($group1->allowedPatterns());

        $group2 = $robotsTxt->groups()[1];
        $this->assertCount(1, $group2->userAgents());
        $this->assertEquals(['CrwlrBot'], $group2->userAgents());
        $this->assertCount(1, $group2->disallowedPatterns());
        $this->assertArrayOfPatterns(['/lorem'], $group2->disallowedPatterns());
        $this->assertCount(2, $group2->allowedPatterns());
        $this->assertArrayOfPatterns(['/hidden', '/hidden-for-most-bots'], $group2->allowedPatterns());

        $group3 = $robotsTxt->groups()[2];
        $this->assertCount(2, $group3->userAgents());
        $this->assertEquals(['SomeBot', 'SomeOtherBot'], $group3->userAgents());
        $this->assertCount(2, $group3->disallowedPatterns());
        $this->assertArrayOfPatterns(['/something', '/something-else'], $group3->disallowedPatterns());
        $this->assertCount(1, $group3->allowedPatterns());
        $this->assertArrayOfPatterns(['/hidden-for-most-bots'], $group3->allowedPatterns());
    }

    public function test_parse_sitemap_lines(): void
    {
        $robotsTxtContent = <<<ROBOTSTXT
User-Agent: *
Disallow: 

User-Agent: BadBot
Disallow: /

Sitemap: https://www.example.com/sitemap1.xml
sitemap: https://www.example.com/sitemap2.xml

 Sitemap: https://www.example.org/sitemap3.xml

ROBOTSTXT;

        $robotsTxt = (new Parser())->parse($robotsTxtContent);

        $this->assertCount(3, $robotsTxt->sitemaps());

        $this->assertEquals([
            'https://www.example.com/sitemap1.xml',
            'https://www.example.com/sitemap2.xml',
            'https://www.example.org/sitemap3.xml',
        ], $robotsTxt->sitemaps());
    }

    /**
     * @param string[] $expected
     * @param RulePattern[] $actual
     */
    private function assertArrayOfPatterns(array $expected, array $actual): void
    {
        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $actual[$key]->pattern());
        }
    }
}
