<?php

namespace Yuga\Views\Tracy;

use Throwable;
use Yuga\Views\Exceptions\ViewException;

class HaxViewPanel
{
    public function __invoke(?Throwable $exception): ?array
    {
        if (!$exception instanceof ViewException) {
            return null;
        }

        return [
            'tab' => 'Hax Source',
            'panel' => $this->render($exception),
        ];
    }

    protected function render(ViewException $exception): string
    {
        $path = $exception->viewPath();
        $line = $exception->viewLine();

        if (!is_file($path)) {
            return '';
        }

        $lines = file($path);

        if ($lines === false) {
            return '';
        }

        $start = max(1, $line - 6);
        $end = min(count($lines), $line + 6);

        $html = '<div class="tracy-inner yuga-hax-view">';

        $html .= sprintf(
            '<p class="yuga-hax-location"><strong>File:</strong> <code>%s:%d</code></p>',
            $this->escape($path),
            $line
        );

        $html .= '<div class="yuga-hax-source">';

        for ($number = $start; $number <= $end; $number++) {
            $source = rtrim($lines[$number - 1], "\r\n");
            $active = $number === $line;

            $html .= sprintf(
                '<div class="yuga-hax-line%s">' .
                    '<span class="yuga-hax-marker">%s</span>' .
                    '<span class="yuga-hax-number">%d</span>' .
                    '<code>%s</code>' .
                    '</div>',
                $active ? ' yuga-hax-line-active' : '',
                $active ? '›' : '',
                $number,
                $this->escape($source)
            );
        }

        $html .= '</div>';

        $html .= <<<'HTML'
            <style>
            .yuga-hax-location {
                margin: 0 0 12px;
            }

            .yuga-hax-source {
                overflow-x: auto;
                font-family: monospace;
                line-height: 1.6;
            }

            .yuga-hax-line {
                display: grid;
                grid-template-columns: 20px 45px minmax(0, 1fr);
                white-space: pre;
            }

            .yuga-hax-marker {
                text-align: center;
            }

            .yuga-hax-number {
                padding-right: 12px;
                text-align: right;
                user-select: none;
                opacity: .55;
            }

            .yuga-hax-line code {
                white-space: pre;
                background: transparent;
            }

            .yuga-hax-line-active {
                font-weight: bold;
            }

            .yuga-hax-line-active .yuga-hax-marker {
                font-weight: bold;
            }
            </style>
        HTML;

        $html .= '</div>';

        return $html;
    }

    protected function escape(string $value): string
    {
        return htmlspecialchars(
            $value,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }
}
