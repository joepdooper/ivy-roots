<?php

namespace Ivy\Livy;

use Ivy\View\View;

class LivyController
{
    /**
     * Handle Livy AJAX requests.
     * Expects JSON payload: { "state": {...}, "action": "methodName", "params": {...} }
     */
    public function handle(string $componentClass): void
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $class = "\\{$componentClass}";
        if (!class_exists($class)) {
            http_response_code(404);
            echo json_encode(['error' => "Component {$componentClass} not found."]);
            exit;
        }

        $component = new $class();

        // Restore state
        foreach ($data['state'] ?? [] as $key => $value) {
            if (property_exists($component, $key)) {
                $component->$key = $value;
            }
        }

        // Execute action
        if (!empty($data['action']) && method_exists($component, $data['action'])) {
            $component->{$data['action']}();
        }

        // Render template
        ob_start();
        $component->render($data['params'] ?? []);
        $html = ob_get_clean();

        header('Content-Type: application/json');
        echo json_encode([
            'state' => get_object_vars($component),
            'html' => $html,
        ]);
    }
}
