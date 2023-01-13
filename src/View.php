<?php

namespace App;

/**
 * @noinspection ForgottenDebugOutputInspection
 * @noinspection UnknownInspectionInspection
 * @noinspection TypeUnsafeComparisonInspection
 * @noinspection NonSecureExtractUsageInspection
 * @noinspection PregQuoteUsageInspection
 * @noinspection NotOptimalRegularExpressionsInspection
 * @noinspection SubStrUsedAsStrPosInspection
 * @noinspection ThrowRawExceptionInspection
 * @noinspection Annotator
 * @noinspection IsNullFunctionUsageInspection
 * @noinspection CallableParameterUseCaseInTypeContextInspection
 */

use App\Enums\View\CompileFileName;
use App\Enums\View\Mode;
use App\Models\Model;
use App\Models\User;
use ArrayAccess;
use BadMethodCallException;
use Closure;
use Countable;
use DateTime;
use Exception;
use InvalidArgumentException;
use ParseError;
use RuntimeException;
use Throwable;

/**
 * View - A simple and single file Blade implementation alternative
 *
 * @author Emilio Brandt Pedrollo <emiliopedrollo at gmail dot com>
 */
class View
{
    public array $aliasClasses = [];
    protected ?array $assetDict = null;
    protected ?string $baseDomain;
    protected string $baseUrl = '.';
    protected string $cachePath;
    protected ?string $canonicalUrl;
    protected CompileFileName $compileTypeFileName = CompileFileName::auto;
    protected array $componentData = [];
    protected array $componentStack = [];
    protected array $conditions = [];
    protected array $contentTags = ['{{', '}}'];
    public string $csrf_token = '';
    protected ?string $currentUrl;
    public ?User $currentUser = null;
    protected array $customDirectives = [];
    protected array $customDirectivesRT = [];
    protected string $echoFormat = "\\htmlentities(%s??'', ENT_QUOTES, 'UTF-8', false)";
    public Closure $errorCallBack;
    protected array $escapedTags = ['{{{', '}}}'];
    protected array $extensions = [];
    protected ?string $fileName = null;
    protected bool $firstCaseInSwitch = true;
    protected array $footer = [];
    protected int $forelseCounter = 0;
    public bool $includeScope = true;
    protected Closure $injectResolver;
    protected array $loopsStack = [];
    protected Mode $mode = Mode::auto;
    protected ?string $notFoundPath = null;
    protected bool $optimize = true;
    protected string $PARENTKEY = '@parentXYZABC';
    protected array $pushes = [];
    protected array $pushStack = [];
    protected array $rawTags = ['{!!', '!!}'];
    protected string $relativePath = '';
    protected int $renderCount = 0;
    protected array $sections = [];
    protected array $sectionStack = [];
    protected array $slots = [];
    protected array $slotStack = [];
    protected int $switchCount = 0;
    protected int $uidCounter = 0;
    protected array $variables = [];
    protected array $variablesGlobal = [];
    protected array $verbatimBlocks = [];
    protected string $verbatimPlaceholder = '$__verbatim__$';
    protected array $viewsPath;
    protected ?string $viewStack;


    /**
     * @param $viewsPath
     * @param $cachePath
     * @param Mode $mode
     */
    public function __construct($viewsPath = null, $cachePath = null, Mode $mode = Mode::auto)
    {
        /** @var Application $app */
        $app = app();

        $viewsPath ??= $app->getViewsPath();
        $cachePath ??= $app->getCachePath();

        $this->viewsPath = (is_array($viewsPath)) ? $viewsPath : [$viewsPath];
        $this->cachePath = $cachePath;

        $this->setMode($mode);

        $this->errorCallBack = fn() => false;

        if (!is_dir($this->cachePath)) {
            if (@mkdir($this->cachePath, 0777, true) === false) {
                $this->showError(
                    'Constructing',
                    "Unable to create the cache folder [$this->cachePath]. Check the permissions of it's parent folder.",
                    true
                );
            }
        }
    }

    /**
     * @param $id
     * @param $text
     * @param bool $critic
     * @param bool $alwaysThrow
     * @return string
     */
    public function showError($id, $text, bool $critic = false, bool $alwaysThrow = false): string
    {
        ob_get_clean();
        if ($alwaysThrow || $critic === true) {
            throw new RuntimeException("View Error [$id] $text");
        }

        $msg = "<div style='background-color: red; color: black; padding: 3px; border: solid 1px black;'>";
        $msg .= "View Error [$id]:<br>";
        $msg .= "<span style='color:white'>$text</span><br></div>\n";
        echo $msg;
        if ($critic) {
            die(1);
        }
        return $msg;
    }

    /**
     * @param $value
     * @return string
     * @noinspection PhpUnused
     */
    public static function e($value): string
    {
        $value ??= '';
        if (is_array($value) || is_object($value)) {
            return htmlentities(print_r($value, true), ENT_QUOTES, 'UTF-8', false);
        }
        if (is_numeric($value)) {
            $value = (string)$value;
        }
        return htmlentities($value, ENT_QUOTES, 'UTF-8', false);
    }

    /**
     * @param $k
     * @param $v
     * @return string
     */
    protected static function convertArgCallBack($k, $v): string
    {
        return $k . "='$v' ";
    }

    /**
     * @param mixed|DateTime $variable
     * @param string|null $format
     * @return string
     * @noinspection PhpUnused
     */
    public function format(mixed $variable, ?string $format = null): string
    {
        if ($variable instanceof DateTime) {
            $format = $format ?? 'Y/m/d';
            return $variable->format($format);
        }
        $format = $format ?? '%s';
        return sprintf($format, $variable);
    }

    /**
     * @param ?string $input
     * @param string $quote
     * @param bool $parse
     * @return string
     * @noinspection PhpUnused
     */
    public function wrapPHP(?string $input, string $quote = '"', bool $parse = true): string
    {
        if ($input === null) {
            return 'null';
        }
        if (str_contains($input, '(') && !$this->isQuoted($input)) {
            if ($parse) {
                return $quote . '<?php echo $this->e(' . $input . ');?>' . $quote;
            }

            return $quote . '<?php echo ' . $input . ';?>' . $quote;
        }
        if (!str_contains($input, '$')) {
            if ($parse) {
                return self::enq($input);
            }

            return $input;
        }
        if ($parse) {
            return $quote . '<?php echo $this->e(' . $input . ');?>' . $quote;
        }
        return $quote . '<?php echo ' . $input . ';?>' . $quote;
    }

    /**
     * @param string|null $text
     * @return bool
     */
    public function isQuoted(?string $text): bool
    {
        if (!$text || strlen($text) < 2) {
            return false;
        }
        if ($text[0] === '"' && str_ends_with($text, '"')) {
            return true;
        }
        return ($text[0] === "'" && str_ends_with($text, "'"));
    }

    /**
     * @param string|array|object $value
     * @return string
     */
    public static function enq(string|array|object $value): string
    {
        if (is_array($value) || is_object($value)) {
            return htmlentities(print_r($value, true), ENT_NOQUOTES, 'UTF-8', false);
        }
        return htmlentities($value ?? '', ENT_NOQUOTES, 'UTF-8', false);
    }

    /**
     * @param string $view
     * @param string|null $alias
     * @noinspection PhpUnused
     */
    public function addInclude(string $view, ?string $alias = null): void
    {
        if (!isset($alias)) {
            $alias = explode('.', $view);
            $alias = end($alias);
        }
        $this->directive($alias, function ($expression) use ($view) {
            $expression = $this->stripParentheses($expression) ?: '[]';
            return "<?php echo \$this->runChild('$view', $expression); ?>";
        });
    }

    /**
     * @param string $name
     * @param callable $handler
     * @return void
     */
    public function directive(string $name, callable $handler): void
    {
        $this->customDirectives[$name] = $handler;
        $this->customDirectivesRT[$name] = false;
    }

    /**
     * @param string|null $expression
     * @return string
     */
    public function stripParentheses(?string $expression): string
    {
        if (is_null($expression)) {
            return '';
        }

        if (static::startsWith($expression, '(')) {
            $expression = substr($expression, 1, -1);
        }

        return $expression;
    }

