<?php

namespace Yuga\Views;

use Yuga\Views\Compilers\Compiler;
use Yuga\Views\Exceptions\ViewException;
use Yuga\Views\Support\FragmentManager;
use Yuga\Views\Support\SectionManager;
use Yuga\Views\Support\ViewComposerManager;

class Engine
{
    protected array $componentStack = [];

    public function __construct(
        protected Finder $finder,
        protected Compiler $compiler,
        protected SectionManager $sections,
        protected FragmentManager $fragments,
        protected ViewComposerManager $composers
    ) {}

    public function render(string $view, array $data = []): string
    {
        $this->sections->flush();
        $this->fragments->flush();

        return $this->renderView($view, $data, true);
    }

    public function renderPartial(string $view, array $data = []): string
    {
        return $this->renderView($view, $data, false);
    }

    protected function renderView(string $view, array $data = [], bool $allowLayout = true): string
    {
        $data = $this->composers->compose($view, $data);

        $path = $this->finder->find($view);

        $compiled = str_ends_with($path, '.hax.php')
            ? $this->compiler->compile($path)
            : $path;

        $output = $this->evaluate($compiled, $data, $path);

        if ($allowLayout && $layout = $this->sections->layout()) {
            return $this->renderView($layout, $data, false);
        }

        return $output;
    }

    public function composer(array|string $views, callable|string $composer): static
    {
        $this->composers->composer($views, $composer);

        return $this;
    }

    protected function evaluate(string $path, array $data, ?string $viewPath = null): string
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

            throw new ViewException(
                $viewPath ?? $path,
                $path,
                $e
            );
        }

        return ob_get_clean();
    }

    public function startComponent(string $view, array $data = []): void
    {
        $this->componentStack[] = [
            'view' => $view,
            'data' => $data,
            'slots' => [],
            'slotStack' => [],
        ];

        ob_start();
    }

    public function startComponentSlot(string $name): void
    {
        $index = count($this->componentStack) - 1;

        if ($index < 0) {
            throw new \RuntimeException('Cannot start a component slot outside a component.');
        }

        $this->componentStack[$index]['slotStack'][] = $name;

        ob_start();
    }

    public function endComponentSlot(): void
    {
        $index = count($this->componentStack) - 1;

        if ($index < 0) {
            throw new \RuntimeException('Cannot end a component slot outside a component.');
        }

        $name = array_pop($this->componentStack[$index]['slotStack']);

        if (!$name) {
            throw new \RuntimeException('Cannot end component slot that was not started.');
        }

        $this->componentStack[$index]['slots'][$name] = ob_get_clean();
    }

    public function endComponent(): string
    {
        if (empty($this->componentStack)) {
            throw new \RuntimeException('Cannot end component that was not started.');
        }

        $slot = ob_get_clean();

        $component = array_pop($this->componentStack);

        $attributes = $component['data'];

        return $this->renderPartial(
            $component['view'],
            array_merge(
                $component['slots'],
                $attributes,
                [
                    'slot' => $slot,
                    'attributes' => new \Yuga\Views\Support\ComponentAttributeBag(
                        $attributes
                    ),
                ]
            )
        );
    }

    public function compiler(): Compiler
    {
        return $this->compiler;
    }

    public function startFragment(string $name): void
    {
        $this->fragments->start($name);
    }

    public function endFragment(): string
    {
        return $this->fragments->stop();
    }

    public function fragment(string $view, string $name, array $data = []): string
    {
        $this->render($view, $data);

        if (!$this->fragments->has($name)) {
            throw new \RuntimeException("Fragment [{$name}] was not found.");
        }

        return $this->fragments->get($name);
    }
}
