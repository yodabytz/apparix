<?php

namespace App\Core;

/**
 * View class for rendering templates
 */
class View
{
    private string $viewPath = __DIR__ . '/../Views/';

    /**
     * Render a view file
     */
    public function render(string $view, array $data = [], string $layout = 'main'): void
    {
        // Extract data into variables
        extract($data, EXTR_SKIP);

        // Start output buffering for view
        ob_start();

        // Include view file
        $viewFile = $this->viewPath . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewFile)) {
            ob_end_clean();
            die("View file not found: $viewFile");
        }

        include $viewFile;
        $content = ob_get_clean();

        // Include layout - wrap content in full HTML document
        if ($layout) {
            $layoutFile = $this->viewPath . 'layouts/' . $layout . '.php';
            if (!file_exists($layoutFile)) {
                echo $content;
                return;
            }
            include $layoutFile;
        } else {
            echo $content;
        }
    }

    /**
     * Include a partial view
     */
    public function partial(string $partial, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $partialFile = $this->viewPath . str_replace('.', '/', $partial) . '.php';

        if (!file_exists($partialFile)) {
            die("Partial view not found: $partialFile");
        }

        include $partialFile;
    }
}
