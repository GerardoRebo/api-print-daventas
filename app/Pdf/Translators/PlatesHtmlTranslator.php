<?php

declare(strict_types=1);


namespace App\Pdf\Translators;

use Illuminate\Support\Facades\Storage;
use League\Plates\Engine as PlatesEngine;
use PhpCfdi\CfdiToPdf\Builders\HtmlTranslators\PlatesHtmlTranslator as HtmlTranslatorsPlatesHtmlTranslator;
use PhpCfdi\CfdiToPdf\CfdiData;

class PlatesHtmlTranslator extends HtmlTranslatorsPlatesHtmlTranslator
{
    /** @var string */
    private $directory;

    /** @var string */
    private $template;

    /** @var object */
    private $userData;
    /**
     * PlatesHtmlTranslator constructor.
     *
     * @param string $directory
     * @param string $template
     */
    public function __construct(string $directory, string $template, $userData)
    {
        $this->directory = $directory;
        $this->template = $template;
        $this->userData = $userData;
    }

    public function translate(CfdiData $cfdiData): string
    {
        // __DIR__ is src/Builders
        $plates = new PlatesEngine($this->directory());
        if ($this?->userData?->organization?->image?->path && Storage::exists($this->userData->organization->image->path)) {
            $imageData = base64_encode(Storage::get($this->userData->organization->image->path));
            $src = 'data:image/png;base64,' . $imageData;
        } else {
            $src = null;
        }
        return $plates->render($this->template(), [
            'cfdiData' => $cfdiData,
            'src' => $src,
        ]);
    }

    public function directory(): string
    {
        return $this->directory;
    }

    public function template(): string
    {
        return $this->template;
    }
}
