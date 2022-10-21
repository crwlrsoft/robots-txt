<?php

declare(strict_types=1);

use Crwlr\RobotsTxt\Encoding;
use PHPUnit\Framework\TestCase;

final class EncodingTest extends TestCase
{
    /**
     * @return Generator&iterable<string[]> [$expected, $path]
     */
    public function pathProvider(): Generator
    {
        yield 'Unnecessarily encoded characters' => ['/foo/bar',       '/f%6F%6f/b%61r'];
        yield 'Non ASCII characters'             => ['/f%F6%F6/b%E4r', '/f%F6%F6/b%E4r'];
        yield 'Encoded reserved characters'      => ['%3A%2F%3F%23%5B%5D%40%21%24%26%27%28%29%2A%2B%2C%3B%3D', '%3A%2F%3F%23%5B%5D%40%21%24%26%27%28%29%2A%2B%2C%3B%3D'];
    }

    /**
     * @dataProvider pathProvider
     */
    public function test_decode(string $expected, string $path): void
    {
        $this->assertEquals($expected, Encoding::decodePercentEncodedAsciiCharactersInPath($path));
    }
}
