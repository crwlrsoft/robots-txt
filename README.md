# Robots Exclusion Standard/Protocol Parser
## for Web Crawling/Scraping

Use this library within crawler/scraper programs to parse robots.txt
files and check if your crawler user-agent is allowed to load certain
paths.

## Requirements

Requires PHP version 7.4 or above.

## Installation

Install the latest version with:

```sh
composer require crwlr/robots-txt
```

## Usage

```php
use Crwlr\RobotsTxt\RobotsTxt;

$robotsTxtContent = file_get_contents('https://www.crwlr.software/robots.txt');
$robotsTxt = RobotsTxt::parse($robotsTxtContent);

$robotsTxt->isAllowed('/packages');
```

You can also check with an absolute url.  
But attention: the library won't (/can't) check if the host of your
absolute url is the same as the robots.txt file was on (because it
doesn't know the host where it's on, you just give it the content).

```php
$robotsTxt->isAllowed('https://www.crwlr.software/packages');
```
