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
            'tab' => 'Hax View',
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

        $html = '<div class="tracy-inner">';
        $html .= '<h2>Hax View</h2>';

        $html .= sprintf(
            '<p><code>%s:%d</code></p>',
            $this->escape($path),
            $line
        );

        $html .= '<pre>';

        for ($number = $start; $number <= $end; $number++) {
            $source = rtrim(
                $lines[$number - 1],
                "\r\n"
            );

            if ($number === $line) {
                $html .= sprintf(
                    '<strong>&gt; %4d | %s</strong>' . "\n",
                    $number,
                    $this->escape($source)
                );

                continue;
            }

            $html .= sprintf(
                '  %4d | %s' . "\n",
                $number,
                $this->escape($source)
            );
        }

        $html .= '</pre>';
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