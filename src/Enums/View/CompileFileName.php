<?php

namespace App\Enums\View;

enum CompileFileName
{
    case auto;
    case md5;
    case sha1;
    case normal;
}
