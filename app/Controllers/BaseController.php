<?php
// ─── app/Controllers/BaseController.php ──────

abstract class BaseController
{
    protected function render(string $view, array $data = [], string $layout = 'main'): void
    {
        extract($data);
        $layoutFile = VIEWS . "/layouts/{$layout}.php";
        $viewFile   = VIEWS . "/pages/{$view}.php";

        if (!file_exists($viewFile)) {
            http_response_code(404);
            die("View not found: $viewFile");
        }
        require_once $layoutFile;
    }

    protected function json(mixed $data, int $code = 200): void
    {
        http_response_code($code);
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        return $body[$key] ?? $_POST[$key] ?? $_GET[$key] ?? $default;
    }

    protected function inputAll(): array
    {
        return json_decode(file_get_contents('php://input'), true)
            ?? $_POST
            ?? [];
    }
}
