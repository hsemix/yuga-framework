<?php

namespace Yuga\Views;

use Yuga\Views\Compilers\Compiler;
use Yuga\Views\Support\SectionManager;

class Engine
{
    public function __construct(
        protected Finder $finder,
        protected Compiler $compiler,
        protected SectionManager $sections
    ) {
    }

    public function render(string $view, array $data = []): string
    {
        $this->sections->flush();

        return $this->renderView($view, $data, true);
    }

    public function renderPartial(string $view, array $data = []): string
    {
        return $this->renderView($view, $data, false);
    }

    protected function renderView(string $view, array $data = [], bool $allowLayout = true): string
    {
        $path = $this->finder->find($view);

        $compiled = str_ends_with($path, '.hax.php')
            ? $this->compiler->compile($path)
            : $path;

        $output = $this->evaluate($compiled, $data);

        if ($allowLayout && $layout = $this->sections->layout()) {
            return $this->renderView($layout, $data, false);
        }

        return $output;
    }

    protected function evaluate(string $path, array $data): string
    {
        $__engine = $this;
        $__sections = $this->sections;

        extract($data, EXTR_SKIP);

        ob_start();

        try {
            include $path;
        } catch (\Throwable $e) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            throw $e;
        }

        return ob_get_clean();
    }
}