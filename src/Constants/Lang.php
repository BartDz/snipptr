<?php

namespace Snipptr\Constants;

class Lang extends Constants
{
    public const PLAINTEXT = 'plaintext';
    public const PHP = 'php';
    public const JAVASCRIPT = 'javascript';
    public const TYPESCRIPT = 'typescript';
    public const PYTHON = 'python';
    public const HTML = 'html';
    public const CSS = 'css';
    public const SQL = 'sql';
    public const BASH = 'bash';
    public const JSON = 'json';
    public const XML = 'xml';
    public const GO = 'go';
    public const RUST = 'rust';
    public const JAVA = 'java';
    public const C = 'c';
    public const CPP = 'cpp';
    public const MARKDOWN = 'markdown';

    public static function getSelectFieldOptions(): array
    {
        return [
            self::PLAINTEXT => 'Plain Text',
            self::PHP => 'PHP',
            self::JAVASCRIPT => 'JavaScript',
            self::TYPESCRIPT => 'TypeScript',
            self::PYTHON => 'Python',
            self::HTML => 'HTML',
            self::CSS => 'CSS',
            self::SQL => 'SQL',
            self::BASH => 'Bash',
            self::JSON => 'JSON',
            self::XML => 'XML',
            self::GO => 'Go',
            self::RUST => 'Rust',
            self::JAVA => 'Java',
            self::C => 'C',
            self::CPP => 'C++',
            self::MARKDOWN => 'Markdown',
        ];
    }

    public static function getPrismLanguage(string $lang): string
    {
        return match ($lang) {
            self::HTML, self::XML => 'markup',
            self::BASH => self::BASH,
            self::C => self::C,
            self::CPP => self::CPP,
            self::CSS => self::CSS,
            self::GO => self::GO,
            self::JAVA => self::JAVA,
            self::JAVASCRIPT => self::JAVASCRIPT,
            self::JSON => self::JSON,
            self::MARKDOWN => self::MARKDOWN,
            self::PHP => self::PHP,
            self::PYTHON => self::PYTHON,
            self::RUST => self::RUST,
            self::SQL => self::SQL,
            self::TYPESCRIPT => self::TYPESCRIPT,
            default => 'none',
        };
    }
}