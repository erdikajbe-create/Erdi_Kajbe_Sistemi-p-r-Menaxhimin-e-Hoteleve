<?php

declare(strict_types=1);

namespace HotelHub\Core;

use RuntimeException;
use Throwable;

final class View
{
    /**
     * @param array<string, mixed> $data
     */
    public static function render(string $template, array $data = []): string
    {
        $templatePath = dirname(__DIR__, 2) . '/views/' . $template . '.php';

        if (!is_file($templatePath)) {
            throw new RuntimeException(sprintf('Pamja "%s" nuk u gjet.', $template));
        }

        extract($data, EXTR_SKIP);
        $title ??= 'HotelHub';

        ob_start();
        try {
            require $templatePath;
            $content = (string) ob_get_clean();
        } catch (Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }

        ob_start();
        try {
            require dirname(__DIR__, 2) . '/views/layout.php';

            return (string) ob_get_clean();
        } catch (Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }
    }
}
