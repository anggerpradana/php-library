<?php

declare(strict_types=1);

namespace System\View;

use System\View\Exceptions\ViewFileNotFound;
use System\View\Templator\BreakTemplator;
use System\View\Templator\CommentTemplator;
use System\View\Templator\ContinueTemplator;
use System\View\Templator\EachTemplator;
use System\View\Templator\IfTemplator;
use System\View\Templator\IncludeTemplator;
use System\View\Templator\NameTemplator;
use System\View\Templator\PHPTemplator;
use System\View\Templator\SectionTemplator;
use System\View\Templator\SetTemplator;

class Templator
{
    private string $templateDir;
    private string $cacheDir;
    public string $suffix = '';
    public int $max_depth = 5;

    public function __construct(string $templateDir, string $cacheDir)
    {
        $this->templateDir = $templateDir;
        $this->cacheDir    = $cacheDir;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $templateName, array $data, bool $cache = true): string
    {
        $templateName .= $this->suffix;
        $templatePath  = $this->templateDir . '/' . $templateName;

        if (!file_exists($templatePath)) {
            throw new ViewFileNotFound($templatePath);
        }

        $cachePath = $this->cacheDir . '/' . md5($templateName) . '.php';

        if ($cache && file_exists($cachePath) && filemtime($cachePath) >= filemtime($templatePath)) {
            return $this->getView($cachePath, $data);
        }

        $template = file_get_contents($templatePath);
        $template = $this->templates($template);

        file_put_contents($cachePath, $template);

        return $this->getView($cachePath, $data);
    }

    /**
     * Compile templator file to php file.
     */
    public function compile(string $template_name): string
    {
        $template_name .= $this->suffix;
        $template_dir  = $this->templateDir . '/' . $template_name;

        if (!file_exists($template_dir)) {
            throw new ViewFileNotFound($template_dir);
        }

        $cachePath = $this->cacheDir . '/' . md5($template_name) . '.php';

        $template = file_get_contents($template_dir);
        $template = $this->templates($template);

        file_put_contents($cachePath, $template);

        return $template;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function getView(string $tempalte_path, array $data): string
    {
        $level = ob_get_level();

        ob_start();

        try {
            (static function ($__, $__file_name__) {
                extract($__);
                include $__file_name__;
            })($data, $tempalte_path);
        } catch (\Throwable $th) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }

            throw $th;
        }

        $out = ob_get_clean();

        return $out === false ? '' : ltrim($out);
    }

    /**
     * Transform templator to php template.
     */
    public function templates(string $template): string
    {
        return array_reduce([
            SetTemplator::class,
            SectionTemplator::class,
            IncludeTemplator::class,
            PHPTemplator::class,
            NameTemplator::class,
            IfTemplator::class,
            EachTemplator::class,
            CommentTemplator::class,
            ContinueTemplator::class,
            BreakTemplator::class,
        ], function ($template, $templator) {
            $templator = new $templator($this->templateDir, $this->cacheDir);
            if ($templator instanceof IncludeTemplator) {
                $templator->maksDept($this->max_depth);
            }

            return $templator->parse($template);
        }, $template);
    }
}
