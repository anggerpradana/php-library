<?php

declare(strict_types=1);

namespace System\View\Templator;

use System\View\AbstractTemplatorParse;

class SetTemplator extends AbstractTemplatorParse
{
    public function parse(string $template): string
    {
        return preg_replace(
            '/{%\s*set\s+(\w+)\s*=\s*(.*?)\s*%}/',
            '<?php $$1 = $2; ?>',
            $template
        );
    }
}
