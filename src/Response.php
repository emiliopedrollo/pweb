<?php

namespace App;

class Response
{
    protected int $code = 200;
    protected array $headers = [];

    private static array $codes = [
        200 => 'Ok',
        404 => 'Not Found',
    ];

    public function __construct(
        protected readonly ?string $content
    ) {}

    function __toString(): string
    {
        foreach ($this->headers as $header => $value) {
            header(sprintf("%s:%s", $header, $value));
        }
        header(self::$codes[$this->code], true, $this->code);
        return $this->content ?? '';
    }

    public static function redirect(string $url): static
    {
        /** @noinspection HtmlRequiredTitleElement */
        /** @noinspection HtmlRequiredLangAttribute */
        return static::make(<<<HTML
            <html><head><meta http-equiv="refresh" content="0;url=$url"></head></html>
        HTML)->addHeader('Location', $url);
    }

    public static function make($content = null): static
    {
        return new static(content: $content);
    }

    public function setCode(int $code): static
    {
        $this->code = $code;
        return $this;
    }

    public function addHeader(string $header, string $value): self
    {
        $this->headers[$header] = $value;
        return $this;
    }
}
