<?php

namespace App\Tests\Service;

use App\Service\SanitizerService;
use PHPUnit\Framework\TestCase;

class SanitizerServiceTest extends TestCase
{
    private SanitizerService $sanitizer;

    protected function setUp(): void
    {
        $this->sanitizer = new SanitizerService();
    }

    // ── sanitizeSpace ────────────────────────────────

    public function testSanitizeSpaceTrimsWhitespace(): void
    {
        $this->assertSame('hello', $this->sanitizer->sanitizeSpace('  hello  '));
    }

    public function testSanitizeSpaceStripsHtml(): void
    {
        $this->assertSame('hello', $this->sanitizer->sanitizeSpace('<b>hello</b>'));
    }

    public function testSanitizeSpaceStripsScript(): void
    {
        $this->assertSame('alert("xss")', $this->sanitizer->sanitizeSpace('<script>alert("xss")</script>'));
    }

    // ── sanitize email ───────────────────────────────

    public function testSanitizeEmailValid(): void
    {
        $this->assertSame('test@email.fr', $this->sanitizer->sanitize('test@email.fr', 'email'));
    }

    public function testSanitizeEmailTrimsSpaces(): void
    {
        $this->assertSame('test@email.fr', $this->sanitizer->sanitize('  test@email.fr  ', 'email'));
    }

    public function testSanitizeEmailInvalidReturnsEmpty(): void
    {
        $this->assertSame('', $this->sanitizer->sanitize('not-an-email', 'email'));
    }

    public function testSanitizeEmailStripsHtml(): void
    {
        $this->assertSame('test@email.fr', $this->sanitizer->sanitize('<b>test@email.fr</b>', 'email'));
    }

    // ── sanitize texte ───────────────────────────────

    public function testSanitizeTexte(): void
    {
        $this->assertSame('Jean-Pierre', $this->sanitizer->sanitize('Jean-Pierre', 'texte'));
    }

    public function testSanitizeTexteStripsHtml(): void
    {
        $this->assertSame('Jean', $this->sanitizer->sanitize('<script>alert("xss")</script>Jean', 'texte'));
    }

    public function testSanitizeTexteTruncatesAt255(): void
    {
        $long = str_repeat('a', 300);
        $result = $this->sanitizer->sanitize($long, 'texte');
        $this->assertSame(255, mb_strlen($result));
    }

    public function testSanitizeTexteShortStringUnchanged(): void
    {
        $this->assertSame('Dupont', $this->sanitizer->sanitize('Dupont', 'texte'));
    }

    // ── sanitize message ─────────────────────────────

    public function testSanitizeMessage(): void
    {
        $this->assertSame('Bonjour', $this->sanitizer->sanitize('Bonjour', 'message'));
    }

    public function testSanitizeMessageTruncatesAt1000(): void
    {
        $long = str_repeat('b', 1200);
        $result = $this->sanitizer->sanitize($long, 'message');
        $this->assertSame(1000, mb_strlen($result));
    }

    // ── sanitize telephone ───────────────────────────

    public function testSanitizeTelephone(): void
    {
        $this->assertSame('06 01 02 03 04', $this->sanitizer->sanitize('06 01 02 03 04', 'telephone'));
    }

    public function testSanitizeTelephoneStripsLetters(): void
    {
        $this->assertSame('+33 6 01 02 03 04', $this->sanitizer->sanitize('+33 6 01 02 03 04 abc', 'telephone'));
    }

    public function testSanitizeTelephoneKeepsSpecialChars(): void
    {
        $this->assertSame('+33(0)601020304', $this->sanitizer->sanitize('+33(0)601020304', 'telephone'));
    }

    // ── sanitize code_postal ─────────────────────────

    public function testSanitizeCodePostal(): void
    {
        $this->assertSame('75001', $this->sanitizer->sanitize('75001', 'code_postal'));
    }

    public function testSanitizeCodePostalStripsNonDigits(): void
    {
        $this->assertSame('75001', $this->sanitizer->sanitize('75-001 abc', 'code_postal'));
    }

    // ── sanitize type inconnu ────────────────────────

    public function testSanitizeUnknownTypeStillCleansBasic(): void
    {
        $result = $this->sanitizer->sanitize('<b>test</b>', 'unknown');
        $this->assertSame('test', $result);
    }

    // ── sanitize control characters ──────────────────

    public function testSanitizeRemovesNullBytes(): void
    {
        $result = $this->sanitizer->sanitize("test\x00value", 'texte');
        $this->assertSame('testvalue', $result);
    }

    public function testSanitizeRemovesControlChars(): void
    {
        $result = $this->sanitizer->sanitize("test\x07value", 'texte');
        $this->assertSame('testvalue', $result);
    }

    // ── escapeForHtml ────────────────────────────────

    public function testEscapeForHtml(): void
    {
        $this->assertSame('&lt;script&gt;', $this->sanitizer->escapeForHtml('<script>'));
    }

    public function testEscapeForHtmlQuotes(): void
    {
        $this->assertSame('&quot;hello&quot;', $this->sanitizer->escapeForHtml('"hello"'));
    }

    public function testEscapeForHtmlSingleQuotes(): void
    {
        $this->assertSame('it&#039;s', $this->sanitizer->escapeForHtml("it's"));
    }

    public function testEscapeForHtmlAmpersand(): void
    {
        $this->assertSame('a &amp; b', $this->sanitizer->escapeForHtml('a & b'));
    }
}
