<?php

namespace App\Services\Forum;

class MarkdownParser
{
    /**
     * Parse markdown to safe HTML
     * Supports: bold, italic, links, inline code, code blocks, lists
     */
    public static function parse(string $text): string
    {
        // Escape HTML first (XSS prevention)
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');

        // Extract code blocks to protect them from further processing
        $codeBlocks = [];
        $text = preg_replace_callback('/```(.*?)```/s', function ($m) use (&$codeBlocks) {
            $key = '%%CODEBLOCK' . count($codeBlocks) . '%%';
            $codeBlocks[$key] = '<pre><code>' . $m[1] . '</code></pre>';
            return $key;
        }, $text);

        // Extract inline code
        $inlineCode = [];
        $text = preg_replace_callback('/`([^`]+)`/', function ($m) use (&$inlineCode) {
            $key = '%%INLINECODE' . count($inlineCode) . '%%';
            $inlineCode[$key] = '<code>' . $m[1] . '</code>';
            return $key;
        }, $text);

        // Bold
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);

        // Italic
        $text = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $text);

        // Links - only allow http/https
        $text = preg_replace_callback(
            '/\[([^\]]+)\]\((https?:\/\/[^\)]+)\)/',
            function ($m) {
                $label = $m[1];
                $url = $m[2];
                return '<a href="' . $url . '" rel="nofollow noopener" target="_blank">' . $label . '</a>';
            },
            $text
        );

        // Process blocks (lists and paragraphs)
        $text = self::processBlocks($text);

        // Restore inline code
        foreach ($inlineCode as $key => $value) {
            $text = str_replace($key, $value, $text);
        }

        // Restore code blocks
        foreach ($codeBlocks as $key => $value) {
            $text = str_replace($key, $value, $text);
        }

        return $text;
    }

    private static function processBlocks(string $text): string
    {
        $lines = explode("\n", $text);
        $output = [];
        $inList = false;
        $listType = null;
        $paragraph = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);

            // Unordered list
            if (preg_match('/^[-*]\s+(.+)/', $trimmed, $m)) {
                if (!empty($paragraph)) {
                    $output[] = '<p>' . implode('<br>', $paragraph) . '</p>';
                    $paragraph = [];
                }
                if (!$inList || $listType !== 'ul') {
                    if ($inList) $output[] = "</{$listType}>";
                    $output[] = '<ul>';
                    $inList = true;
                    $listType = 'ul';
                }
                $output[] = '<li>' . $m[1] . '</li>';
                continue;
            }

            // Ordered list
            if (preg_match('/^\d+\.\s+(.+)/', $trimmed, $m)) {
                if (!empty($paragraph)) {
                    $output[] = '<p>' . implode('<br>', $paragraph) . '</p>';
                    $paragraph = [];
                }
                if (!$inList || $listType !== 'ol') {
                    if ($inList) $output[] = "</{$listType}>";
                    $output[] = '<ol>';
                    $inList = true;
                    $listType = 'ol';
                }
                $output[] = '<li>' . $m[1] . '</li>';
                continue;
            }

            // Close list if we're not in a list item anymore
            if ($inList) {
                $output[] = "</{$listType}>";
                $inList = false;
                $listType = null;
            }

            // Empty line = paragraph break
            if ($trimmed === '') {
                if (!empty($paragraph)) {
                    $output[] = '<p>' . implode('<br>', $paragraph) . '</p>';
                    $paragraph = [];
                }
                continue;
            }

            // Code block placeholders pass through
            if (strpos($trimmed, '%%CODEBLOCK') !== false) {
                if (!empty($paragraph)) {
                    $output[] = '<p>' . implode('<br>', $paragraph) . '</p>';
                    $paragraph = [];
                }
                $output[] = $trimmed;
                continue;
            }

            $paragraph[] = $trimmed;
        }

        // Close any open list
        if ($inList) {
            $output[] = "</{$listType}>";
        }

        // Flush remaining paragraph
        if (!empty($paragraph)) {
            $output[] = '<p>' . implode('<br>', $paragraph) . '</p>';
        }

        return implode("\n", $output);
    }
}
