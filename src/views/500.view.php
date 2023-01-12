<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE-edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Not Found</title>
</head>
<body>
    <h2>500 - Internal Server Error</h2>
    <h3> ❌ {{ get_class($exception) }}: {{ $exception->getMessage() }} in {{ $exception->getFile() }}:{{ $exception->getLine() }} </h3>
    <br/>
    <table>
        <thead>
        <tr>
            <th scope="col">#</th>
            <th scope="col">file</th>
            <th scope="col">call</th>
        </tr>
        </thead>
        <tbody>
        @foreach($exception->getTrace() as $index => $trace)
            <tr>
                <th scope="row">
                    {{ $index }}
                </th>
                <td>
                    {{ $trace['file'] }}:{{ $trace['line'] }}
                </td>
                <td>
                    {{ $trace['class'] }}{{ $trace['type'] }}{{ $trace['function'] }}&#40;
                    @foreach($trace['args'] as $arg)
                        @switch(true)
                            @case(is_string($arg)){{ sprintf("\"%s\"", $arg) }}@break
                            @case(is_float($arg)){{ sprintf("%f", $arg) }}@break
                            @case(is_integer($arg)){{ sprintf("%d", $arg) }}@break
                            @case(is_array($arg)){{ sprintf("%s", json_encode($arg)) }}@break
                            @case(is_object($arg)){{ sprintf("%s", get_class($arg)) }}@break
                            @default {{ gettype($arg) }}
                        @endswitch
                        @if (!$loop->last), @endif
                    @endforeach
                    &#41;
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>
