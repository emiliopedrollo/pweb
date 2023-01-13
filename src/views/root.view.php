@extends('index')
@section('title', 'Raiz')

@section('body')
<div>
    <form method="GET" action="/">
        <label>
            Valor:
            <input type="number" name="number" value="{{$number}}" />
        </label>
        <button type="submit">Calcular Raiz</button>
    </form>

    <h4> {{ strtoupper($blah) }} </h4>

    @auth
        Welcome @user() <br/>
    @endauth

    @arg(['fuck' => 'this'])

    @isset($root)
        <h3>Raiz: {{$root}}</h3>
    @endif
</div>
@endsection
