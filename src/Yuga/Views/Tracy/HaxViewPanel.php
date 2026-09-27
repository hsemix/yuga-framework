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
                $active ? '->' : '',
                $number,
                $this->escape($source)
            );
        }

        $html .= '</div>';

        $trace = $exception->componentTrace();

        $html .= $this->renderComponentTrace($exception);

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

            .yuga-hax-component-location {
                margin-top: 3px;
                opacity: .65;
                font-family: monospace;
                font-size: .9em;
            }

            .yuga-hax-components {
                margin-top: 18px;
            }

            .yuga-hax-components ol {
                margin-bottom: 0;
            }
            </style>
        HTML;

        $html .= '</div>';

        return $html;
    }

    protected function renderComponentTrace(ViewException $exception): string
    {
        $trace = $exception->componentTrace();

        if ($trace === []) {
            return '';
        }

        $html = '<div class="yuga-component-trace">';
        $html .= '<strong>Component trace</strong>';
        $html .= '<div class="yuga-component-trace-tree">';

        foreach ($trace as $depth => $component) {
            $source = $component['source'] ?? null;
            $line = $component['line'] ?? null;

            if ($source !== null) {
                $location = $this->shortenPath($source);

                if ($line !== null) {
                    $location .= ':' . $line;
                }

                $html .= sprintf(
                    '<div style="margin-left:%dpx">%s</div>',
                    $depth * 20,
                    $this->escape($location)
                );
            }

            $html .= sprintf(
                '<div style="margin-left:%dpx">└─ <code>&lt;x-%s&gt;</code></div>',
                $depth * 20,
                $this->escape(
                    $this->componentName($component['view'])
                )
            );
        }

        $depth = count($trace);

        $html .= sprintf(
            '<div style="margin-left:%dpx">%s:%d <strong><- error</strong></div>',
            $depth * 20,
            $this->escape(
                $this->shortenPath($exception->viewPath())
            ),
            $exception->viewLine()
        );

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    protected function shortenPath(string $path): string
    {
        $needle = DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR;

        $position = strpos($path, $needle);

        if ($position !== false) {
            return 'resources/views/' . str_replace(
                DIRECTORY_SEPARATOR,
                '/',
                substr($path, $position + strlen($needle))
            );
        }

        return $path;
    }

    protected function escape(string $value): string
    {
        return htmlspecialchars(
            $value,
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );
    }

    protected function componentName(string $view): string
    {
        if (str_starts_with($view, 'components.')) {
            return substr($view, strlen('components.'));
        }

        if (str_contains($view, '::components.')) {
            return str_replace('::components.', '::', $view);
        }

        return $view;
    }
}
