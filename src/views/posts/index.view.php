@extends('layouts.app')

@section('content')

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card mb-3">
                <div class="card-body">

                    <div class="container-fluid">
                        <div class="row">
                            <div class="col">
                                <h2>
                                    {{'Publicações'}}
                                </h2>
                            </div>
                            <div class="col text-end">
                                <a type="button" href="{{ route('post.create')}}"
                                   class="btn btn-primary mb-3">{{'Nova publicação'}}</a>
                            </div>
                        </div>
                    </div>

                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    <table class="table table-bordered table-striped">
                        <thead class="thead-dark">
                        <tr>
                            <th scope="col">{{ 'Agendamento' }}</th>
                            <th scope="col">{{ 'Excerto' }}</th>
                            <th scope="col">{{ 'Comentários' }}</th>
                            <th scope="col" class="text-end">{{ 'Ações' }}</th>
                        </tr>
                        </thead>
                        <tbody class="align-middle">
                        @foreach($posts as $post)
                            <tr>
                                <td>{{ $post->schedule->format('d/m/Y H:i:s') }}</td>
                                <td>{{ excerpt($post->content,50) }}</td>
                                <td>{{ count($post->comments) }}</td>
                                <td class="text-end">
                                    <form method="POST"
                                          action="{{ route('post.destroy', ['post' => $post->id]) }}">
                                        @method('delete')
                                        <a type="button" href="{{ route('post.show', ['post' => $post->id]) }}"
                                           class="btn btn-outline-primary btn-sm">{{ 'Visualizar' }}</a>
                                        <a type="button" href="{{ route('post.edit', ['post' => $post->id]) }}"
                                           class="btn btn-outline-secondary btn-sm">{{ 'Alterar' }}</a>
                                        <a type="button" href="{{ route('post.destroy', ['post' => $post->id]) }}"
                                           onclick="event.preventDefault();this.closest('form').submit();"
                                           class="btn btn-outline-danger btn-sm">{{ 'Deletar' }}</a>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