    /**
     * @param string $haystack
     * @param array|string $needles
     * @return bool
     */
    public static function startsWith(string $haystack, array|string $needles): bool
    {
        foreach ((array)$needles as $needle) {
            if ($needle != '') {
                if (function_exists('mb_strpos')) {
                    if ($haystack !== null && mb_strpos($haystack, $needle) === 0) {
                        return true;
                    }
                } elseif ($haystack !== null && str_starts_with($haystack, $needle)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return array
     * @noinspection PhpUnused
     */
    public function getAliasClasses(): array
    {
        return $this->aliasClasses;
    }

    /**
     * @param array $aliasClasses
     * @noinspection PhpUnused
     */
    public function setAliasClasses(array $aliasClasses): void
    {
        $this->aliasClasses = $aliasClasses;
    }

    /**
     * @param string $aliasName
     * @param string $classWithNS
     * @noinspection PhpUnused
     */
    public function addAliasClasses(string $aliasName, string $classWithNS): void
    {
        $this->aliasClasses[$aliasName] = $classWithNS;
    }

    /**
     * @param User $user
     * @noinspection PhpUnused
     */
    public function setAuth(User $user): void
    {
        $this->currentUser = $user;
    }

    /**
     * @param string $string
     * @param array $data
     * @return string
     * @throws Exception
     * @noinspection PhpUnused
     */
    public function runString(string $string, array $data = []): string
    {
        $php = $this->compileString($string);

        $obLevel = ob_get_level();
        ob_start();
        extract($data, EXTR_SKIP);

        $previousError = error_get_last();

        try {
            @eval('?' . '>' . $php);
        } catch (Exception $e) {
            while (ob_get_level() > $obLevel) {
                ob_end_clean();
            }
            throw $e;
        } catch (ParseError $e) {
            while (ob_get_level() > $obLevel) {
                ob_end_clean();
            }
            $this->showError('runString', $e->getMessage() . ' ' . $e->getCode(), true);
            return '';
        }

        $lastError = error_get_last();
        if ($previousError != $lastError && $lastError['type'] == E_PARSE) {
            while (ob_get_level() > $obLevel) {
                ob_end_clean();
            }
            $this->showError('runString', $lastError['message'] . ' ' . $lastError['type'], true);
            return '';
        }

        return ob_get_clean();
    }

    /**
     * @param string $value
     * @return string
     */
    public function compileString(string $value): string
    {
        $result = '';
        if (str_contains($value, '@verbatim')) {
            $value = $this->storeVerbatimBlocks($value);
        }
        $this->footer = [];
        foreach (token_get_all($value) as $token) {
            $result .= is_array($token) ? $this->parseToken($token) : $token;
        }
        if (!empty($this->verbatimBlocks)) {
            $result = $this->restoreVerbatimBlocks($result);
        }
        if (count($this->footer) > 0) {
            $result = ltrim($result, PHP_EOL)
                . PHP_EOL . implode(PHP_EOL, array_reverse($this->footer));
        }
        return $result;
    }

    /**
     * @param string $value
     * @return string
     */
    protected function storeVerbatimBlocks(string $value): string
    {
        return preg_replace_callback('~(?<!@)@verbatim(.*?)@endverbatim~s', function ($matches) {
            $this->verbatimBlocks[] = $matches[1];
            return $this->verbatimPlaceholder;
        }, $value);
    }

    /**
     * @param array $token
     * @return string
     * @see View::compileStatements
     * @see View::compileExtends
     * @see View::compileComments
     * @see View::compileEchos
     */
    protected function parseToken(array $token): string
    {
        [$id, $content] = $token;
        if ($id == T_INLINE_HTML) {
            foreach (['Extensions', 'Statements', 'Comments', 'Echos'] as $type) {
                $content = $this->{"compile$type"}($content);
            }
        }
        return $content;
    }

    /**
     * @param string $result
     * @return string
     */
    protected function restoreVerbatimBlocks(string $result): string
    {
        $result = preg_replace_callback('~' . preg_quote($this->verbatimPlaceholder) . '~', function () {
            return array_shift($this->verbatimBlocks);
        }, $result);
        $this->verbatimBlocks = [];
        return $result;
    }

    /**
     * @param string $relativeWeb
     * @return string
     * @noinspection PhpUnused
     */
    public function relative(string $relativeWeb): string
    {
        return $this->assetDict[$relativeWeb] ?? ($this->relativePath . $relativeWeb);
    }

    /**
     * @param array|string $name
     * @param string $url
     * @noinspection PhpUnused
     */
    public function addAssetDict(array|string $name, string $url = ''): void
    {
        if (is_array($name)) {
            if ($this->assetDict === null) {
                $this->assetDict = $name;
            } else {
                $this->assetDict = array_merge($this->assetDict, $name);
            }
        } else {
            $this->assetDict[$name] = $url;
        }
    }

    /**
     * @param string $expression
     * @return string
     * @noinspection PhpUnused
     */
    public function compilePush(string $expression): string
    {
        return "<?php \$this->startPush$expression; ?>";
    }

    /**
     * @param string $expression
     * @return string
     * @noinspection PhpUnused
     */
    public function compilePushOnce(string $expression): string
    {
        $key = '$__pushonce__' . trim(substr($expression, 2, -2));
        return "<?php if(!isset($key)): $key=1;  \$this->startPush$expression; ?>";
    }

    /**
     * @param string $expression
     * @return string
     * @noinspection PhpUnused
     */
    public function compilePrepend(string $expression): string
    {
        return "<?php \$this->startPush$expression; ?>";
    }

    /**
     * @param string $section
     * @param string $content
     * @return void
     * @noinspection PhpUnused
     */
    public function startPush(string $section, string $content = ''): void
    {
        if ($content === '') {
            if (ob_start()) {
                $this->pushStack[] = $section;
            }
        } else {
            $this->extendPush($section, $content);
        }
    }

    /**
     * @param string $section
     * @param string $content
     * @return void
     */
    protected function extendPush(string $section, string $content): void
    {
        if (!isset($this->pushes[$section])) {
            $this->pushes[$section] = []; // start an empty section
        }
        if (!isset($this->pushes[$section][$this->renderCount])) {
            $this->pushes[$section][$this->renderCount] = $content;
        } else {
            $this->pushes[$section][$this->renderCount] .= $content;
        }
    }

    /**
     * @param string $section
     * @param string $content
     * @return void
     * @noinspection PhpUnused
     */
    public function startPrepend(string $section, string $content = ''): void
    {
        if ($content === '') {
            if (ob_start()) {
                array_unshift($this->pushStack[], $section);
            }
        } else {
            $this->extendPush($section, $content);
        }
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function stopPush(): string
    {
        if (empty($this->pushStack)) {
            $this->showError('stopPush', 'Cannot end a section without first starting one', true);
        }
        $last = array_pop($this->pushStack);
        $this->extendPush($last, ob_get_clean());
        return $last;
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function stopPrepend(): string
    {
        if (empty($this->pushStack)) {
            $this->showError('stopPrepend', 'Cannot end a section without first starting one', true);
        }
        $last = array_shift($this->pushStack);
        $this->extendStartPush($last, ob_get_clean());
        return $last;
    }

    /**
     * @param string $section
     * @param string $content
     * @return void
     */
    protected function extendStartPush(string $section, string $content): void
    {
        if (!isset($this->pushes[$section])) {
            $this->pushes[$section] = [];
        }
        if (!isset($this->pushes[$section][$this->renderCount])) {
            $this->pushes[$section][$this->renderCount] = $content;
        } else {
            $this->pushes[$section][$this->renderCount] = $content . $this->pushes[$section][$this->renderCount];
        }
    }

    /**
     * @param string $section
     * @param string $default
     * @return string
     * @noinspection PhpUnused
     */
    public function yieldPushContent(string $section, string $default = ''): string
    {
        if (!isset($this->pushes[$section])) {
            return $default;
        }
        return implode(array_reverse($this->pushes[$section]));
    }

    /**
     * @param int|string $each
     * @param string $splitText
     * @param string $splitEnd
     * @return string
     * @noinspection PhpUnused
     */
    public function splitForeach(int|string $each = 1, string $splitText = ',', string $splitEnd = ''): string
    {
        $loopStack = static::last($this->loopsStack);
        if (($loopStack['index']) == $loopStack['count'] - 1) {
            return $splitEnd;
        }
        $eachN = 0;
        if (is_numeric($each)) {
            $eachN = $each;
        } elseif (strlen($each) > 1) {
            if ($each[0] === 'c') {
                $eachN = $loopStack['count'] / substr($each, 1);
            }
        } else {
            $eachN = PHP_INT_MAX;
        }

        if (($loopStack['index'] + 1) % $eachN === 0) {
            return $splitText;
        }
        return '';
    }

    /**
     * @param array $array
     * @param callable|null $callback
     * @param mixed|null $default
     * @return mixed
     */
    public static function last(array $array, callable $callback = null, mixed $default = null): mixed
    {
        if (is_null($callback)) {
            return empty($array) ? static::value($default) : end($array);
        }
        return static::first(array_reverse($array), $callback, $default);
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public static function value(mixed $value): mixed
    {
        return $value instanceof Closure ? $value() : $value;
    }

    /**
     * @param array $array
     * @param callable|null $callback
     * @param mixed|null $default
     * @return mixed
     */
    public static function first(array $array, callable $callback = null, mixed $default = null): mixed
    {
        if (is_null($callback)) {
            return empty($array) ? static::value($default) : reset($array);
        }
        foreach ($array as $key => $value) {
            if ($callback($key, $value)) {
                return $value;
            }
        }
        return static::value($default);
    }

    /**
     * @param string $name
     * @param array $args
     * @return string
     * @throws BadMethodCallException
     */
    public function __call(string $name, array $args)
    {
        if ($name === 'if') {
            return $this->registerIfStatement($args[0] ?? null, $args[1] ?? null);
        }
        $this->showError('call', "function $name is not defined<br>", true, true);
        return '';
    }

    /**
     * @param string $name
     * @param callable $callback
     * @return string
     */
    public function registerIfStatement(string $name, callable $callback): string
    {
        $this->conditions[$name] = $callback;

        $this->directive($name, function ($expression) use ($name) {
            $tmp = $this->stripParentheses($expression);
            return $expression !== ''
                ? "<?php  if (\$this->check('$name', $tmp)): ?>"
                : "<?php  if (\$this->check('$name')): ?>";
        });

        $this->directive('else' . $name, function ($expression) use ($name) {
            $tmp = $this->stripParentheses($expression);
            return $expression !== ''
                ? "<?php  elseif (\$this->check('$name', $tmp)): ?>"
                : "<?php  elseif (\$this->check('$name')): ?>";
        });

        $this->directive('end' . $name, function () {
            return '<?php  endif; ?>';
        });
        return '';
    }

    /**
     * @param string $name
     * @param array $parameters
     * @return bool
     * @noinspection PhpUnused
     */
    public function check(string $name, ...$parameters): bool
    {
        return call_user_func($this->conditions[$name], ...$parameters);
    }

    /**
     * @param bool $bool
     * @param string $view
     * @param array $value
     * @return string
     * @throws Throwable
     * @noinspection PhpUnused
     */
    public function includeWhen(bool $bool = false, string $view = '', array $value = []): string
    {
        if ($bool) {
            return $this->runChild($view, $value);
        }
        return '';
    }

    /**
     * @param       $view
     * @param array $variables
     * @return string
     * @throws Throwable
     */
    public function runChild($view, array $variables = []): string
    {
        if (is_array($variables)) {
            if ($this->includeScope) {
                $backup = $this->variables;
            } else {
                $backup = null;
            }
            $newVariables = array_merge($this->variables, $variables);
        } else {
            if ($variables === null) {
                $newVariables = $this->variables;
                var_dump($newVariables);
                die(1);
            }

            $this->showError('run/include', "RunChild: Include/run variables should be defined as array ['idx'=>'value']", true);
            return '';
        }
        $isFast = $this->mode === Mode::fast;
        $r = $this->runInternal($view, $newVariables, false, false, $isFast);
        if ($backup !== null) {
            $this->variables = $backup;
        }
        return $r;
    }

    /**
     * @param $view
     * @param array $variables
     * @param bool $forced
     * @param bool $isParent
     * @param bool $runFast
     * @return string
     * @throws Throwable
     * @noinspection PhpUnusedParameterInspection
     */
    protected function runInternal($view, array $variables = [], bool $forced = false, bool $isParent = true, bool $runFast = false): string
    {
        if (@count($this->variablesGlobal) > 0) {
            $this->variables = array_merge($variables, $this->variablesGlobal);
        } else {
            $this->variables = $variables;
        }
        if (!$runFast) {
            if ($view) {
                $this->fileName = $view;
            }
            $result = $this->compile($view, $forced);
            if (in_array($this->mode, [Mode::dev, Mode::debug])) {
                return $this->evaluateText($result, $this->variables);
            }
        } elseif ($view) {
            $this->fileName = $view;
        }
        return $this->evaluatePath($this->getCompiledFile(), $this->variables);
    }

    /**
     * @param $templateName
     * @param bool $forced
     * @return bool|string
     */
    public function compile($templateName = null, bool $forced = false): bool|string
    {
        $compiled = $this->getCompiledFile($templateName);
        $template = $this->getViewFile($templateName);
        if (in_array($this->mode, [Mode::dev, Mode::debug])) {
            return $this->compileString($this->getFile($template));
        }
        if ($forced || $this->isExpired($templateName)) {
            $contents = $this->compileString($this->getFile($template));
            $dir = dirname($compiled);
            if (!is_dir($dir)) {
                if (@mkdir($dir, 0777, true) === false) {
                    $this->showError(
                        'Compiling',
                        "Unable to create the compile folder [$dir]. Check the permissions of it's parent folder.",
                        true
                    );
                    return false;
                }
            }
            if ($this->optimize) {
                $contents = preg_replace('~^ {2,}~m', ' ', $contents);
                $contents = preg_replace('~^\t{2,}~m', ' ', $contents);
            }
            if (@file_put_contents($compiled, $contents) === false) {
                $this->showError(
                    'Compiling',
                    "Unable to save the file [$compiled]. Check the compile folder is defined and has the right permission"
                );
                return false;
            }
        }
        return true;
    }

    /**
     * @param string $viewName
     * @return string
     */
    public function getCompiledFile(string $viewName = ''): string
    {
        $viewName = (empty($viewName)) ? $this->fileName : $viewName;
        $style = $this->compileTypeFileName;
        if ($style === CompileFileName::auto) {
            $style = ($this->getMode() === Mode::debug) ? CompileFileName::normal : CompileFileName::sha1;
        }
        return match ($style) {
            CompileFileName::md5 => $this->cachePath . '/' . md5($viewName) . '.viewc',
            CompileFileName::sha1 => $this->cachePath . '/' . sha1($viewName) . '.viewc',
            default => $this->cachePath . '/' . $viewName . '.viewc',
        };
    }

    /**
     * @return Mode
     */
    public function getMode(): Mode
    {
        return $this->mode;
    }

    /**
     * @param Mode $mode
     * @return void
     */
    public function setMode(Mode $mode): static
    {
        $this->mode = $mode;
        return $this;
    }

    /**
     * @param string $viewName
     * @return string
     */
    public function getViewFile(string $viewName = ''): string
    {
        $viewName = (empty($viewName)) ? $this->fileName : $viewName;
        if (str_contains($viewName, '/')) {
            return $this->locateView($viewName);
        }
        $arr = explode('.', $viewName);
        $c = count($arr);
        if ($c == 1) {
            return $this->locateView($viewName . '.view.php');
        }

        $file = $arr[$c - 1];
        array_splice($arr, $c - 1, $c - 1);
        $path = implode('/', $arr);
        return $this->locateView($path . '/' . $file . '.view.php');
    }

    /**
     * @param $name
     * @return string
     */
    protected function locateView($name): string
    {
        $this->notFoundPath = '';
        foreach ($this->viewsPath as $dir) {
            $path = $dir . '/' . $name;
            if (is_file($path)) {
                return $path;
            }

            $this->notFoundPath .= $path . ",";
        }
        return '';
    }

    /**
     * @param $fullFileName
     * @return string
     */
    public function getFile($fullFileName): string
    {
        if (is_file($fullFileName)) {
            return file_get_contents($fullFileName);
        }
        $this->showError('getFile', "File does not exist at paths (separated by comma) [$this->notFoundPath] or permission denied");
        return '';
    }

    /**
     * @param $fileName
     * @return bool
     */
    public function isExpired($fileName): bool
    {
        $compiled = $this->getCompiledFile($fileName);
        $template = $this->getViewFile($fileName);
        if (!is_file($template)) {
            if ($this->mode == Mode::debug) {
                $this->showError('Read file', 'Template not found :' . $this->fileName . " on file: $template", true);
            } else {
                $this->showError('Read file', 'Template not found :' . $this->fileName, true);
            }
        }
        if (!$this->cachePath || !is_file($compiled)) {
            return true;
        }
        return filemtime($compiled) < filemtime($template);
    }

    /**
     * @param $content
     * @param $variables
     * @return string
     * @throws Throwable
     */
    protected function evaluateText($content, $variables): string
    {
        ob_start();
        extract($variables);
        try {
            eval(' ?>' . $content . '<?php ');
        } catch (Exception $e) {
            $this->handleViewException($e);
        }
        return ltrim(ob_get_clean());
    }

    /**
     * @param $e
     * @return void
     * @throws Throwable
     */
    protected function handleViewException($e): void
    {
        ob_get_clean();
        throw $e;
    }

    /**
     * @param $compiledFile
     * @param $variables
     * @return string
     * @throws Throwable
     */
    protected function evaluatePath($compiledFile, $variables): string
    {
        ob_start();
        extract($variables);
        try {
            include $compiledFile;
        } catch (Exception $e) {
            $this->handleViewException($e);
        }
        return ltrim(ob_get_clean());
    }

    /**
     * @param array $views
     * @param array $value
     * @return string
     * @throws Throwable
     * @noinspection PhpUnused
     */
    public function includeFirst(array $views = [], array $value = []): string
    {
        foreach ($views as $view) {
            if ($this->viewExists($view)) {
                return $this->runChild($view, $value);
            }
        }
        return '';
    }

    /**
     * @param $viewName
     * @return bool
     */
    protected function viewExists($viewName): bool
    {
        $file = $this->getViewFile($viewName);
        return is_file($file);
    }

    /**
     * @param array|string $array
     * @return string
     * @noinspection PhpUnused
     */
    public function convertArg(array|string $array): string
    {
        if (!is_array($array)) {
            return $array;
        }
        return implode(' ', array_map([View::class, 'convertArgCallBack'], array_keys($array), $array));
    }

    /**
     * @param bool $fullToken
     * @param string $tokenId
     *
     * @return string
     * @noinspection PhpUnused
     */
    public function getCsrfToken(bool $fullToken = false, string $tokenId = '_token'): string
    {
        if ($this->csrf_token == '') {
            $this->regenerateToken($tokenId);
        }
        if ($fullToken) {
            return $this->csrf_token . '|' . $this->ipClient();
        }
        return $this->csrf_token;
    }

    /**
     * @param string $tokenId
     */
    public function regenerateToken(string $tokenId = '_token'): void
    {
        try {
            $this->csrf_token = bin2hex(random_bytes(10));
        } catch (Exception) {
            $this->csrf_token = '123456789012345678901234567890';
        }
        @$_SESSION[$tokenId] = $this->csrf_token . '|' . $this->ipClient();
    }

    /**
     * @return mixed|string
     */
    public function ipClient(): mixed
    {
        if (
            isset($_SERVER['HTTP_X_FORWARDED_FOR'])
            && preg_match('~^(d{1,3}).(d{1,3}).(d{1,3}).(d{1,3})$~', $_SERVER['HTTP_X_FORWARDED_FOR'])
        ) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        return $_SERVER['REMOTE_ADDR'] ?? '';
    }

    /**
     * @param bool $alwaysRegenerate
     * @param string $tokenId
     *
     * @return bool
     * @noinspection PhpUnused
     */
    public function csrfIsValid(bool $alwaysRegenerate = false, string $tokenId = '_token'): bool
    {
        if (@$_SERVER['REQUEST_METHOD'] === 'POST' && $alwaysRegenerate === false) {
            $this->csrf_token = $_POST[$tokenId] ?? null;
            return $this->csrf_token . '|' . $this->ipClient() === ($_SESSION[$tokenId] ?? null);
        }

        if ($this->csrf_token == '' || $alwaysRegenerate) {
            $this->regenerateToken($tokenId);
        }
        return true;
    }

    /**
     * @return string|null
     * @noinspection PhpUnused
     */
    public function yieldSection(): ?string
    {
        $sc = $this->stopSection();
        return $this->sections[$sc] ?? null;
    }

    /**
     * @param bool $overwrite
     * @return string
     */
    public function stopSection(bool $overwrite = false): string
    {
        if (empty($this->sectionStack)) {
            $this->showError('stopSection', 'Cannot end a section without first starting one.', true, true);
        }
        $last = array_pop($this->sectionStack);
        if ($overwrite) {
            $this->sections[$last] = ob_get_clean();
        } else {
            $this->extendSection($last, ob_get_clean());
        }
        return $last;
    }

    /**
     * @param string $section
     * @param string $content
     * @return void
     */
    protected function extendSection(string $section, string $content): void
    {
        if (isset($this->sections[$section])) {
            $content = str_replace($this->PARENTKEY, $content, $this->sections[$section]);
        }
        $this->sections[$section] = $content;
    }

    /**
     * @param $object
     * @param bool $jsconsole
     * @return void
     * @noinspection PhpUnused
     */
    public function dump($object, bool $jsconsole = false): void
    {
        if (!$jsconsole) {
            echo '<pre>';
            var_dump($object);
            echo '</pre>';
        } else {
            /** @noinspection BadExpressionStatementJS */
            /** @noinspection JSVoidFunctionReturnValueUsed */
            echo '<script>console.log(' . json_encode($object) . ')</script>';
        }
    }

    /**
     * @param string $section
     * @param string $content
     * @return void
     * @noinspection PhpUnused
     */
    public function startSection(string $section, string $content = ''): void
    {
        if ($content === '') {
            ob_start() && $this->sectionStack[] = $section;
        } else {
            $this->extendSection($section, $content);
        }
    }

    /**
     * @return string
     * @throws InvalidArgumentException
     * @noinspection PhpUnused
     */
    public function appendSection(): string
    {
        if (empty($this->sectionStack)) {
            $this->showError('appendSection', 'Cannot end a section without first starting one.', true, true);
        }
        $last = array_pop($this->sectionStack);
        if (isset($this->sections[$last])) {
            $this->sections[$last] .= ob_get_clean();
        } else {
            $this->sections[$last] = ob_get_clean();
        }
        return $last;
    }

    /**
     * @param array|string $varname
     * @param mixed|null $value
     * @return $this
     * @noinspection PhpUnused
     */
    public function with(array|string $varname, mixed $value = null): View
    {
        return $this->share($varname, $value);
    }

    /**
     * @param array|string $varname
     * @param mixed|null $value
     * @return $this
     */
    public function share(array|string $varname, mixed $value = null): View
    {
        if (is_array($varname)) {
            $this->variablesGlobal = array_merge($this->variablesGlobal, $varname);
        } else {
            $this->variablesGlobal[$varname] = $value;
        }
        return $this;
    }

    /**
     * @param string $section
     * @param string $default
     * @return string
     * @noinspection PhpUnused
     */
    public function yieldContent(string $section, string $default = ''): string
    {
        if (isset($this->sections[$section])) {
            return str_replace($this->PARENTKEY, $default, $this->sections[$section]);
        }

        return $default;
    }

    /**
     * @param callable $compiler
     * @return void
     * @noinspection PhpUnused
     */
    public function extend(callable $compiler): void
    {
        $this->extensions[] = $compiler;
    }

    /**
     * @param string $name
     * @param callable $handler
     * @return void
     * @noinspection PhpUnused
     */
    public function directiveRT(string $name, callable $handler): void
    {
        $this->customDirectives[$name] = $handler;
        $this->customDirectivesRT[$name] = true;
    }

    /**
     * @param string $openTag
     * @param string $closeTag
     * @return void
     * @noinspection PhpUnused
     */
    public function setEscapedContentTags(string $openTag, string $closeTag): void
    {
        $this->setContentTags($openTag, $closeTag, true);
    }

    /**
     * @return array
     * @noinspection PhpUnused
     */
    public function getContentTags(): array
    {
        return $this->getTags();
    }

    /**
     * @param string $openTag
     * @param string $closeTag
     * @param bool $escaped
     * @return void
     */
    public function setContentTags(string $openTag, string $closeTag, bool $escaped = false): void
    {
        $property = ($escaped === true) ? 'escapedTags' : 'contentTags';
        $this->{$property} = [preg_quote($openTag), preg_quote($closeTag)];
    }

    /**
     * @param bool $escaped
     * @return array
     */
    protected function getTags(bool $escaped = false): array
    {
        $tags = $escaped ? $this->escapedTags : $this->contentTags;
        return array_map('stripcslashes', $tags);
    }

    /**
     * @return array
     * @noinspection PhpUnused
     */
    public function getEscapedContentTags(): array
    {
        return $this->getTags(true);
    }

    /**
     * @param Closure $function
     * @noinspection PhpUnused
     */
    public function setInjectResolver(Closure $function): void
    {
        $this->injectResolver = $function;
    }

    /**
     * @return CompileFileName
     * @see View::setCompileTypeFileName
     * @noinspection PhpUnused
     */
    public function getCompileTypeFileName(): CompileFileName
    {
        return $this->compileTypeFileName;
    }

    /**
     * @param CompileFileName $compileTypeFileName
     * @return View
     */
    public function setCompileTypeFileName(CompileFileName $compileTypeFileName): View
    {
        $this->compileTypeFileName = $compileTypeFileName;
        return $this;
    }

    /**
     * @param Countable|array $data
     * @return void
     * @noinspection PhpUnused
     */
    public function addLoop(mixed $data): void
    {
        $length = is_array($data) || $data instanceof Countable ? count($data) : null;
        $parent = static::last($this->loopsStack);
        $this->loopsStack[] = [
            'index' => -1,
            'iteration' => 0,
            'remaining' => isset($length) ? $length + 1 : null,
            'count' => $length,
            'first' => true,
            'even' => true,
            'odd' => false,
            'last' => isset($length) ? $length == 1 : null,
            'depth' => count($this->loopsStack) + 1,
            'parent' => $parent ? (object)$parent : null,
        ];
    }

    /**
     * @return object
     * @noinspection PhpUnused
     */
    public function incrementLoopIndices(): object
    {
        $c = count($this->loopsStack) - 1;
        $loop = &$this->loopsStack[$c];

        $loop['index']++;
        $loop['iteration']++;
        $loop['first'] = $loop['index'] == 0;
        $loop['even'] = $loop['index'] % 2 == 0;
        $loop['odd'] = !$loop['even'];
        if (isset($loop['count'])) {
            $loop['remaining']--;
            $loop['last'] = $loop['index'] == $loop['count'] - 1;
        }
        return (object)$loop;
    }

    /**
     * @return void
     * @noinspection PhpUnused
     */
    public function popLoop(): void
    {
        array_pop($this->loopsStack);
    }

    /**
     * @return object|null
     * @noinspection PhpUnused
     */
    public function getFirstLoop(): ?object
    {
        return ($last = static::last($this->loopsStack)) ? (object)$last : null;
    }

    /**
     * @param string $view
     * @param array $data
     * @param string $iterator
     * @param string $empty
     * @return string
     * @throws Throwable
     * @noinspection PhpUnused
     */
    public function renderEach(string $view, array $data, string $iterator, string $empty = 'raw|'): string
    {
        $result = '';

        if (count($data) > 0) {
            foreach ($data as $key => $value) {
                $data = ['key' => $key, $iterator => $value];
                $result .= $this->runChild($view, $data);
            }
        } elseif (static::startsWith($empty, 'raw|')) {
            $result = substr($empty, 4);
        } else {
            $result = $this->run($empty);
        }
        return $result;
    }

    /**
     * @param string|null $view
     * @param array $variables
     * @return string
     * @throws Throwable
     */
    public function run(?string $view = null, array $variables = []): string
    {
        $mode = $this->getMode();

        if ($view === null) {
            $view = $this->viewStack;
        }
        $this->viewStack = null;
        if ($view === null) {
            $this->showError('run', 'View: view not set', true);
            return '';
        }

        $forced = in_array($mode, [Mode::dev, Mode::debug]);
        $runFast = $mode === Mode::fast;
        $this->sections = [];
        return $this->runInternal($view, $variables, $forced, true, $runFast);
    }

    /**
     * @param $view
     * @return $this
     * @noinspection PhpUnused
     */
    public function setView($view): View
    {
        $this->viewStack = $view;
        return $this;
    }

    /**
     * @param string $name
     * @param array $data
     * @return void
     * @noinspection PhpUnused
     */
    public function startComponent(string $name, array $data = []): void
    {
        if (ob_start()) {
            $this->componentStack[] = $name;

            $this->componentData[$this->currentComponent()] = $data;

            $this->slots[$this->currentComponent()] = [];
        }
    }

    /**
     * @return int
     */
    protected function currentComponent(): int
    {
        return count($this->componentStack) - 1;
    }

    /**
     * @return string
     * @throws Throwable
     * @noinspection PhpUnused
     */
    public function renderComponent(): string
    {
        $name = array_pop($this->componentStack);

        $cd = $this->componentData();
        $clean = array_keys($cd);
        $r = $this->runChild($name, $cd);

        foreach ($clean as $key) {
            unset($this->variables[$key]);
        }
        return $r;
    }

    /**
     * @return array
     */
    protected function componentData(): array
    {
        $cs = count($this->componentStack);
        return array_merge(
            $this->componentData[$cs],
            ['slot' => trim(ob_get_clean())],
            $this->slots[$cs]
        );
    }

    /**
     * @param string $name
     * @param string|null $content
     * @return void
     * @noinspection PhpUnused
     */
    public function slot(string $name, string $content = null): void
    {
        if (count(func_get_args()) === 2) {
            $this->slots[$this->currentComponent()][$name] = $content;
        } elseif (ob_start()) {
            $this->slots[$this->currentComponent()][$name] = '';

            $this->slotStack[$this->currentComponent()][] = $name;
        }
    }

    /**
     * @return void
     * @noinspection PhpUnused
     */
    public function endSlot(): void
    {
        static::last($this->componentStack);

        $currentSlot = array_pop(
            $this->slotStack[$this->currentComponent()]
        );

        $this->slots[$this->currentComponent()][$currentSlot] = trim(ob_get_clean());
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * @param string $baseUrl
     * @return View
     * @noinspection PhpUnused
     */
    public function setBaseUrl(string $baseUrl): View
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->baseDomain = @parse_url($this->baseUrl)['host'];
        $currentUrl = $this->getCurrentUrlCalculated();
        if ($currentUrl === '') {
            $this->relativePath = '';
            return $this;
        }
        if (str_starts_with($currentUrl, $this->baseUrl)) {
            $part = str_replace($this->baseUrl, '', $currentUrl);
            $numf = substr_count($part, '/') - 1;
            $numf = ($numf > 10) ? 10 : $numf;
            $this->relativePath = ($numf < 0) ? '' : str_repeat('../', $numf);
        } else {
            $this->relativePath = '';
        }
        return $this;
    }

    /**
     * @param bool $noArgs
     * @return string
     */
    public function getCurrentUrlCalculated(bool $noArgs = false): string
    {
        if (!isset($_SERVER['HTTP_HOST'], $_SERVER['REQUEST_URI'])) {
            return '';
        }
        $host = $this->baseDomain ?? $_SERVER['HTTP_HOST'];
        $link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http');
        $port = $_SERVER['SERVER_PORT'];
        $port2 = (($link === 'http' && $port === '80') || ($link === 'https' && $port === '443')) ? '' : ':' . $port;
        $link .= "://$host$port2$_SERVER[REQUEST_URI]";
        if ($noArgs) {
            $link = @explode('?', $link)[0];
        }
        return $link;
    }

    /**
     * @return string
     * @see View::setBaseUrl
     * @noinspection PhpUnused
     */
    public function getRelativePath(): string
    {
        return $this->relativePath;
    }

    /**
     * @return string|null
     * @noinspection PhpUnused
     */
    public function getCanonicalUrl(): ?string
    {
        return $this->canonicalUrl ?? $this->getCurrentUrl();
    }

    /**
     * @param string|null $canonUrl
     * @return View
     * @noinspection PhpUnused
     */
    public function setCanonicalUrl(string $canonUrl = null): View
    {
        $this->canonicalUrl = $canonUrl;
        return $this;
    }

    /**
     * @param bool $noArgs if true then it ignores the arguments.
     * @return string|null
     */
    public function getCurrentUrl(bool $noArgs = false): ?string
    {
        $link = $this->currentUrl ?? $this->getCurrentUrlCalculated();
        if ($noArgs) {
            $link = @explode('?', $link)[0];
        }
        return $link;
    }

    /**
     * @param string|null $currentUrl
     * @return View
     * @noinspection PhpUnused
     */
    public function setCurrentUrl(string $currentUrl = null): View
    {
        $this->currentUrl = $currentUrl;
        return $this;
    }

    /**
     * @param bool $bool
     * @return View
     * @noinspection PhpUnused
     */
    public function setOptimize(bool $bool = false): View
    {
        $this->optimize = $bool;
        return $this;
    }

    /**
     * @param callable $fn
     * @noinspection PhpUnused
     */
    public function setErrorFunction(callable $fn): void
    {
        $this->errorCallBack = $fn;
    }

    /**
     * @return array
     * @noinspection PhpUnused
     */
    public function getLoopStack(): array
    {
        return $this->loopsStack;
    }

    /**
     * @param $quoted
     * @param $newFragment
     * @return string
     * @noinspection PhpUnused
     */
    public function addInsideQuote($quoted, $newFragment): string
    {
        if ($this->isQuoted($quoted)) {
            return substr($quoted, 0, -1) . $newFragment . substr($quoted, -1);
        }
        return $quoted . $newFragment;
    }

    /**
     * @param string|null $text
     * @return bool
     * @noinspection PhpUnused
     */
    public function isVariablePHP(?string $text): bool
    {
        if (!$text || strlen($text) < 2) {
            return false;
        }
        return $text[0] === '$';
    }

    /**
     * @param $expression
     * @return string
     * @see View::getCanonicalUrl
     * @noinspection PhpUnused
     * @noinspection PhpUnusedParameterInspection
     */
    public function compileCanonical($expression = null): string
    {
        /** @noinspection HtmlUnknownTarget */
        return '<link rel="canonical" href="<?php echo $this->getCanonicalUrl();?>" />';
    }

    /**
     * @param $expression
     * @return string
     * @see View::getBaseUrl()
     * @noinspection PhpUnusedParameterInspection
     * @noinspection PhpUnused
     */
    public function compileBase($expression = null): string
    {
        /** @noinspection HtmlUnknownTarget */
        /** @noinspection HtmlUnknownAttribute */
        return '<base rel="canonical" href="<?php echo $this->getBaseUrl() ;?>" />';
    }

    /**
     * @param $text
     * @param string $separator
     * @return array
     * @noinspection PhpUnused
     */
    public function parseArgsOld($text, string $separator = ','): array
    {
        if ($text === null || $text === '') {
            return [];
        }
        $chars = str_split($text);
        $parts = [];
        $nextpart = '';
        $strL = count($chars);
        /** @noinspection ForeachInvariantsInspection */
        for ($i = 0; $i < $strL; $i++) {
            $char = $chars[$i];
            if ($char === '"' || $char === "'") {
                $inext = strpos($text, $char, $i + 1);
                $inext = $inext === false ? $strL : $inext;
                $nextpart .= substr($text, $i, $inext - $i + 1);
                $i = $inext;
            } else {
                $nextpart .= $char;
            }
            if ($char === $separator) {
                $parts[] = substr($nextpart, 0, -1);
                $nextpart = '';
            }
        }
        if ($nextpart !== '') {
            $parts[] = $nextpart;
        }
        $result = [];
        foreach ($parts as $part) {
            $r = explode('=', $part, 2);
            $result[trim($r[0])] = count($r) === 2 ? trim($r[1]) : null;
        }
        return $result;
    }

    /**
     * @param $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileUse($expression): string
    {
        return '<?php use ' . $this->stripParentheses($expression) . '; ?>';
    }

    /**
     * @param $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileSwitch($expression): string
    {
        $this->switchCount++;
        $this->firstCaseInSwitch = true;
        return "<?php switch $expression {";
    }

    /**
     * @param $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileDump($expression): string
    {
        return "<?php echo \$this->dump$expression;?>";
    }

    /**
     * @param $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileRelative($expression): string
    {
        return "<?php echo \$this->relative$expression;?>";
    }

    /**
     * @param $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileMethod($expression): string
    {
        $v = $this->stripParentheses($expression);

        /** @noinspection HtmlUnknownTarget */
        return "<input type='hidden' name='_method' value='<?php echo $v; " . "?>'/>";
    }

    /**
     * @param string|null $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compilecsrf(?string $expression = null): string
    {
        $expression = $expression ?? "'_token'";

        /** @noinspection HtmlUnknownTarget */
        return "<input type='hidden' name='<?php echo $expression; ?>' value='<?php echo \$this->csrf_token; " . "?>'/>";
    }

    /**
     * @param $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileDd($expression): string
    {
        return "<?php echo '<pre>'; var_dump$expression; echo '</pre>';?>";
    }

    /**
     * @param $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileCase($expression): string
    {
        if ($this->firstCaseInSwitch) {
            $this->firstCaseInSwitch = false;
            return 'case ' . $expression . ': ?>';
        }
        return "<?php case $expression: ?>";
    }

    /**
     * @param string $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileWhile(string $expression): string
    {
        return "<?php while$expression: ?>";
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileDefault(): string
    {
        if ($this->firstCaseInSwitch) {
            return $this->showError('@default', '@switch without any @case', true);
        }
        return '<?php default: ?>';
    }

    /**
     * @return string
     * @noinspection PhpUnused
     * @noinspection PhpUnused
     */
    protected function compileEndSwitch(): string
    {
        --$this->switchCount;
        if ($this->switchCount < 0) {
            return $this->showError('@endswitch', 'Missing @switch', true);
        }
        return '<?php } ?>';
    }

    /**
     * Compile while statements into valid PHP.
     *
     * @param string $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileInject(string $expression): string
    {
        $ex = $this->stripParentheses($expression);
        $p0 = strpos($ex, ',');
        if (!$p0) {
            $var = $this->stripQuotes($ex);
            $namespace = '';
        } else {
            $var = $this->stripQuotes(substr($ex, 0, $p0));
            $namespace = $this->stripQuotes(substr($ex, $p0 + 1));
        }
        return "<?php \$$var = \$this->injectClass('$namespace', '$var'); ?>";
    }

    /**
     * @param mixed $text
     * @return null|string|string[]
     */
    public function stripQuotes(mixed $text): array|string|null
    {
        if (!$text || strlen($text) < 2) {
            return $text;
        }
        $text = trim($text);
        $p0 = $text[0];
        $p1 = substr($text, -1);
        if ($p0 === $p1 && ($p0 === '"' || $p0 === "'")) {
            return substr($text, 1, -1);
        }
        return $text;
    }

    /**
     * @param string $value
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileExtensions(string $value): string
    {
        foreach ($this->extensions as $compiler) {
            $value = $compiler($value, $this);
        }
        return $value;
    }

    /**
     * @param string $value
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileComments(string $value): string
    {
        $pattern = sprintf('~%s--(.*?)--%s~s', $this->contentTags[0], $this->contentTags[1]);
        return preg_replace($pattern, '<?php /*$1*/ ?>', $value);
    }

    /**
     * @param string $value
     * @return string
     * @throws Exception
     * @noinspection PhpUnused
     */
    protected function compileEchos(string $value): string
    {
        foreach ($this->getEchoMethods() as $method => $length) {
            $value = $this->$method($value);
        }
        return $value;
    }

    /**
     * @return array
     */
    protected function getEchoMethods(): array
    {
        $methods = [
            'compileRawEchos' => strlen(stripcslashes($this->rawTags[0])),
            'compileEscapedEchos' => strlen(stripcslashes($this->escapedTags[0])),
            'compileRegularEchos' => strlen(stripcslashes($this->contentTags[0])),
        ];
        uksort($methods, static function ($method1, $method2) use ($methods) {
            if ($methods[$method1] > $methods[$method2]) {
                return -1;
            }
            if ($methods[$method1] < $methods[$method2]) {
                return 1;
            }
            if ($method1 === 'compileRawEchos') {
                return -1;
            }
            if ($method2 === 'compileRawEchos') {
                return 1;
            }
            if ($method1 === 'compileEscapedEchos') {
                return -1;
            }
            if ($method2 === 'compileEscapedEchos') {
                return 1;
            }
            throw new BadMethodCallException("Method [$method1] not defined");
        });
        return $methods;
    }

    /**
     * @param string $value
     * @return array|string|string[]|null
     * @noinspection PhpUnused
     */
    protected function compileStatements(string $value): array|string|null
    {
        /**
         * @param array $match
         * @return mixed|string
         */
        $callback = function (array $match) {
            if (static::contains($match[1], '@')) {
                $match[0] = isset($match[3]) ? $match[1] . $match[3] : $match[1];
            } else {
                if (str_contains($match[1], '::')) {
                    return $this->compileStatementClass($match);
                }
                if (isset($this->customDirectivesRT[$match[1]])) {
                    if ($this->customDirectivesRT[$match[1]]) {
                        $match[0] = $this->compileStatementCustom($match);
                    } else {
                        $match[0] = call_user_func(
                            $this->customDirectives[$match[1]],
                            $this->stripParentheses(static::get($match, 3))
                        );
                    }
                } elseif (method_exists($this, $method = 'compile' . ucfirst($match[1]))) {
                    $match[0] = $this->$method(static::get($match, 3));
                } else {
                    return $match[0];
                }
            }
            return isset($match[3]) ? $match[0] : $match[0] . $match[2];
        };

        /** @noinspection PhpArgumentWithoutNamedIdentifierInspection */
        /** @noinspection Annotator */
        return preg_replace_callback('~\B@(@?\w+(?:::\w+)?)([ \t]*)(\( ( (?>[^()]+) | (?3) )* \))?~x', $callback, $value);
/*        return preg_replace_callback('~\B@(@?\w+(?:::\w+)?)([ \t]*)(\( ( (?>[^()]+) )* \))?~x', $callback, $value);*/
    }

    /**
     * @param string $haystack
     * @param array|string $needles
     * @return bool
     */
    public static function contains(string $haystack, array|string $needles): bool
    {
        foreach ((array)$needles as $needle) {
            if ($needle != '') {
                if (function_exists('mb_strpos')) {
                    if (mb_strpos($haystack, $needle) !== false) {
                        return true;
                    }
                } elseif (str_contains($haystack, $needle)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param $match
     * @return string
     */
    protected function compileStatementClass($match): string
    {
        if (isset($match[3])) {
            return '<?php echo ' . $this->fixNamespaceClass($match[1]) . $match[3] . '; ?>';
        }

        return '<?php echo ' . $this->fixNamespaceClass($match[1]) . '(); ?>';
    }

    /**
     * @param string $text
     * @return string
     * @see View::$aliasClasses
     */
    protected function fixNamespaceClass(string $text): string
    {
        if (!str_contains($text, '::')) {
            return $text;
        }
        $classPart = explode('::', $text, 2);
        if (isset($this->aliasClasses[$classPart[0]])) {
            $classPart[0] = $this->aliasClasses[$classPart[0]];
        }
        return $classPart[0] . '::' . $classPart[1];
    }

    /**
     * @param $match
     * @return string
     */
    protected function compileStatementCustom($match): string
    {
        $v = $this->stripParentheses(static::get($match, 3));
        $v = ($v == '') ? '' : ',' . $v;
        return '<?php call_user_func($this->customDirectives[\'' . $match[1] . '\']' . $v . '); ?>';
    }

    /**
     * @param ArrayAccess|array $array
     * @param string|null $key
     * @param mixed|null $default
     * @return mixed
     */
    public static function get(ArrayAccess|array $array, ?string $key, mixed $default = null): mixed
    {
        $accesible = is_array($array) || $array instanceof ArrayAccess;
        if (!$accesible) {
            return static::value($default);
        }
        if (is_null($key)) {
            return $array;
        }
        if (static::exists($array, $key)) {
            return $array[$key];
        }
        foreach (explode('.', $key) as $segment) {
            if (static::exists($array, $segment)) {
                $array = $array[$segment];
            } else {
                return static::value($default);
            }
        }
        return $array;
    }

    /**
     * @param ArrayAccess|array $array
     * @param int|string $key
     * @return bool
     */
    public static function exists(ArrayAccess|array $array, int|string $key): bool
    {
        if ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }
        return array_key_exists($key, $array);
    }

    /**
     * @param string $expression
     * @return array
     * @noinspection PhpUnused
     */
    protected function getArgs(string $expression): array
    {
        return $this->parseArgs($this->stripParentheses($expression), ' ');
    }

    /**
     * @param $text
     * @param string $separator
     * @param string $assigment
     * @param bool $emptyKey
     * @return array
     */
    public function parseArgs($text, string $separator = ',', string $assigment = '=', bool $emptyKey = true): array
    {
        if ($text === null || $text === '') {
            return [];
        }
        $chars = $text;
        $parts = [];
        $nextpart = '';
        $strL = strlen($chars);
        $stringArr = '"\'¬';
        $parenthesis = '([{';
        $parenthesisClose = ')]}';
        $insidePar = false;
        for ($i = 0; $i < $strL; $i++) {
            $char = $chars[$i];
            $pp = strpos($parenthesis, $char);
            if ($pp !== false) {
                $insidePar = $parenthesisClose[$pp];
            }
            if ($char === $insidePar) {
                $insidePar = false;
            }
            if (str_contains($stringArr, $char)) {
                $inext = strpos($text, $char, $i + 1);
                $inext = $inext === false ? $strL : $inext;
                $nextpart .= substr($text, $i, $inext - $i + 1);
                $i = $inext;
            } else {
                $nextpart .= $char;
            }
            if ($char === $separator && !$insidePar) {
                $parts[] = substr($nextpart, 0, -1);
                $nextpart = '';
            }
        }
        if ($nextpart !== '') {
            $parts[] = $nextpart;
        }
        $result = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part) {
                $char = $part[0];
                if (str_contains($stringArr, $char)) {
                    if ($emptyKey) {
                        $result[$part] = null;
                    } else {
                        $result[] = $part;
                    }
                } else {
                    $r = explode($assigment, $part, 2);
                    if (count($r) === 2) {
                        $result[trim($r[0])] = trim($r[1]);
                    } elseif ($emptyKey) {
                        $result[trim($r[0])] = null;
                    } else {
                        $result[] = trim($r[0]);
                    }
                }
            }
        }
        return $result;
    }

    /**
     * @param string $value
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileRawEchos(string $value): string
    {
        $pattern = sprintf('~(@)?%s\s*(.+?)\s*%s(\r?\n)?~s', $this->rawTags[0], $this->rawTags[1]);
        $callback = function ($matches) {
            $whitespace = empty($matches[3]) ? '' : $matches[3] . $matches[3];
            return $matches[1] ? substr(
                $matches[0],
                1
            ) : '<?php echo ' . $this->compileEchoDefaults($matches[2]) . '; ?>' . $whitespace;
        };
        return preg_replace_callback($pattern, $callback, $value);
    }

    /**
     * @param string $value
     * @return string
     */
    protected function compileEchoDefaults(string $value): string
    {
        /** @noinspection PhpArgumentWithoutNamedIdentifierInspection */
        /** @noinspection Annotator */
        $result = preg_replace('~^(?=\$)(.+?)s+or\s+(.+?)$~s', 'isset($1) ? $1 : $2', $value);
        return $this->fixNamespaceClass($result);
    }

    /**
     * @param string $value
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileRegularEchos(string $value): string
    {
        $pattern = sprintf('~(@)?%s\s*(.+?)\s*%s(\r?\n)?~s', $this->contentTags[0], $this->contentTags[1]);
        $callback = function ($matches) {
            $whitespace = empty($matches[3]) ? '' : $matches[3] . $matches[3];
            $wrapped = sprintf($this->echoFormat, $this->compileEchoDefaults($matches[2]));
            return $matches[1] ? substr($matches[0], 1) : '<?php echo ' . $wrapped . '; ?>' . $whitespace;
        };
        return preg_replace_callback($pattern, $callback, $value);
    }

    /**
     * @param string $value
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileEscapedEchos(string $value): string
    {
        $pattern = sprintf('~(@)?%s\s*(.+?)\s*%s(\r?\n)?~s', $this->escapedTags[0], $this->escapedTags[1]);
        $callback = function ($matches) {
            $whitespace = empty($matches[3]) ? '' : $matches[3] . $matches[3];

            return $matches[1] ? $matches[0] : '<?php '
                . sprintf($this->echoFormat, $this->compileEchoDefaults($matches[2])) . '; ?>'
                . $whitespace;
        };
        return preg_replace_callback($pattern, $callback, $value);
    }

    /**
     * @param string $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileEach(string $expression): string
    {
        return "<?php echo \$this->renderEach$expression; ?>";
    }

    /**
     * @param $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileSet($expression): string
    {
        $segments = explode('=', $this->stripParentheses($expression));
        $value = (count($segments) >= 2) ? '=@' . implode('=', array_slice($segments, 1)) : '++';
        return '<?php ' . trim($segments[0]) . $value . ';?>';
    }

    /**
     * @param string $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileYield(string $expression): string
    {
        return "<?php echo \$this->yieldContent$expression; ?>";
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileShow(): string
    {
        return '<?php echo $this->yieldSection(); ?>';
    }

    /**
     * @param string $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileSection(string $expression): string
    {
        return "<?php \$this->startSection$expression; ?>";
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileAppend(): string
    {
        return '<?php $this->appendSection(); ?>';
    }

    /**
     * @param string|null $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileAuth(?string $expression = ''): string
    {
        $role = $this->stripParentheses($expression);
        if ($role == '') {
            return '<?php if(isset($this->currentUser)): ?>';
        }

        return "<?php if(isset(\$this->currentUser) && \$this->currentRole==$role): ?>";
    }

    /**
     * @param string $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileElseAuth(string $expression = ''): string
    {
        $role = $this->stripParentheses($expression);
        if ($role == '') {
            return '<?php else: ?>';
        }

        return "<?php elseif(isset(\$this->currentUser) && \$this->currentRole==$role): ?>";
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileEndAuth(): string
    {
        return '<?php endif; ?>';
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileGuest(): string
    {
        return '<?php if(!isset($this->currentUser)): ?>';
    }

    /**
     * @param $expression
     * @return string
     * @noinspection PhpUnused
     * @noinspection PhpUnusedParameterInspection
     */
    protected function compileElseGuest($expression): string
    {
        return '<?php else: ?>';
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileEndGuest(): string
    {
        return '<?php endif; ?>';
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileEndsection(): string
    {
        return '<?php $this->stopSection(); ?>';
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileStop(): string
    {
        return '<?php $this->stopSection(); ?>';
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileOverwrite(): string
    {
        return '<?php $this->stopSection(true); ?>';
    }

    /**
     * @param string $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileUnless(string $expression): string
    {
        return "<?php if ( ! $expression): ?>";
    }

    /**
     * Compile the User statements into valid PHP.
     *
     * @return string
     */
    protected function compileUser(): string
    {
        return "<?php echo '" . $this->currentUser?->email . "'; ?>";
    }


    /**
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileEndunless(): string
    {
        return '<?php endif; ?>';
    }

    /**
     * @param $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileError($expression): string
    {
        $key = $this->stripParentheses($expression);
        return '<?php $message = call_user_func($this->errorCallBack,' . $key . '); if ($message): ?>';
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileEndError(): string
    {
        return '<?php endif; ?>';
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileElse(): string
    {
        return '<?php else: ?>';
    }

    /**
     * @param string $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileFor(string $expression): string
    {
        return "<?php for$expression: ?>";
    }

    /**
     * @param string|null $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileForeach(?string $expression): string
    {
        if ($expression === null) {
            return '@foreach';
        }
        preg_match('~\( *(.*) * as *([^)]*)~', $expression, $matches);
        $iteratee = trim($matches[1]);
        $iteration = trim($matches[2]);
        $initLoop = "\$__currentLoopData = $iteratee; \$this->addLoop(\$__currentLoopData);\$this->getFirstLoop();\n";
        $iterateLoop = '$loop = $this->incrementLoopIndices(); ';
        return "<?php $initLoop foreach(\$__currentLoopData as $iteration): $iterateLoop ?>";
    }

    /**
     * @param string $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileSplitForeach(string $expression): string
    {
        return '<?php echo $this::splitForeach' . $expression . '; ?>';
    }

    /**
     * @param string|null $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileBreak(?string $expression): string
    {
        return $expression ? "<?php if$expression break; ?>" : '<?php break; ?>';
    }

    /**
     * @param string|null $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileContinue(?string $expression): string
    {
        return $expression ? "<?php if$expression continue; ?>" : '<?php continue; ?>';
    }

    /**
     * @param string $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileForelse(string $expression): string
    {
        $empty = '$__empty_' . ++$this->forelseCounter;
        return "<?php $empty = true; foreach$expression: $empty = false; ?>";
    }

    /**
     * @param string $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileIf(string $expression): string
    {
        return "<?php if$expression: ?>";
    }

    /**
     * @param string $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileElseif(string $expression): string
    {
        return "<?php elseif$expression: ?>";
    }

    /**
     * @param string $expression empty if it's inside a for loop.
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileEmpty(string $expression = ''): string
    {
        if ($expression == '') {
            $empty = '$__empty_' . $this->forelseCounter--;
            return "<?php endforeach; if ($empty): ?>";
        }
        return "<?php if (empty$expression): ?>";
    }

    /**
     * @param string $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileHasSection(string $expression): string
    {
        return "<?php if (! empty(trim(\$this->yieldContent$expression))): ?>";
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileEndwhile(): string
    {
        return '<?php endwhile; ?>';
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileEndfor(): string
    {
        return '<?php endfor; ?>';
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileEndforeach(): string
    {
        return '<?php endforeach; $this->popLoop(); $loop = $this->getFirstLoop(); ?>';
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileEndif(): string
    {
        return '<?php endif; ?>';
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileEndforelse(): string
    {
        return '<?php endif; ?>';
    }

    /**
     * @param string|null $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compilePhp(?string $expression): string
    {
        return $expression ? "<?php $expression; ?>" : "<?php ";
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileEndphp(): string
    {
        return ' ?>';
    }

    /**
     * @param string $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileUnset(string $expression): string
    {
        return "<?php unset$expression; ?>";
    }

    /**
     * @param string $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileExtends(string $expression): string
    {
        $expression = $this->stripParentheses($expression);
        $this->uidCounter++;
        $data = '<?php if (isset($_shouldextend[' . $this->uidCounter . '])) { echo $this->runChild(' . $expression . '); } ?>';
        $this->footer[] = $data;
        return '<?php $_shouldextend[' . $this->uidCounter . ']=1; ?>';
    }


    /**
     * @return string
     * @see extendSection
     * @noinspection PhpUnused
     */
    protected function compileParent(): string
    {
        return $this->PARENTKEY;
    }

    /**
     * @param string $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileInclude(string $expression): string
    {
        $expression = $this->stripParentheses($expression);
        return '<?php echo $this->runChild(' . $expression . '); ?>';
    }

    /**
     * @param $expression
     * @return string
     * @throws Exception
     * @noinspection PhpUnused
     */
    protected function compileIncludeFast($expression): string
    {
        $expression = $this->stripParentheses($expression);
        $ex = $this->stripParentheses($expression);
        $exp = explode(',', $ex);
        $file = $this->stripQuotes($exp[0] ?? null);
        $fileC = $this->getCompiledFile($file);
        if (!@is_file($fileC)) {
            $this->compile($file, true);
        }
        return $this->getFile($fileC);
    }

    /**
     * @param string $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileIncludeIf(string $expression): string
    {
        return '<?php if ($this->templateExist' . $expression . ') echo $this->runChild' . $expression . '; ?>';
    }

    /**
     * @param string $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileIncludeWhen(string $expression): string
    {
        $expression = $this->stripParentheses($expression);
        return '<?php echo $this->includeWhen(' . $expression . '); ?>';
    }

    /**
     * @param string $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileIncludeFirst(string $expression): string
    {
        $expression = $this->stripParentheses($expression);
        return '<?php echo $this->includeFirst(' . $expression . '); ?>';
    }

    /**
     * @param string $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileCompileStamp(string $expression): string
    {
        $expression = $this->stripQuotes($this->stripParentheses($expression));
        $expression = ($expression === '') ? 'Y-m-d H:i:s' : $expression;
        return date($expression);
    }

    /**
     * @param mixed $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileViewName(mixed $expression): string
    {
        $expression = $this->stripQuotes($this->stripParentheses($expression));
        return match ($expression) {
            'compiled' => $this->getCompiledFile($this->fileName),
            'template' => $this->getViewFile($this->fileName),
            default => $this->fileName,
        };
    }

    /**
     * @param string $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileStack(string $expression): string
    {
        return "<?php echo \$this->yieldPushContent$expression; ?>";
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileEndpush(): string
    {
        return '<?php $this->stopPush(); ?>';
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileEndpushOnce(): string
    {
        return '<?php $this->stopPush(); endif; ?>';
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileEndPrepend(): string
    {
        return '<?php $this->stopPrepend(); ?>';
    }

    /**
     * @param string $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileComponent(string $expression): string
    {
        return "<?php  \$this->startComponent$expression; ?>";
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileEndComponent(): string
    {
        return '<?php echo $this->renderComponent(); ?>';
    }

    /**
     * @param string $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileSlot(string $expression): string
    {
        return "<?php  \$this->slot$expression; ?>";
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileEndSlot(): string
    {
        return '<?php  $this->endSlot(); ?>';
    }

    /**
     * @param $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileAsset($expression): string
    {
        return "<?php echo (isset(\$this->assetDict[$expression]))?\$this->assetDict[$expression]:\$this->baseUrl.'/'.$expression; ?>";
    }

    /**
     * @param $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileJSon($expression): string
    {
        $parts = explode(',', $this->stripParentheses($expression));
        $options = isset($parts[1]) ? trim($parts[1]) : JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
        $depth = isset($parts[2]) ? trim($parts[2]) : 512;
        return "<?php echo json_encode($parts[0], $options, $depth); ?>";
    }

    /**
     * @param $expression
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileIsset($expression): string
    {
        return "<?php if(isset$expression): ?>";
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileEndIsset(): string
    {
        return '<?php endif; ?>';
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    protected function compileEndEmpty(): string
    {
        return '<?php endif; ?>';
    }


    /**
     * @param string $className
     * @param string|null $variableName
     * @return mixed
     * @noinspection PhpUnused
     */
    protected function injectClass(string $className, string $variableName = null): mixed
    {
        if (isset($this->injectResolver)) {
            return call_user_func($this->injectResolver, $className, $variableName);
        }

        $fullClassName = $className . "\\" . $variableName;
        return new $fullClassName();
    }

}
