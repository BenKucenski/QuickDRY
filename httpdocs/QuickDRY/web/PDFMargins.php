<?php
namespace QuickDRY\Web;

use QuickDRY\Utilities\SafeClass;

class PDFMargins extends SafeClass
{
    public ?string $Units;
    public ?string $Top;
    public ?string $Left;
    public ?string $Right;
    public ?string $Bottom;
}