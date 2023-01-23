@extends('layouts.app')

@section('content')
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">

                        <div class="container-fluid">
                            <div class="row">
                                <div class="col">
                                    <h2>
                                        {{ ($post ?? false) ? 'Editar publicação' : 'Nova publicação' }}
                                    </h2>
                                </div>
                            </div>
                        </div>

                        @if (session('status'))
                            <div class="alert alert-success" role="alert">
                                {{ session('status') }}
                            </div>

                        @endif


                        <div class="container">

                            <form method="POST" action="{{ ($post ?? false)
                                    ? route('post.update', ['post' => $post->id])
                                    : route('post.store')
                                }}">
                                @csrf

                                @if(($post ?? false))
                                    <input type="hidden" name="id" value="{{ $post->id }}">
                                @endif

                                <div class="row">
                                    <div class="mb-3">
                                        <label for="post-schedule" class="form-label">{{ 'Agendamento' }}</label>
                                        <input type="text" class="form-control" id="post-schedule" name="schedule" required
                                               data-format="**/**/**** **:**:**" data-mask="DD/MM/YYYY HH:MM:SS"
                                               value="{{ old('schedule') ?? (($post ?? false)
                                                        ? $post->schedule->format('d/m/Y H:i:s')
                                                        : null) }}">
                                        @error('schedule')
                                        <div class="text-danger pt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="row gx-3">
                                    <div class="mb-3 col col-12">
                                        <label for="post-content" class="form-label">{{ 'Conteúdo' }}</label>
                                        <textarea class="form-control" id="post-content" name="content" required rows="5">{{
                                            old('content') ?? (($post ?? false) ? $post->content : null)
                                        }}</textarea>
                                        @error('content')
                                        <div class="text-danger pt-1">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                @if($post ?? false)
                                    <button type="submit" class="btn btn-primary">{{ 'Alterar' }}</button>
                                @else
                                    <button type="submit" class="btn btn-primary">{{ 'Publicar' }}</button>
                                @endif
                                <a href="{{ route('posts') }}"
                                   class="btn btn-outline-secondary">{{ 'Cancelar' }}</a>
                            </form>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
