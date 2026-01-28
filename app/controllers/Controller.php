<?php

class Controller
{
    protected function abortForbidden(): void
    {
        http_response_code(403);
        echo '403 Forbidden';
        exit;
    }
}
